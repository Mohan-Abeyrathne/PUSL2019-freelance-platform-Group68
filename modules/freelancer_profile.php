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
$allowedProficiencies = array("Beginner", "Intermediate", "Expert");
$allowedAiDomains = array(
    "Machine Learning",
    "Deep Learning",
    "NLP",
    "Computer Vision",
    "Generative AI",
    "Data Analytics"
);
$freelancerProfile = null;
$attachedSkills = array();
$availableSkills = array();
$myServices = array();
$failedEditServiceId = 0;

// Handle Freelancer Workspace Actions (Profile, Skills)
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
    } elseif ($postAction === "add_skill") {
        $skillId = isset($_POST["skill_id"]) ? (int) $_POST["skill_id"] : 0;
        $proficiencyLevel = trim((string) ($_POST["proficiency_level"] ?? ""));
        $yearsUsedRaw = trim((string) ($_POST["years_used"] ?? ""));

        $skillErrors = array();

        if ($skillId <= 0) {
            $skillErrors[] = "Please select an AI skill to attach.";
        }
        if (!in_array($proficiencyLevel, $allowedProficiencies, true)) {
            $skillErrors[] = "Please select a valid proficiency level.";
        }
        $yearsUsed = filter_var($yearsUsedRaw, FILTER_VALIDATE_INT);
        if ($yearsUsed === false || $yearsUsed < 0) {
            $skillErrors[] = "Years used must be a whole number of 0 or more.";
        }

        if (count($skillErrors) > 0) {
            $error = implode(" ", $skillErrors);
        } else {
            try {
                // Verify the skill exists in ai_skills
                $skillLookupSql = "SELECT skill_id, skill_name FROM ai_skills WHERE skill_id = ? LIMIT 1";
                $skillLookupStmt = mysqli_prepare($conn, $skillLookupSql);
                $skillRow = null;
                if ($skillLookupStmt) {
                    mysqli_stmt_bind_param($skillLookupStmt, "i", $skillId);
                    if (mysqli_stmt_execute($skillLookupStmt)) {
                        $skillLookupRes = mysqli_stmt_get_result($skillLookupStmt);
                        if ($skillLookupRes) {
                            $skillRow = mysqli_fetch_assoc($skillLookupRes);
                        }
                    }
                    mysqli_stmt_close($skillLookupStmt);
                }

                if (!$skillRow) {
                    $error = "Selected AI skill does not exist.";
                } else {
                    // Check for duplicate mapping gracefully
                    $dupSql = "SELECT 1 FROM freelancer_skills WHERE freelancer_id = ? AND skill_id = ? LIMIT 1";
                    $dupStmt = mysqli_prepare($conn, $dupSql);
                    $alreadyAttached = false;
                    if ($dupStmt) {
                        mysqli_stmt_bind_param($dupStmt, "ii", $sessionUserId, $skillId);
                        if (mysqli_stmt_execute($dupStmt)) {
                            $dupRes = mysqli_stmt_get_result($dupStmt);
                            if ($dupRes && mysqli_num_rows($dupRes) > 0) {
                                $alreadyAttached = true;
                            }
                        }
                        mysqli_stmt_close($dupStmt);
                    }

                    if ($alreadyAttached) {
                        $error = "The skill '" . (string) $skillRow["skill_name"] . "' is already attached to your profile.";
                    } else {
                        $insertSkillSql = "INSERT INTO freelancer_skills (freelancer_id, skill_id, proficiency_level, years_used)
                                           VALUES (?, ?, ?, ?)";
                        $insertSkillStmt = mysqli_prepare($conn, $insertSkillSql);
                        if ($insertSkillStmt) {
                            mysqli_stmt_bind_param($insertSkillStmt, "iisi", $sessionUserId, $skillId, $proficiencyLevel, $yearsUsed);
                            if (mysqli_stmt_execute($insertSkillStmt)) {
                                $message = "AI skill '" . (string) $skillRow["skill_name"] . "' attached to your profile.";
                            } else {
                                $error = "Failed to attach AI skill: " . mysqli_stmt_error($insertSkillStmt);
                            }
                            mysqli_stmt_close($insertSkillStmt);
                        } else {
                            $error = "Failed to prepare skill attachment: " . mysqli_error($conn);
                        }
                    }
                }
            } catch (mysqli_sql_exception $ex) {
                if ((int) $ex->getCode() === 1062) {
                    $error = "This AI skill is already attached to your profile.";
                } else {
                    $error = "Database error while attaching skill: " . $ex->getMessage();
                }
            }
        }
    } elseif ($postAction === "remove_skill") {
        $skillId = isset($_POST["skill_id"]) ? (int) $_POST["skill_id"] : 0;

        if ($skillId <= 0) {
            $error = "Invalid AI skill selected for removal.";
        } else {
            try {
                $deleteSkillSql = "DELETE FROM freelancer_skills WHERE freelancer_id = ? AND skill_id = ?";
                $deleteSkillStmt = mysqli_prepare($conn, $deleteSkillSql);
                if ($deleteSkillStmt) {
                    mysqli_stmt_bind_param($deleteSkillStmt, "ii", $sessionUserId, $skillId);
                    if (mysqli_stmt_execute($deleteSkillStmt)) {
                        if (mysqli_stmt_affected_rows($deleteSkillStmt) > 0) {
                            $message = "AI skill removed from your profile.";
                        } else {
                            $error = "That AI skill was not found on your profile.";
                        }
                    } else {
                        $error = "Failed to remove AI skill: " . mysqli_stmt_error($deleteSkillStmt);
                    }
                    mysqli_stmt_close($deleteSkillStmt);
                } else {
                    $error = "Failed to prepare skill removal: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $ex) {
                $error = "Database error while removing skill: " . $ex->getMessage();
            }
        }
    } elseif ($postAction === "add_service") {
        $serviceTitle = trim((string) ($_POST["service_title"] ?? ""));
        $aiDomain = trim((string) ($_POST["ai_domain"] ?? ""));
        $serviceDesc = trim((string) ($_POST["description"] ?? ""));
        $basePriceRaw = trim((string) ($_POST["base_price"] ?? ""));
        $deliveryDaysRaw = trim((string) ($_POST["delivery_days"] ?? ""));
        $isActive = isset($_POST["is_active"]) ? 1 : 0;

        $serviceErrors = array();

        if ($serviceTitle === "") {
            $serviceErrors[] = "Service title is required.";
        } elseif (strlen($serviceTitle) > 150) {
            $serviceErrors[] = "Service title must not exceed 150 characters.";
        }

        if (!in_array($aiDomain, $allowedAiDomains, true)) {
            $serviceErrors[] = "Please select a valid AI domain.";
        }

        if ($serviceDesc === "") {
            $serviceErrors[] = "Service description is required.";
        }

        if ($basePriceRaw === "" || !is_numeric($basePriceRaw)) {
            $serviceErrors[] = "Please enter a valid numeric base price.";
        } else {
            $basePrice = round((float) $basePriceRaw, 2);
        }

        $deliveryDays = filter_var($deliveryDaysRaw, FILTER_VALIDATE_INT);
        if ($deliveryDays === false) {
            $serviceErrors[] = "Delivery days must be a whole number.";
        }

        if (count($serviceErrors) > 0) {
            $error = implode(" ", $serviceErrors);
        } else {
            try {
                $insServiceSql = "INSERT INTO ai_services (freelancer_id, service_title, ai_domain, description, base_price, delivery_days, is_active)
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                $insServiceStmt = mysqli_prepare($conn, $insServiceSql);
                if ($insServiceStmt) {
                    mysqli_stmt_bind_param(
                        $insServiceStmt,
                        "isssdii",
                        $sessionUserId,
                        $serviceTitle,
                        $aiDomain,
                        $serviceDesc,
                        $basePrice,
                        $deliveryDays,
                        $isActive
                    );

                    if (mysqli_stmt_execute($insServiceStmt)) {
                        $message = "AI service offering '" . $serviceTitle . "' created successfully.";
                    } else {
                        $error = "Database rejected service offering: " . mysqli_stmt_error($insServiceStmt);
                    }
                    mysqli_stmt_close($insServiceStmt);
                } else {
                    $error = "Failed to prepare service creation: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($postAction === "update_service") {
        $serviceId = isset($_POST["service_id"]) ? (int) $_POST["service_id"] : 0;
        $serviceTitle = trim((string) ($_POST["service_title"] ?? ""));
        $aiDomain = trim((string) ($_POST["ai_domain"] ?? ""));
        $serviceDesc = trim((string) ($_POST["description"] ?? ""));
        $basePriceRaw = trim((string) ($_POST["base_price"] ?? ""));
        $deliveryDaysRaw = trim((string) ($_POST["delivery_days"] ?? ""));
        $isActive = isset($_POST["is_active"]) ? 1 : 0;

        $serviceErrors = array();

        if ($serviceId <= 0) {
            $serviceErrors[] = "Invalid service selected for editing.";
        }
        if ($serviceTitle === "") {
            $serviceErrors[] = "Service title is required.";
        } elseif (strlen($serviceTitle) > 150) {
            $serviceErrors[] = "Service title must not exceed 150 characters.";
        }
        if (!in_array($aiDomain, $allowedAiDomains, true)) {
            $serviceErrors[] = "Please select a valid AI domain.";
        }
        if ($serviceDesc === "") {
            $serviceErrors[] = "Service description is required.";
        }
        if ($basePriceRaw === "" || !is_numeric($basePriceRaw)) {
            $serviceErrors[] = "Please enter a valid numeric base price.";
        } else {
            $basePrice = round((float) $basePriceRaw, 2);
        }
        $deliveryDays = filter_var($deliveryDaysRaw, FILTER_VALIDATE_INT);
        if ($deliveryDays === false) {
            $serviceErrors[] = "Delivery days must be a whole number.";
        }

        if (count($serviceErrors) > 0) {
            $error = implode(" ", $serviceErrors);
            $failedEditServiceId = $serviceId;
        } else {
            try {
                // Verify ownership first
                $ownSql = "SELECT 1 FROM ai_services WHERE service_id = ? AND freelancer_id = ? LIMIT 1";
                $ownStmt = mysqli_prepare($conn, $ownSql);
                $ownsService = false;
                if ($ownStmt) {
                    mysqli_stmt_bind_param($ownStmt, "ii", $serviceId, $sessionUserId);
                    if (mysqli_stmt_execute($ownStmt)) {
                        $ownRes = mysqli_stmt_get_result($ownStmt);
                        if ($ownRes && mysqli_num_rows($ownRes) > 0) {
                            $ownsService = true;
                        }
                    }
                    mysqli_stmt_close($ownStmt);
                }

                if (!$ownsService) {
                    $error = "Service offering not found or you do not have permission to edit it.";
                } else {
                    $updServiceSql = "UPDATE ai_services
                                      SET service_title = ?,
                                          ai_domain = ?,
                                          description = ?,
                                          base_price = ?,
                                          delivery_days = ?,
                                          is_active = ?
                                      WHERE service_id = ? AND freelancer_id = ?";
                    $updServiceStmt = mysqli_prepare($conn, $updServiceSql);
                    if ($updServiceStmt) {
                        mysqli_stmt_bind_param(
                            $updServiceStmt,
                            "sssdiiii",
                            $serviceTitle,
                            $aiDomain,
                            $serviceDesc,
                            $basePrice,
                            $deliveryDays,
                            $isActive,
                            $serviceId,
                            $sessionUserId
                        );

                        if (mysqli_stmt_execute($updServiceStmt)) {
                            $message = "AI service offering '" . $serviceTitle . "' updated successfully.";
                        } else {
                            $error = "Database rejected service update: " . mysqli_stmt_error($updServiceStmt);
                            $failedEditServiceId = $serviceId;
                        }
                        mysqli_stmt_close($updServiceStmt);
                    } else {
                        $error = "Failed to prepare service update: " . mysqli_error($conn);
                        $failedEditServiceId = $serviceId;
                    }
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
                $failedEditServiceId = $serviceId;
            }
        }
    } elseif ($postAction === "toggle_service") {
        $serviceId = isset($_POST["service_id"]) ? (int) $_POST["service_id"] : 0;
        $newStatus = (isset($_POST["new_status"]) && (int) $_POST["new_status"] === 1) ? 1 : 0;

        if ($serviceId <= 0) {
            $error = "Invalid service selected.";
        } else {
            try {
                $toggleSql = "UPDATE ai_services SET is_active = ? WHERE service_id = ? AND freelancer_id = ?";
                $toggleStmt = mysqli_prepare($conn, $toggleSql);
                if ($toggleStmt) {
                    mysqli_stmt_bind_param($toggleStmt, "iii", $newStatus, $serviceId, $sessionUserId);
                    if (mysqli_stmt_execute($toggleStmt)) {
                        if (mysqli_stmt_affected_rows($toggleStmt) > 0) {
                            $message = $newStatus === 1
                                ? "AI service offering activated."
                                : "AI service offering marked as inactive.";
                        } else {
                            $error = "Service offering not found or status unchanged.";
                        }
                    } else {
                        $error = "Failed to update service status: " . mysqli_stmt_error($toggleStmt);
                    }
                    mysqli_stmt_close($toggleStmt);
                } else {
                    $error = "Failed to prepare service status update: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } elseif ($postAction === "delete_service") {
        $serviceId = isset($_POST["service_id"]) ? (int) $_POST["service_id"] : 0;

        if ($serviceId <= 0) {
            $error = "Invalid service selected for deletion.";
        } else {
            try {
                $delServiceSql = "DELETE FROM ai_services WHERE service_id = ? AND freelancer_id = ?";
                $delServiceStmt = mysqli_prepare($conn, $delServiceSql);
                if ($delServiceStmt) {
                    mysqli_stmt_bind_param($delServiceStmt, "ii", $serviceId, $sessionUserId);
                    if (mysqli_stmt_execute($delServiceStmt)) {
                        if (mysqli_stmt_affected_rows($delServiceStmt) > 0) {
                            $message = "AI service offering deleted.";
                        } else {
                            $error = "Service offering not found or you do not have permission to delete it.";
                        }
                    } else {
                        $error = "Failed to delete service offering: " . mysqli_stmt_error($delServiceStmt);
                    }
                    mysqli_stmt_close($delServiceStmt);
                } else {
                    $error = "Failed to prepare service deletion: " . mysqli_error($conn);
                }
            } catch (mysqli_sql_exception $e) {
                $error = "Database error: " . $e->getMessage();
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

    // Fetch attached AI skills and remaining unattached skills for this freelancer
    if ($freelancerProfile) {
        try {
            $mySkillsSql = "SELECT
                                fs.skill_id,
                                sk.skill_name,
                                sk.ai_domain,
                                sk.description,
                                fs.proficiency_level,
                                fs.years_used
                            FROM freelancer_skills fs
                            INNER JOIN ai_skills sk ON fs.skill_id = sk.skill_id
                            WHERE fs.freelancer_id = ?
                            ORDER BY FIELD(fs.proficiency_level, 'Expert', 'Intermediate', 'Beginner'), sk.skill_name ASC";
            $mySkillsStmt = mysqli_prepare($conn, $mySkillsSql);
            if ($mySkillsStmt) {
                mysqli_stmt_bind_param($mySkillsStmt, "i", $sessionUserId);
                if (mysqli_stmt_execute($mySkillsStmt)) {
                    $mySkillsRes = mysqli_stmt_get_result($mySkillsStmt);
                    if ($mySkillsRes) {
                        while ($skRow = mysqli_fetch_assoc($mySkillsRes)) {
                            $attachedSkills[] = $skRow;
                        }
                    }
                }
                mysqli_stmt_close($mySkillsStmt);
            }

            $availSkillsSql = "SELECT
                                   sk.skill_id,
                                   sk.skill_name,
                                   sk.ai_domain
                               FROM ai_skills sk
                               WHERE NOT EXISTS (
                                   SELECT 1
                                   FROM freelancer_skills fs
                                   WHERE fs.freelancer_id = ?
                                     AND fs.skill_id = sk.skill_id
                               )
                               ORDER BY sk.ai_domain ASC, sk.skill_name ASC";
            $availSkillsStmt = mysqli_prepare($conn, $availSkillsSql);
            if ($availSkillsStmt) {
                mysqli_stmt_bind_param($availSkillsStmt, "i", $sessionUserId);
                if (mysqli_stmt_execute($availSkillsStmt)) {
                    $availSkillsRes = mysqli_stmt_get_result($availSkillsStmt);
                    if ($availSkillsRes) {
                        while ($avRow = mysqli_fetch_assoc($availSkillsRes)) {
                            $availableSkills[] = $avRow;
                        }
                    }
                }
                mysqli_stmt_close($availSkillsStmt);
            }

            $myServicesSql = "SELECT
                                  service_id,
                                  service_title,
                                  ai_domain,
                                  description,
                                  base_price,
                                  delivery_days,
                                  is_active,
                                  created_at
                              FROM ai_services
                              WHERE freelancer_id = ?
                              ORDER BY is_active DESC, created_at DESC, service_id DESC";
            $myServicesStmt = mysqli_prepare($conn, $myServicesSql);
            if ($myServicesStmt) {
                mysqli_stmt_bind_param($myServicesStmt, "i", $sessionUserId);
                if (mysqli_stmt_execute($myServicesStmt)) {
                    $myServicesRes = mysqli_stmt_get_result($myServicesStmt);
                    if ($myServicesRes) {
                        while ($svRow = mysqli_fetch_assoc($myServicesRes)) {
                            $myServices[] = $svRow;
                        }
                    }
                }
                mysqli_stmt_close($myServicesStmt);
            }
        } catch (mysqli_sql_exception $ex) {
            $error = ($error !== "" ? $error . " " : "") . "Database error loading AI skills/services: " . $ex->getMessage();
        }
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
            <!-- Section 2: My AI Skills -->
            <div class="col-lg-5" id="skillsSection">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 fw-bold mb-0">My AI Skills</h2>
                            <div class="small text-muted">Showcase your technical AI specializations.</div>
                        </div>
                        <span class="badge bg-dark"><?php echo count($attachedSkills); ?> attached</span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <?php if (count($attachedSkills) > 0) { ?>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($attachedSkills as $sk) {
                                    $prof = (string) $sk["proficiency_level"];
                                    $profBadgeClass = "bg-light text-dark border";
                                    if ($prof === "Expert") {
                                        $profBadgeClass = "bg-primary-subtle text-primary border border-primary-subtle";
                                    } elseif ($prof === "Intermediate") {
                                        $profBadgeClass = "bg-info-subtle text-info-emphasis border border-info-subtle";
                                    }
                                    $yrs = (int) $sk["years_used"];
                                ?>
                                    <div class="border rounded p-3 bg-light d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold text-dark"><?php echo e($sk["skill_name"]); ?></div>
                                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                <span class="badge bg-white text-secondary border"><?php echo e($sk["ai_domain"]); ?></span>
                                                <span class="badge <?php echo $profBadgeClass; ?>"><?php echo e($prof); ?></span>
                                                <span class="text-muted small ms-1"><?php echo $yrs; ?> yr<?php echo ($yrs === 1) ? "" : "s"; ?></span>
                                            </div>
                                        </div>
                                        <form method="POST"
                                              action="freelancer_profile.php#skillsSection"
                                              class="m-0 flex-shrink-0"
                                              onsubmit="return confirm('Remove this AI skill from your profile?');">
                                            <input type="hidden" name="action" value="remove_skill">
                                            <input type="hidden" name="skill_id" value="<?php echo (int) $sk["skill_id"]; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove skill">Remove</button>
                                        </form>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-light border text-muted small mb-3">
                                No AI skills attached yet. Use the form below to add your skills.
                            </div>
                        <?php } ?>

                        <hr class="my-3">

                        <h3 class="h6 fw-bold mb-3">Attach Existing AI Skill</h3>
                        <?php if (count($availableSkills) > 0) { ?>
                            <form method="POST" action="freelancer_profile.php#skillsSection" class="row g-2">
                                <input type="hidden" name="action" value="add_skill">

                                <div class="col-12">
                                    <label for="attachSkillSelect" class="form-label small fw-semibold mb-1">AI Skill</label>
                                    <select id="attachSkillSelect" name="skill_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select an AI skill --</option>
                                        <?php
                                        $skillGroupDomain = null;
                                        foreach ($availableSkills as $avSk) {
                                            $avDomain = (string) $avSk["ai_domain"];
                                            if ($avDomain !== $skillGroupDomain) {
                                                if ($skillGroupDomain !== null) {
                                                    echo "</optgroup>";
                                                }
                                                echo '<optgroup label="' . e($avDomain) . '">';
                                                $skillGroupDomain = $avDomain;
                                            }
                                        ?>
                                            <option value="<?php echo (int) $avSk["skill_id"]; ?>">
                                                <?php echo e($avSk["skill_name"]); ?>
                                            </option>
                                        <?php
                                        }
                                        if ($skillGroupDomain !== null) {
                                            echo "</optgroup>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="col-7">
                                    <label for="proficiencySelect" class="form-label small fw-semibold mb-1">Proficiency Level</label>
                                    <select id="proficiencySelect" name="proficiency_level" class="form-select form-select-sm" required>
                                        <?php foreach ($allowedProficiencies as $profOpt) { ?>
                                            <option value="<?php echo e($profOpt); ?>" <?php echo ($profOpt === "Intermediate") ? "selected" : ""; ?>>
                                                <?php echo e($profOpt); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div class="col-5">
                                    <label for="yearsUsedInput" class="form-label small fw-semibold mb-1">Years Used</label>
                                    <input
                                        type="number"
                                        id="yearsUsedInput"
                                        name="years_used"
                                        class="form-control form-control-sm"
                                        step="1"
                                        min="0"
                                        value="1"
                                        required
                                    >
                                </div>

                                <div class="col-12 mt-2">
                                    <button type="submit" class="btn btn-dark btn-sm w-100">Attach Skill</button>
                                </div>
                            </form>
                        <?php } else { ?>
                            <div class="small text-muted">
                                All catalog AI skills are already attached to your profile.
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Section 3: My AI Service Offerings -->
            <?php
            $keepNewServiceInput = ($_SERVER["REQUEST_METHOD"] === "POST" && (string) ($_POST["action"] ?? "") === "add_service" && $error !== "");
            $newSvcTitle = $keepNewServiceInput ? (string) ($_POST["service_title"] ?? "") : "";
            $newSvcDomain = $keepNewServiceInput ? (string) ($_POST["ai_domain"] ?? "Machine Learning") : "Machine Learning";
            $newSvcDesc = $keepNewServiceInput ? (string) ($_POST["description"] ?? "") : "";
            $newSvcPrice = $keepNewServiceInput ? (string) ($_POST["base_price"] ?? "") : "";
            $newSvcDays = $keepNewServiceInput ? (string) ($_POST["delivery_days"] ?? "7") : "7";
            $newSvcActive = $keepNewServiceInput ? isset($_POST["is_active"]) : true;
            ?>
            <div class="col-lg-7" id="servicesSection">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h5 fw-bold mb-0">My AI Service Offerings</h2>
                            <div class="small text-muted">Publish fixed-price AI service packages for clients.</div>
                        </div>
                        <span class="badge bg-dark"><?php echo count($myServices); ?> total</span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <?php if (count($myServices) > 0) { ?>
                            <div class="d-flex flex-column gap-3 mb-3">
                                <?php foreach ($myServices as $svc) {
                                    $svcId = (int) $svc["service_id"];
                                    $isSvcActive = ((int) $svc["is_active"] === 1);
                                    $statusBadgeClass = $isSvcActive
                                        ? "bg-success-subtle text-success border border-success-subtle"
                                        : "bg-secondary-subtle text-secondary border border-secondary-subtle";
                                    $statusLabel = $isSvcActive ? "Active" : "Inactive";
                                    $editCollapseId = "editServiceCollapse_" . $svcId;

                                    $isEditingThis = ($failedEditServiceId === $svcId);
                                    $editTitle = $isEditingThis ? (string) ($_POST["service_title"] ?? "") : (string) $svc["service_title"];
                                    $editDomain = $isEditingThis ? (string) ($_POST["ai_domain"] ?? "Machine Learning") : (string) $svc["ai_domain"];
                                    $editDesc = $isEditingThis ? (string) ($_POST["description"] ?? "") : (string) $svc["description"];
                                    $editPrice = $isEditingThis
                                        ? (string) ($_POST["base_price"] ?? "")
                                        : number_format((float) $svc["base_price"], 2, ".", "");
                                    $editDays = $isEditingThis ? (string) ($_POST["delivery_days"] ?? "") : (string) (int) $svc["delivery_days"];
                                    $editActive = $isEditingThis ? isset($_POST["is_active"]) : $isSvcActive;
                                ?>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <h3 class="h6 fw-bold mb-1"><?php echo e($svc["service_title"]); ?></h3>
                                                <div class="d-flex flex-wrap align-items-center gap-1">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                        <?php echo e($svc["ai_domain"]); ?>
                                                    </span>
                                                    <span class="badge <?php echo $statusBadgeClass; ?>">
                                                        <?php echo $statusLabel; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-dark">
                                                    $<?php echo number_format((float) $svc["base_price"], 2); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo (int) $svc["delivery_days"]; ?> day<?php echo ((int) $svc["delivery_days"] === 1) ? "" : "s"; ?> delivery
                                                </div>
                                            </div>
                                        </div>

                                        <p class="small text-secondary mb-3"><?php echo e($svc["description"]); ?></p>

                                        <div class="d-flex flex-wrap justify-content-end gap-2 pt-2 border-top">
                                            <form method="POST" action="freelancer_profile.php#servicesSection" class="m-0">
                                                <input type="hidden" name="action" value="toggle_service">
                                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $isSvcActive ? 0 : 1; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <?php echo $isSvcActive ? "Deactivate" : "Activate"; ?>
                                                </button>
                                            </form>

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-dark"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?php echo e($editCollapseId); ?>"
                                                aria-expanded="<?php echo $isEditingThis ? "true" : "false"; ?>"
                                                aria-controls="<?php echo e($editCollapseId); ?>"
                                            >
                                                Edit
                                            </button>

                                            <form method="POST"
                                                  action="freelancer_profile.php#servicesSection"
                                                  class="m-0"
                                                  onsubmit="return confirm('Delete this AI service offering?');">
                                                <input type="hidden" name="action" value="delete_service">
                                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </div>

                                        <!-- Inline Edit Form -->
                                        <div class="collapse <?php echo $isEditingThis ? "show" : ""; ?> mt-3" id="<?php echo e($editCollapseId); ?>">
                                            <form method="POST" action="freelancer_profile.php#servicesSection" class="border rounded bg-white p-3 row g-2">
                                                <input type="hidden" name="action" value="update_service">
                                                <input type="hidden" name="service_id" value="<?php echo $svcId; ?>">

                                                <div class="col-md-7">
                                                    <label class="form-label small fw-semibold mb-1">Service Title</label>
                                                    <input
                                                        type="text"
                                                        name="service_title"
                                                        class="form-control form-control-sm"
                                                        maxlength="150"
                                                        value="<?php echo e($editTitle); ?>"
                                                        required
                                                    >
                                                </div>

                                                <div class="col-md-5">
                                                    <label class="form-label small fw-semibold mb-1">AI Domain</label>
                                                    <select name="ai_domain" class="form-select form-select-sm" required>
                                                        <?php foreach ($allowedAiDomains as $domOpt) { ?>
                                                            <option value="<?php echo e($domOpt); ?>" <?php echo ($editDomain === $domOpt) ? "selected" : ""; ?>>
                                                                <?php echo e($domOpt); ?>
                                                            </option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold mb-1">Base Price ($ USD)</label>
                                                    <input
                                                        type="number"
                                                        name="base_price"
                                                        class="form-control form-control-sm"
                                                        step="0.01"
                                                        value="<?php echo e($editPrice); ?>"
                                                        required
                                                    >
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label small fw-semibold mb-1">Delivery Days</label>
                                                    <input
                                                        type="number"
                                                        name="delivery_days"
                                                        class="form-control form-control-sm"
                                                        step="1"
                                                        value="<?php echo e($editDays); ?>"
                                                        required
                                                    >
                                                </div>

                                                <div class="col-md-4 d-flex align-items-end">
                                                    <div class="form-check mb-1">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="is_active"
                                                            value="1"
                                                            id="editActive_<?php echo $svcId; ?>"
                                                            <?php echo $editActive ? "checked" : ""; ?>
                                                        >
                                                        <label class="form-check-label small fw-semibold" for="editActive_<?php echo $svcId; ?>">
                                                            Active Listing
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label small fw-semibold mb-1">Description</label>
                                                    <textarea
                                                        name="description"
                                                        class="form-control form-control-sm"
                                                        rows="2"
                                                        required
                                                    ><?php echo e($editDesc); ?></textarea>
                                                </div>

                                                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#<?php echo e($editCollapseId); ?>"
                                                    >
                                                        Cancel
                                                    </button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-light border text-muted small mb-3">
                                No AI service offerings created yet. Use the form below to publish your first service package.
                            </div>
                        <?php } ?>

                        <hr class="my-3">

                        <h3 class="h6 fw-bold mb-3">Create New AI Service Offering</h3>
                        <form method="POST" action="freelancer_profile.php#servicesSection" class="row g-2">
                            <input type="hidden" name="action" value="add_service">

                            <div class="col-md-7">
                                <label for="newServiceTitle" class="form-label small fw-semibold mb-1">Service Title</label>
                                <input
                                    type="text"
                                    id="newServiceTitle"
                                    name="service_title"
                                    class="form-control form-control-sm"
                                    maxlength="150"
                                    value="<?php echo e($newSvcTitle); ?>"
                                    placeholder="e.g. Private Knowledge Base RAG Assistant"
                                    required
                                >
                            </div>

                            <div class="col-md-5">
                                <label for="newServiceDomain" class="form-label small fw-semibold mb-1">AI Domain</label>
                                <select id="newServiceDomain" name="ai_domain" class="form-select form-select-sm" required>
                                    <?php foreach ($allowedAiDomains as $domOpt) { ?>
                                        <option value="<?php echo e($domOpt); ?>" <?php echo ($newSvcDomain === $domOpt) ? "selected" : ""; ?>>
                                            <?php echo e($domOpt); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="newServicePrice" class="form-label small fw-semibold mb-1">Base Price ($ USD)</label>
                                <input
                                    type="number"
                                    id="newServicePrice"
                                    name="base_price"
                                    class="form-control form-control-sm"
                                    step="0.01"
                                    value="<?php echo e($newSvcPrice); ?>"
                                    placeholder="500.00"
                                    required
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="newServiceDays" class="form-label small fw-semibold mb-1">Delivery Days</label>
                                <input
                                    type="number"
                                    id="newServiceDays"
                                    name="delivery_days"
                                    class="form-control form-control-sm"
                                    step="1"
                                    value="<?php echo e($newSvcDays); ?>"
                                    required
                                >
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check mb-1">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        id="newServiceActive"
                                        <?php echo $newSvcActive ? "checked" : ""; ?>
                                    >
                                    <label class="form-check-label small fw-semibold" for="newServiceActive">
                                        Active Immediately
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="newServiceDesc" class="form-label small fw-semibold mb-1">Description</label>
                                <textarea
                                    id="newServiceDesc"
                                    name="description"
                                    class="form-control form-control-sm"
                                    rows="3"
                                    placeholder="Outline scope, deliverables, model stack, and evaluation metrics..."
                                    required
                                ><?php echo e($newSvcDesc); ?></textarea>
                                <div class="form-text">Base price must be greater than $0.00 and delivery days at least 1.</div>
                            </div>

                            <div class="col-12 mt-2">
                                <button type="submit" class="btn btn-dark btn-sm w-100">Create Service Offering</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

<?php } ?>

<?php
include "../includes/footer.php";
?>
