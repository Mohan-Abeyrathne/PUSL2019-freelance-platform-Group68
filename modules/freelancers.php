<?php
$basePath = "../";
include "../config/db.php";
include "../includes/header.php";

global $conn;

$error = "";

// Read and sanitize filter parameters
$searchQuery = trim((string) ($_GET["q"] ?? ""));
$selectedSkillId = isset($_GET["skill_id"]) ? (int) $_GET["skill_id"] : 0;
if ($selectedSkillId < 0) {
    $selectedSkillId = 0;
}

$allowedAvailabilities = array("Available", "Busy", "Unavailable");
$selectedAvailability = trim((string) ($_GET["availability"] ?? ""));
if (!in_array($selectedAvailability, $allowedAvailabilities, true)) {
    $selectedAvailability = "";
}

// Helper for safe HTML escaping in PHP 8+
function e($value) {
    return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
}

// 1. Fetch all AI skills for the filter dropdown
$allSkills = array();
$skillsSql = "SELECT skill_id, skill_name, ai_domain FROM ai_skills ORDER BY ai_domain ASC, skill_name ASC";
$skillsStmt = mysqli_prepare($conn, $skillsSql);
if ($skillsStmt) {
    if (mysqli_stmt_execute($skillsStmt)) {
        $skillsRes = mysqli_stmt_get_result($skillsStmt);
        if ($skillsRes) {
            while ($skRow = mysqli_fetch_assoc($skillsRes)) {
                $allSkills[] = $skRow;
            }
        }
    }
    mysqli_stmt_close($skillsStmt);
} else {
    $error = "Unable to load AI skills filter: " . mysqli_error($conn);
}

// 2. Query active freelancers joined with users, using fn_FreelancerAvgRating UDF
$freelancers = array();
$freelancerIds = array();

if ($error === "") {
    $whereClauses = array("u.account_status = 'Active'");
    $paramTypes = "";
    $paramValues = array();

    if ($selectedSkillId > 0) {
        $whereClauses[] = "EXISTS (
            SELECT 1
            FROM freelancer_skills fs_filter
            WHERE fs_filter.freelancer_id = f.freelancer_id
              AND fs_filter.skill_id = ?
        )";
        $paramTypes .= "i";
        $paramValues[] = $selectedSkillId;
    }

    if ($selectedAvailability !== "") {
        $whereClauses[] = "f.availability_status = ?";
        $paramTypes .= "s";
        $paramValues[] = $selectedAvailability;
    }

    if ($searchQuery !== "") {
        $likePattern = "%" . $searchQuery . "%";
        $whereClauses[] = "(
            u.full_name LIKE ?
            OR f.headline LIKE ?
            OR COALESCE(f.bio, '') LIKE ?
            OR EXISTS (
                SELECT 1
                FROM freelancer_skills fs_search
                INNER JOIN ai_skills sk_search ON fs_search.skill_id = sk_search.skill_id
                WHERE fs_search.freelancer_id = f.freelancer_id
                  AND (sk_search.skill_name LIKE ? OR sk_search.ai_domain LIKE ?)
            )
            OR EXISTS (
                SELECT 1
                FROM ai_services sv_search
                WHERE sv_search.freelancer_id = f.freelancer_id
                  AND sv_search.is_active = 1
                  AND (sv_search.service_title LIKE ? OR sv_search.description LIKE ? OR sv_search.ai_domain LIKE ?)
            )
        )";
        $paramTypes .= "ssssssss";
        for ($i = 0; $i < 8; $i++) {
            $paramValues[] = $likePattern;
        }
    }

    $freelancersSql = "SELECT
            f.freelancer_id,
            u.full_name,
            f.headline,
            f.bio,
            f.hourly_rate,
            f.experience_years,
            f.availability_status,
            fn_FreelancerAvgRating(f.freelancer_id) AS avg_rating
        FROM freelancers f
        INNER JOIN users u ON f.freelancer_id = u.user_id
        WHERE " . implode(" AND ", $whereClauses) . "
        ORDER BY avg_rating DESC, f.experience_years DESC, u.full_name ASC";

    $freeStmt = mysqli_prepare($conn, $freelancersSql);
    if ($freeStmt) {
        if ($paramTypes !== "") {
            mysqli_stmt_bind_param($freeStmt, $paramTypes, ...$paramValues);
        }
        if (mysqli_stmt_execute($freeStmt)) {
            $freeRes = mysqli_stmt_get_result($freeStmt);
            if ($freeRes) {
                while ($row = mysqli_fetch_assoc($freeRes)) {
                    $fid = (int) $row["freelancer_id"];
                    $freelancers[$fid] = $row;
                    $freelancerIds[] = $fid;
                }
            }
        } else {
            $error = "Failed to execute freelancer search: " . mysqli_stmt_error($freeStmt);
        }
        mysqli_stmt_close($freeStmt);
    } else {
        $error = "Failed to prepare freelancer query: " . mysqli_error($conn);
    }
}

