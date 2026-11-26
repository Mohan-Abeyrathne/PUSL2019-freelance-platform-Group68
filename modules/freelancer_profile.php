<?php
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$basePath = "../";
include "../config/db.php";

global $conn;

$message = "";
$error = "";

// Helper for safe HTML escaping in PHP 8+
function e($value) {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Handle ?action=logout and Demo Mode Sign-In BEFORE header.php outputs HTML
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["action"]) && $_GET["action"] === "logout") {
    unset(
        $_SESSION["user_id"],
        $_SESSION["full_name"],
        $_SESSION["name"],
        $_SESSION["email"],
        $_SESSION["role"]
    );
    $message = "You have been signed out of the demo session. Select a freelancer below to sign in.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $postAction = (string) ($_POST["action"] ?? "");

    if ($postAction === "demo_login") {
        $demoFreelancerId = isset($_POST["demo_freelancer_id"]) ? (int) $_POST["demo_freelancer_id"] : 0;

        if ($demoFreelancerId <= 0) {
            $error = "Please select a freelancer account to sign in.";
        } else {
            try {
                $loginSql = "SELECT u.user_id, u.full_name, u.email, u.role, u.account_status
                             FROM freelancers f
                             INNER JOIN users u ON f.freelancer_id = u.user_id
                             WHERE f.freelancer_id = ?
                             LIMIT 1";
                $loginStmt = mysqli_prepare($conn, $loginSql);
                if ($loginStmt) {
                    mysqli_stmt_bind_param($loginStmt, "i", $demoFreelancerId);
                    if (mysqli_stmt_execute($loginStmt)) {
                        $loginRes = mysqli_stmt_get_result($loginStmt);
                        if ($loginRes && ($demoUser = mysqli_fetch_assoc($loginRes))) {
                            $_SESSION["user_id"] = (int) $demoUser["user_id"];
                            $_SESSION["role"] = "Freelancer";
                            $_SESSION["name"] = (string) $demoUser["full_name"];
                            $_SESSION["full_name"] = (string) $demoUser["full_name"];
                            $_SESSION["email"] = (string) $demoUser["email"];
                            $message = "Signed in as " . (string) $demoUser["full_name"] . " (Demo Mode).";
                        } else {
                            $error = "Selected freelancer account does not exist.";
                        }
                    } else {
                        $error = "Unable to complete demo sign-in: " . mysqli_stmt_error($loginStmt);
                    }
                    mysqli_stmt_close($loginStmt);
                } else {
                    $error = "Unable to prepare demo sign-in query: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $ex) {
                $error = "Database error during demo sign-in: " . $ex->getMessage();
            }
        }
    } elseif ($postAction === "demo_logout") {
        unset(
            $_SESSION["user_id"],
            $_SESSION["full_name"],
            $_SESSION["name"],
            $_SESSION["email"],
            $_SESSION["role"]
        );
        $message = "You have been signed out of the demo session.";
    }
}

include "../includes/header.php";

$isFreelancer = ($isLoggedIn && $currentRole === "Freelancer");
$sessionUserId = $isFreelancer ? (int) $_SESSION["user_id"] : 0;

$allowedAvailabilities = array("Available", "Busy", "Unavailable");
$freelancerProfile = null;

// Handle Profile & Availability Update (Freelancer only)
if ($isFreelancer && $_SERVER["REQUEST_METHOD"] === "POST") {
    $postAction = (string) ($_POST["action"] ?? "");

    if ($postAction === "update_profile") {
        $headline = trim((string) ($_POST["headline"] ?? ""));
        $bioInput = trim((string) ($_POST["bio"] ?? ""));
        $hourlyRateRaw = trim((string) ($_POST["hourly_rate"] ?? ""));
        $experienceRaw = trim((string) ($_POST["experience_years"] ?? ""));
        $availabilityStatus = trim((string) ($_POST["availability_status"] ?? ""));

        $validationErrors = array();

        if ($headline === "") {
            $validationErrors[] = "Professional headline is required.";
        } elseif (strlen($headline) > 150) {
            $validationErrors[] = "Headline must not exceed 150 characters.";
        }

        if ($hourlyRateRaw === "" || !is_numeric($hourlyRateRaw)) {
            $validationErrors[] = "Please enter a valid hourly rate.";
        } else {
            $hourlyRate = round((float) $hourlyRateRaw, 2);
            if ($hourlyRate <= 0) {
                $validationErrors[] = "Hourly rate must be greater than $0.00.";
            }
        }

        $experienceYears = filter_var($experienceRaw, FILTER_VALIDATE_INT);
        if ($experienceYears === false || $experienceYears < 0) {
            $validationErrors[] = "Experience years must be a whole number of 0 or more.";
        }

        if (!in_array($availabilityStatus, $allowedAvailabilities, true)) {
            $validationErrors[] = "Please select a valid availability status.";
        }

        if (count($validationErrors) > 0) {
            $error = implode(" ", $validationErrors);
        } else {
            $bioParam = ($bioInput === "") ? null : $bioInput;

            try {
                $updateSql = "UPDATE freelancers
                              SET headline = ?,
                                  bio = ?,
                                  hourly_rate = ?,
                                  experience_years = ?,
                                  availability_status = ?
                              WHERE freelancer_id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                if ($updateStmt) {
                    mysqli_stmt_bind_param(
                        $updateStmt,
                        "ssdisi",
                        $headline,
                        $bioParam,
                        $hourlyRate,
                        $experienceYears,
                        $availabilityStatus,
                        $sessionUserId
                    );

                    if (mysqli_stmt_execute($updateStmt)) {
                        $message = "Your freelancer profile and availability have been updated.";
                    } else {
                        $error = "Failed to update profile: " . mysqli_stmt_error($updateStmt);
                    }
                    mysqli_stmt_close($updateStmt);
                } else {
                    $error = "Failed to prepare profile update: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $ex) {
                $error = "Database error while updating profile: " . $ex->getMessage();
            }
        }
    }
}

// Fetch logged-in freelancer profile + UDF average rating
if ($isFreelancer) {
    try {
        $profileSql = "SELECT
                           f.freelancer_id,
                           u.full_name,
                           u.email,
                           u.account_status,
                           f.headline,
                           f.bio,
                           f.hourly_rate,
                           f.experience_years,
                           f.availability_status,
                           fn_FreelancerAvgRating(f.freelancer_id) AS avg_rating
                       FROM freelancers f
                       INNER JOIN users u ON f.freelancer_id = u.user_id
                       WHERE f.freelancer_id = ?
                       LIMIT 1";
        $profileStmt = mysqli_prepare($conn, $profileSql);
        if ($profileStmt) {
            mysqli_stmt_bind_param($profileStmt, "i", $sessionUserId);
            if (mysqli_stmt_execute($profileStmt)) {
                $profileRes = mysqli_stmt_get_result($profileStmt);
                if ($profileRes) {
                    $freelancerProfile = mysqli_fetch_assoc($profileRes);
                }
            } else {
                $error = ($error !== "" ? $error . " " : "") . "Unable to load profile: " . mysqli_stmt_error($profileStmt);
            }
            mysqli_stmt_close($profileStmt);
        } else {
            $error = ($error !== "" ? $error . " " : "") . "Unable to prepare profile lookup: " . mysqli_error($conn);
        }
    } catch (mysqli_sql_exception $ex) {
        $error = ($error !== "" ? $error . " " : "") . "Database error loading profile: " . $ex->getMessage();
    }

    if (!$freelancerProfile && $error === "") {
        $error = "No freelancer profile record was found for your user account.";
    }
}

// Fetch all freelancers joined with users for the Demo Mode sign-in / switch dropdown
$demoFreelancers = array();
try {
    $demoListSql = "SELECT f.freelancer_id, u.full_name, u.email, f.headline, f.availability_status, u.account_status
                    FROM freelancers f
                    INNER JOIN users u ON f.freelancer_id = u.user_id
                    ORDER BY u.full_name ASC";
    $demoListStmt = mysqli_prepare($conn, $demoListSql);
    if ($demoListStmt) {
        if (mysqli_stmt_execute($demoListStmt)) {
            $demoListRes = mysqli_stmt_get_result($demoListStmt);
            if ($demoListRes) {
                while ($dRow = mysqli_fetch_assoc($demoListRes)) {
                    $demoFreelancers[] = $dRow;
                }
            }
        } else {
            $error = ($error !== "" ? $error . " " : "") . "Unable to load freelancer accounts: " . mysqli_stmt_error($demoListStmt);
        }
        mysqli_stmt_close($demoListStmt);
    } else {
        $error = ($error !== "" ? $error . " " : "") . "Unable to prepare freelancer list query: " . mysqli_error($conn);
    }
} catch (mysqli_sql_exception $ex) {
    $error = ($error !== "" ? $error . " " : "") . "Database error loading freelancer accounts: " . $ex->getMessage();
}
?>

<?php if (!$isFreelancer) { ?>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="alert alert-warning border shadow-sm mb-4" role="alert">
                <h1 class="h5 fw-bold mb-1">Freelancer Access Required</h1>
                <p class="mb-0">
                    This workspace is restricted strictly to logged-in users with the <strong>Freelancer</strong> role.
                    <?php if ($isLoggedIn) { ?>
                        You are currently signed in as <strong><?php echo e($currentName); ?></strong> (<?php echo e($currentRole); ?>).
                    <?php } else { ?>
                        You are currently browsing as a <strong>Guest</strong>.
                    <?php } ?>
                </p>
            </div>

            <?php if ($message !== "") { ?>
                <div class="alert alert-success"><?php echo e($message); ?></div>
            <?php } ?>

            <?php if ($error !== "") { ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php } ?>

            <!-- Standalone Demo Sign-In Card (works directly inside <main> without login.php / logout.php) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Freelancer Workspace Sign-In (Demo Mode)</h2>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted small mb-3">
                        Choose a freelancer profile below to sign in and test the Freelancer Workspace directly on this page.
                    </p>

                    <?php if (count($demoFreelancers) > 0) { ?>
                        <form method="POST" action="freelancer_profile.php" class="row g-3">
                            <input type="hidden" name="action" value="demo_login">
                            <div class="col-12">
                                <label for="demoFreelancerSelect" class="form-label fw-semibold">Select Freelancer Account</label>
                                <select id="demoFreelancerSelect" name="demo_freelancer_id" class="form-select" required>
                                    <option value="">-- Choose a freelancer --</option>
                                    <?php foreach ($demoFreelancers as $df) { ?>
                                        <option value="<?php echo (int) $df["freelancer_id"]; ?>">
                                            <?php echo e($df["full_name"]); ?> (<?php echo e($df["email"]); ?>) &mdash; <?php echo e($df["availability_status"]); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-dark">Sign In as Freelancer</button>
                                <a href="freelancers.php" class="btn btn-outline-secondary">Browse Public Directory</a>
                            </div>
                        </form>
                    <?php } else { ?>
                        <div class="alert alert-light border text-muted mb-0">
                            No freelancer records found in the database. Make sure <code>database/02_seed_data.sql</code> has been imported.
                        </div>
                    <?php } ?>

                    <?php if ($isLoggedIn) { ?>
                        <hr class="my-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted">Clear current session (<?php echo e($currentRole); ?>)</span>
                            <a href="freelancer_profile.php?action=logout" class="btn btn-sm btn-outline-danger">Switch Freelancer / Logout</a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>

    <?php
    // Prepare display & form values (keep submitted values if validation failed)
    $useSubmittedValues = ($_SERVER["REQUEST_METHOD"] === "POST" && (string) ($_POST["action"] ?? "") === "update_profile" && $error !== "");
    $formHeadline = $useSubmittedValues
        ? (string) ($_POST["headline"] ?? "")
        : (string) ($freelancerProfile["headline"] ?? "");
    $formBio = $useSubmittedValues
        ? (string) ($_POST["bio"] ?? "")
        : (string) ($freelancerProfile["bio"] ?? "");
    $formHourlyRate = $useSubmittedValues
        ? (string) ($_POST["hourly_rate"] ?? "")
        : number_format((float) ($freelancerProfile["hourly_rate"] ?? 0), 2, ".", "");
    $formExperienceYears = $useSubmittedValues
        ? (string) ($_POST["experience_years"] ?? "")
        : (string) (int) ($freelancerProfile["experience_years"] ?? 0);
    $formAvailability = $useSubmittedValues
        ? (string) ($_POST["availability_status"] ?? "Available")
        : (string) ($freelancerProfile["availability_status"] ?? "Available");

    $avgRating = (float) ($freelancerProfile["avg_rating"] ?? 0);
    $ratingText = ($avgRating > 0)
        ? "&#9733; " . number_format($avgRating, 2) . " / 5.0"
        : "&#9733; New (0.00)";
    $ratingBadgeClass = ($avgRating > 0)
        ? "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
        : "bg-light text-muted border";

    $currentAvailability = (string) ($freelancerProfile["availability_status"] ?? "Available");
    $availBadgeClass = "bg-secondary-subtle text-secondary border border-secondary-subtle";
    if ($currentAvailability === "Available") {
        $availBadgeClass = "bg-success-subtle text-success border border-success-subtle";
    } elseif ($currentAvailability === "Busy") {
        $availBadgeClass = "bg-warning-subtle text-warning-emphasis border border-warning-subtle";
    }
    ?>

    <!-- Workspace Header -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h1 class="h3 fw-bold mb-0">My Freelancer Workspace</h1>
                        <span class="badge <?php echo $availBadgeClass; ?>"><?php echo e($currentAvailability); ?></span>
                        <span class="badge <?php echo $ratingBadgeClass; ?>"><?php echo $ratingText; ?></span>
                    </div>
                    <?php if ($freelancerProfile) { ?>
                        <div class="text-muted">
                            <span class="fw-semibold text-dark"><?php echo e($freelancerProfile["full_name"]); ?></span>
                            &middot; <?php echo e($freelancerProfile["email"]); ?>
                        </div>
                    <?php } ?>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="freelancers.php" class="btn btn-sm btn-outline-dark">View Public Directory</a>

                    <?php if (count($demoFreelancers) > 1) { ?>
                        <form method="POST" action="freelancer_profile.php" class="d-flex align-items-center gap-1">
                            <input type="hidden" name="action" value="demo_login">
                            <select name="demo_freelancer_id" class="form-select form-select-sm" aria-label="Switch demo freelancer" onchange="this.form.submit()">
                                <option value="">Switch demo user...</option>
                                <?php foreach ($demoFreelancers as $df) { ?>
                                    <option value="<?php echo (int) $df["freelancer_id"]; ?>" <?php echo ((int) $df["freelancer_id"] === $sessionUserId) ? "selected" : ""; ?>>
                                        <?php echo e($df["full_name"]); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </form>
                    <?php } ?>

                    <a href="freelancer_profile.php?action=logout" class="btn btn-sm btn-outline-danger">Switch Freelancer / Logout</a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($message !== "") { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if ($error !== "") { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if ($freelancerProfile) { ?>
        <!-- Section 1: Profile & Availability Management -->
        <div class="card border-0 shadow-sm mb-4" id="profileSection">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h5 fw-bold mb-0">Profile &amp; Availability Management</h2>
                    <div class="small text-muted">Keep your headline, bio, hourly rate, experience, and availability up to date.</div>
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="freelancer_profile.php" class="row g-3">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="col-12">
                        <label for="headlineInput" class="form-label fw-semibold">Professional Headline</label>
                        <input
                            type="text"
                            id="headlineInput"
                            name="headline"
                            class="form-control"
                            maxlength="150"
                            value="<?php echo e($formHeadline); ?>"
                            placeholder="e.g. Generative AI &amp; RAG Architect"
                            required
                        >
                        <div class="form-text">Up to 150 characters shown on your public freelancer card.</div>
                    </div>

                    <div class="col-md-4">
                        <label for="hourlyRateInput" class="form-label fw-semibold">Hourly Rate ($ USD)</label>
                        <input
                            type="number"
                            id="hourlyRateInput"
                            name="hourly_rate"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            value="<?php echo e($formHourlyRate); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="experienceYearsInput" class="form-label fw-semibold">Experience (Years)</label>
                        <input
                            type="number"
                            id="experienceYearsInput"
                            name="experience_years"
                            class="form-control"
                            step="1"
                            min="0"
                            value="<?php echo e($formExperienceYears); ?>"
                            required
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="availabilitySelect" class="form-label fw-semibold">Availability Status</label>
                        <select id="availabilitySelect" name="availability_status" class="form-select" required>
                            <?php foreach ($allowedAvailabilities as $availOption) { ?>
                                <option value="<?php echo e($availOption); ?>" <?php echo ($formAvailability === $availOption) ? "selected" : ""; ?>>
                                    <?php echo e($availOption); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="bioTextarea" class="form-label fw-semibold">Bio / Summary</label>
                        <textarea
                            id="bioTextarea"
                            name="bio"
                            class="form-control"
                            rows="4"
                            placeholder="Describe your AI specialties, production experience, and typical deliverables..."
                        ><?php echo e($formBio); ?></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end pt-2">
                        <button type="submit" class="btn btn-primary px-4">Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Upcoming Workspace Sections (Skills & Services) -->
        <div class="row g-4">
            <!-- Section 2: My AI Skills (placeholder for next step) -->
            <div class="col-lg-5" id="skillsSection">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h2 class="h5 fw-bold mb-0">My AI Skills</h2>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-0">
                            Attach your AI skills and proficiency levels here (coming in the next step).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 3: My AI Service Offerings (placeholder for next step) -->
            <div class="col-lg-7" id="servicesSection">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3">
                        <h2 class="h5 fw-bold mb-0">My AI Service Offerings</h2>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-0">
                            Create and manage your fixed-price AI service packages here (coming in the next step).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

<?php } ?>

<?php
include "../includes/footer.php";
?>