// 3. Batch-fetch attached AI skills and active AI services for matched freelancers
$skillsByFreelancer = array();
$servicesByFreelancer = array();

if ($error === "" && count($freelancerIds) > 0) {
    $placeholders = implode(",", array_fill(0, count($freelancerIds), "?"));
    $idTypes = str_repeat("i", count($freelancerIds));

    // Attached AI skills
    $fsSql = "SELECT
            fs.freelancer_id,
            sk.skill_id,
            sk.skill_name,
            sk.ai_domain,
            fs.proficiency_level,
            fs.years_used
        FROM freelancer_skills fs
        INNER JOIN ai_skills sk ON fs.skill_id = sk.skill_id
        WHERE fs.freelancer_id IN ($placeholders)
        ORDER BY FIELD(fs.proficiency_level, 'Expert', 'Intermediate', 'Beginner'), sk.skill_name ASC";

    $fsStmt = mysqli_prepare($conn, $fsSql);
    if ($fsStmt) {
        mysqli_stmt_bind_param($fsStmt, $idTypes, ...$freelancerIds);
        if (mysqli_stmt_execute($fsStmt)) {
            $fsRes = mysqli_stmt_get_result($fsStmt);
            if ($fsRes) {
                while ($fsRow = mysqli_fetch_assoc($fsRes)) {
                    $fid = (int) $fsRow["freelancer_id"];
                    $skillsByFreelancer[$fid][] = $fsRow;
                }
            }
        }
        mysqli_stmt_close($fsStmt);
    }

    // Active AI service packages
    $svSql = "SELECT
            service_id,
            freelancer_id,
            service_title,
            ai_domain,
            description,
            base_price,
            delivery_days
        FROM ai_services
        WHERE is_active = 1
          AND freelancer_id IN ($placeholders)
        ORDER BY base_price ASC, service_id ASC";

    $svStmt = mysqli_prepare($conn, $svSql);
    if ($svStmt) {
        mysqli_stmt_bind_param($svStmt, $idTypes, ...$freelancerIds);
        if (mysqli_stmt_execute($svStmt)) {
            $svRes = mysqli_stmt_get_result($svStmt);
            if ($svRes) {
                while ($svRow = mysqli_fetch_assoc($svRes)) {
                    $fid = (int) $svRow["freelancer_id"];
                    $servicesByFreelancer[$fid][] = $svRow;
                }
            }
        }
        mysqli_stmt_close($svStmt);
    }
}

$hasActiveFilters = ($searchQuery !== "" || $selectedSkillId > 0 || $selectedAvailability !== "");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Browse AI Freelancers &amp; Services</h1>
        <p class="text-muted mb-0">Explore verified AI engineers, attached skills, ratings, and fixed-price service packages.</p>
    </div>
    <?php if ($isLoggedIn && $currentRole === "Client") { ?>
        <a href="<?php echo $basePath; ?>modules/projects.php" class="btn btn-primary">
            Hire / Post Project
        </a>
    <?php } ?>
</div>

<?php if ($error !== "") { ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php } ?>

<!-- Search & Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="freelancers.php" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label for="keywordSearch" class="form-label small fw-semibold text-muted mb-1">Keyword Search</label>
                <input
                    type="text"
                    id="keywordSearch"
                    name="q"
                    class="form-control"
                    value="<?php echo e($searchQuery); ?>"
                    placeholder="Search name, headline, bio, skill, or service..."
                >
            </div>

            <div class="col-md-3">
                <label for="skillFilter" class="form-label small fw-semibold text-muted mb-1">AI Skill</label>
                <select id="skillFilter" name="skill_id" class="form-select">
                    <option value="0">All AI Skills</option>
                    <?php
                    $currentDomain = null;
                    foreach ($allSkills as $skill) {
                        $domain = (string) $skill["ai_domain"];
                        if ($domain !== $currentDomain) {
                            if ($currentDomain !== null) {
                                echo "</optgroup>";
                            }
                            echo '<optgroup label="' . e($domain) . '">';
                            $currentDomain = $domain;
                        }
                        $skId = (int) $skill["skill_id"];
                        $selectedAttr = ($selectedSkillId === $skId) ? "selected" : "";
                    ?>
                        <option value="<?php echo $skId; ?>" <?php echo $selectedAttr; ?>>
                            <?php echo e($skill["skill_name"]); ?>
                        </option>
                    <?php
                    }
                    if ($currentDomain !== null) {
                        echo "</optgroup>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="availabilityFilter" class="form-label small fw-semibold text-muted mb-1">Availability</label>
                <select id="availabilityFilter" name="availability" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($allowedAvailabilities as $availOpt) { ?>
                        <option value="<?php echo e($availOpt); ?>" <?php echo ($selectedAvailability === $availOpt) ? "selected" : ""; ?>>
                            <?php echo e($availOpt); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark flex-grow-1">Filter</button>
                <?php if ($hasActiveFilters) { ?>
                    <a href="freelancers.php" class="btn btn-outline-secondary">Reset</a>
                <?php } ?>
            </div>
        </form>
    </div>
</div>

<!-- Results Summary -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">
        Showing <strong><?php echo count($freelancers); ?></strong> active freelancer<?php echo (count($freelancers) === 1) ? "" : "s"; ?>
        <?php if ($hasActiveFilters) { ?>
            matching your current filters
        <?php } ?>
    </p>
</div>

<!-- Freelancer Cards Grid -->
<?php if (count($freelancers) > 0) { ?>
    <div class="row g-4">
        <?php foreach ($freelancers as $fid => $freelancer) {
            $fullName = (string) $freelancer["full_name"];
            $initials = strtoupper(substr(trim($fullName), 0, 1));
            if ($initials === "") {
                $initials = "F";
            }

            $avgRating = (float) ($freelancer["avg_rating"] ?? 0);
            $hasRating = ($avgRating > 0);
            $ratingBadgeClass = $hasRating
                ? "bg-warning-subtle text-warning-emphasis border border-warning-subtle"
                : "bg-light text-muted border";
            $ratingText = $hasRating
                ? "&#9733; " . number_format($avgRating, 2) . " / 5.0"
                : "&#9733; New (0.00)";

            $avail = (string) ($freelancer["availability_status"] ?? "Available");
            $availBadgeClass = "bg-secondary-subtle text-secondary border border-secondary-subtle";
            if ($avail === "Available") {
                $availBadgeClass = "bg-success-subtle text-success border border-success-subtle";
            } elseif ($avail === "Busy") {
                $availBadgeClass = "bg-warning-subtle text-warning-emphasis border border-warning-subtle";
            }

            $attachedSkills = $skillsByFreelancer[$fid] ?? array();
            $activeServices = $servicesByFreelancer[$fid] ?? array();
            $serviceCount = count($activeServices);
            $collapseId = "servicesCollapse_" . $fid;
        ?>
            <div class="col-12 col-lg-6">
                <div class="card border shadow-sm h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <!-- Profile Header -->
                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                     style="width: 50px; height: 50px; font-size: 1.15rem;">
                                    <?php echo e($initials); ?>
                                </div>
                                <div>
                                    <h2 class="h5 fw-bold mb-1"><?php echo e($fullName); ?></h2>
                                    <div class="text-muted small"><?php echo e($freelancer["headline"]); ?></div>
                                </div>
                            </div>
                            <span class="badge <?php echo $availBadgeClass; ?> flex-shrink-0">
                                <?php echo e($avail); ?>
                            </span>
                        </div>

                        <!-- Rating, Experience & Hourly Rate -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 px-3 bg-light rounded border mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge <?php echo $ratingBadgeClass; ?>"><?php echo $ratingText; ?></span>
                                <span class="text-muted small">
                                    <?php echo (int) $freelancer["experience_years"]; ?>+ yrs experience
                                </span>
                            </div>
                            <div class="fw-bold text-primary">
                                $<?php echo number_format((float) $freelancer["hourly_rate"], 2); ?><span class="text-muted fw-normal small">/hr</span>
                            </div>
                        </div>

                        <!-- Bio -->
                        <?php if (!empty($freelancer["bio"])) { ?>
                            <p class="text-secondary small mb-3"><?php echo e($freelancer["bio"]); ?></p>
                        <?php } ?>

                        <!-- Attached AI Skills -->
                        <div class="mb-3">
                            <div class="small fw-semibold text-dark mb-2">AI Skills</div>
                            <?php if (count($attachedSkills) > 0) { ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php foreach ($attachedSkills as $sk) {
                                        $prof = (string) $sk["proficiency_level"];
                                    ?>
                                        <span class="badge bg-light text-dark border"
                                              title="<?php echo e($sk["ai_domain"] . " • " . $prof . " (" . (int) $sk["years_used"] . " yrs)"); ?>">
                                            <?php echo e($sk["skill_name"]); ?>
                                            <span class="text-muted fw-normal">&middot; <?php echo e($prof); ?></span>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <span class="text-muted small">No AI skills attached yet.</span>
                            <?php } ?>
                        </div>

                        <!-- Expandable Active AI Service Packages -->
                        <div class="mb-3">
                            <?php if ($serviceCount > 0) { ?>
                                <button
                                    class="btn btn-sm btn-outline-dark w-100 d-flex justify-content-between align-items-center"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?php echo e($collapseId); ?>"
                                    aria-expanded="false"
                                    aria-controls="<?php echo e($collapseId); ?>"
                                >
                                    <span class="fw-semibold">AI Service Offerings (<?php echo $serviceCount; ?>)</span>
                                    <span class="small text-muted">Expand / Collapse</span>
                                </button>

                                <div class="collapse mt-2" id="<?php echo e($collapseId); ?>">
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($activeServices as $svc) { ?>
                                            <div class="border rounded p-3 bg-light">
                                                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                                    <h3 class="h6 fw-bold mb-0"><?php echo e($svc["service_title"]); ?></h3>
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle flex-shrink-0">
                                                        <?php echo e($svc["ai_domain"]); ?>
                                                    </span>
                                                </div>
                                                <p class="small text-muted mb-2"><?php echo e($svc["description"]); ?></p>
                                                <div class="d-flex justify-content-between align-items-center small pt-2 border-top">
                                                    <span class="fw-bold text-dark">
                                                        $<?php echo number_format((float) $svc["base_price"], 2); ?>
                                                    </span>
                                                    <span class="text-muted">
                                                        Delivery: <?php echo (int) $svc["delivery_days"]; ?> day<?php echo ((int) $svc["delivery_days"] === 1) ? "" : "s"; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="small text-muted border rounded px-3 py-2 bg-light">
                                    No active AI service packages listed.
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center gap-2">
                            <span class="small text-muted">
                                <?php echo $serviceCount; ?> active service<?php echo ($serviceCount === 1) ? "" : "s"; ?>
                            </span>
                            <?php if ($isLoggedIn && $currentRole === "Client") { ?>
                                <a href="<?php echo $basePath; ?>modules/projects.php" class="btn btn-sm btn-primary">
                                    Hire / Post Project
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } elseif ($error === "") { ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <h2 class="h5 fw-bold mb-2">No matching freelancers found</h2>
            <p class="text-muted mb-3">Try adjusting your keyword search, AI skill filter, or availability status.</p>
            <?php if ($hasActiveFilters) { ?>
                <a href="freelancers.php" class="btn btn-outline-dark btn-sm">Clear All Filters</a>
            <?php } ?>
        </div>
    </div>
<?php } ?>

<?php
include "../includes/footer.php";
?>
