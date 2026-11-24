<?php

//echo "admin dashboard";
$basePath = "../";
include "../config/db.php";
include "../includes/header.php";

global $conn;
$message = "";
$error = "";

$openSection = ""; //for keep track of open section

$disputeFilter = "All"; //dispute status filter
if (isset($_GET["dispute_filter"])) {
    $allowedDisputeStatuses = array("All", "Open", "Under Review", "Resolved");
    if (in_array($_GET["dispute_filter"], $allowedDisputeStatuses)) {
        $disputeFilter = $_GET["dispute_filter"];
        $openSection = "disputes";
    }
}

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "Admin") {
    $error = "Access denied. Please log in with an Admin account to view this page!!";
}


if ($error == "" && $_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
// update user section
    if ($action == "update_user_status") {
        $targetUserId = (int) $_POST["user_id"];
        $newStatus = mysqli_real_escape_string($conn, $_POST["account_status"]);

        if ($newStatus != "Active" && $newStatus != "Suspended" && $newStatus != "Inactive") {
            $newStatus = "Active";
        }

        $updUserSql = "UPDATE users SET account_status = '$newStatus' WHERE user_id = $targetUserId";
        if (mysqli_query($conn, $updUserSql)) {
            $message = "User #$targetUserId status updated to $newStatus.";
        } else {
            $error = "Failed to update user status: " . mysqli_error($conn);
        }
    }
// Add a new Manager account section
    if ($action == "add_manager") {
        $mgrName = trim($_POST["manager_name"]);
        $mgrEmail = trim($_POST["manager_email"]);
        $mgrPass = $_POST["manager_password"];

        if ($mgrName == "" || $mgrEmail == "" || $mgrPass == "") {
            $error = "Please fill in full name, email, and password to create a Manager.";
        } elseif (!filter_var($mgrEmail, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address for the Manager.";
        } elseif (strlen($mgrPass) < 6) {
            $error = "Manager password must be at least 6 characters long.";
        } else {
            $safeName = mysqli_real_escape_string($conn, $mgrName);
            $safeEmail = mysqli_real_escape_string($conn, $mgrEmail);

            // Check if the email already exists
            $chkRes = mysqli_query($conn, "SELECT user_id FROM users WHERE email = '$safeEmail' LIMIT 1");
            if ($chkRes && mysqli_num_rows($chkRes) > 0) {
                $error = "An account with email '$mgrEmail' already exists.";
            } else {
                $hashedPass = mysqli_real_escape_string($conn, password_hash($mgrPass, PASSWORD_DEFAULT));
                $insMgrSql = "INSERT INTO users (full_name, email, password_hash, role, account_status)
                                VALUES ('$safeName', '$safeEmail', '$hashedPass', 'Manager', 'Active')";
                if (mysqli_query($conn, $insMgrSql)) {
                    $message = "Manager account for '$mgrName' created successfully.";
                } else {
                    $error = "Could not create Manager account: " . mysqli_error($conn);
                }
            }
        }
    }

    // Add a new AI skill using stored procedure sp_AddSkill
    if ($action == "add_skill") {
        $skillName = mysqli_real_escape_string($conn, trim($_POST["skill_name"]));
        $aiDomain = mysqli_real_escape_string($conn, $_POST["ai_domain"]);
        $description = mysqli_real_escape_string($conn, trim($_POST["description"]));

        if ($skillName == "") {
            $error = "Please enter an AI skill name.";
        } else {
            if ($description == "") {
                $descSql = "NULL";
            } else {
                $descSql = "'$description'";
            }

            $callSpSql = "CALL sp_AddSkill('$skillName', '$aiDomain', $descSql)";
            //handling the errors can occur with the sp call
            if (mysqli_query($conn, $callSpSql)) {
                while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
                    $extraRes = mysqli_use_result($conn);
                    if ($extraRes) {
                        mysqli_free_result($extraRes);
                    }
                }
                $message = "AI skill '$skillName' added successfully.";
            } else {
                    $error = "Could not add AI skill: " . mysqli_error($conn);
                }
            
        }
    }

    // Resolve or update a dispute section
    if ($action == "resolve_dispute") {
        $disputeId = (int) $_POST["dispute_id"];
        $disputeStatus = mysqli_real_escape_string($conn, $_POST["dispute_status"]);
        $resNotes = mysqli_real_escape_string($conn, trim($_POST["resolution_notes"]));
        $adminId = (int) $_SESSION["user_id"];

        if ($disputeStatus != "Open" && $disputeStatus != "Under Review" && $disputeStatus != "Resolved") {
            $disputeStatus = "Under Review";
        }

        if ($resNotes == "") {
            $notesSql = "NULL";
        } else {
            $notesSql = "'$resNotes'";
        }

        $updDisputeSql = "UPDATE disputes
                            SET dispute_status = '$disputeStatus',
                            resolution_notes = $notesSql,
                            resolved_by = $adminId
                        WHERE dispute_id = $disputeId";

        if (mysqli_query($conn, $updDisputeSql)) {
            $message = "Dispute #$disputeId updated to '$disputeStatus'.";
        } else {
            $error = "Failed to update dispute: " . mysqli_error($conn);
        }
    }
}

//  Count users by role
$totalUsers = 0;
$countAdmins = 0;
$countManagers = 0;
$countFreelancers = 0;
$countClients = 0;
$totalSkills = 0;
$totalDisputes = 0;

// if ($error == "") {
if (isset($_SESSION["user_id"]) && $_SESSION["role"] == "Admin") {
    $roleCountSql = "SELECT role, COUNT(*) AS cnt FROM users GROUP BY role";
    $roleCountRes = mysqli_query($conn, $roleCountSql);
    if ($roleCountRes) {
        while ($rcRow = mysqli_fetch_assoc($roleCountRes)) {
            $cnt = (int) $rcRow["cnt"];
            $totalUsers += $cnt;
            if ($rcRow["role"] == "Admin") { 
                $countAdmins = $cnt; 
                }
            if ($rcRow["role"] == "Manager") { 
                $countManagers = $cnt; 
                }
            if ($rcRow["role"] == "Freelancer") { 
                $countFreelancers = $cnt; 
                }
            if ($rcRow["role"] == "Client") { 
                $countClients = $cnt; 
                }

            }

            $skCntRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM ai_skills");
            if ($skCntRes) {
                $skCntRow = mysqli_fetch_assoc($skCntRes);
                $totalSkills = (int) $skCntRow["cnt"];
            }

            $dpCntRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM disputes");
            if ($dpCntRes) {
                $dpCntRow = mysqli_fetch_assoc($dpCntRes);
                $totalDisputes = (int) $dpCntRow["cnt"];
            }
        }
    }


// role filter for viewing users
$roleFilter = "All";
if (isset($_GET["role_filter"])) {
    $allowedRoles = array("All", "Admin", "Manager", "Freelancer", "Client");
    if (in_array($_GET["role_filter"], $allowedRoles)) {
        $roleFilter = $_GET["role_filter"];
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Admin Control Panel</h1>
        <p class="text-muted mb-0">Manage user accounts, AI skill categories, and platform disputes.</p>
    </div>
</div>

<?php if ($error != "") { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php } ?>

<?php if ($message != "") { ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php } ?>

<?php if (isset($_SESSION["user_id"]) && $_SESSION["role"] == "Admin") { ?>

<!-- summary cards -->
<div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Registered Users</div>
                    <div class="h4 mb-0"><?php echo $totalUsers; ?></div>
                    <div class="text-muted small">Includes <?php echo $countAdmins; ?> Admin(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Managers</div>
                    <div class="h4 mb-0"><?php echo $countManagers; ?></div>
                    <div class="text-muted small">Platform operations</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Freelancers</div>
                    <div class="h4 mb-0"><?php echo $countFreelancers; ?></div>
                    <div class="text-muted small">AI specialists</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Clients</div>
                    <div class="h4 mb-0"><?php echo $countClients; ?></div>
                    <div class="text-muted small">Individuals & organizations</div>
                </div>
            </div>
        </div>
    </div>
<!-- add new mnger section
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Create New Manager Account</h2>
            <form method="POST" action="admin_dashboard.php" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add_manager">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Full Name *</label>
                    <input type="text" name="manager_name" class="form-control form-control-sm" placeholder="e.g. Kasun Perera" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-1">Email Address *</label>
                    <input type="email" name="manager_email" class="form-control form-control-sm" placeholder="manager.kasun@aihub.lk" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Password *</label>
                    <input type="password" name="manager_password" class="form-control form-control-sm" placeholder="Min 6 chars" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark btn-sm w-100">Add Manager</button>
                </div>
            </form>
        </div>
    </div> -->

<!-- Section 1: User Account Management -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-3">User Accounts Management</h2>
            <form method="GET" action="admin_dashboard.php" class="d-flex gap-2">
                    <select name="role_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="All" <?php if ($roleFilter == "All") { echo "selected"; } ?>>All Roles (<?php echo $totalUsers; ?>)</option>
                        <option value="Admin" <?php if ($roleFilter == "Admin") { echo "selected"; } ?>>Admin (<?php echo $countAdmins; ?>)</option>
                        <option value="Manager" <?php if ($roleFilter == "Manager") { echo "selected"; } ?>>Manager (<?php echo $countManagers; ?>)</option>
                        <option value="Freelancer" <?php if ($roleFilter == "Freelancer") { echo "selected"; } ?>>Freelancer (<?php echo $countFreelancers; ?>)</option>
                        <option value="Client" <?php if ($roleFilter == "Client") { echo "selected"; } ?>>Client (<?php echo $countClients; ?>)</option>
                    </select>
                    <?php if ($roleFilter != "All") { ?>
                        <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php } ?>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $whereRole = "";
                        if ($roleFilter != "All") {
                            $safeRole = mysqli_real_escape_string($conn, $roleFilter);
                            $whereRole = "WHERE role = '$safeRole'";
                        }

                        $usersSql = "SELECT user_id, full_name, email, role, account_status
                                    FROM users
                                    $whereRole
                                    ORDER BY user_id ASC";
                        $usersRes = mysqli_query($conn, $usersSql);
                        if ($usersRes && mysqli_num_rows($usersRes) > 0) {
                            while ($uRow = mysqli_fetch_assoc($usersRes)) {
                                // Status badge styling
                                $badgeClass = "bg-secondary";
                                if ($uRow["account_status"] == "Active") {
                                    $badgeClass = "bg-success";
                                } elseif ($uRow["account_status"] == "Suspended") {
                                    $badgeClass = "bg-danger";
                                }
                        ?>
                            <tr>
                                <td><?php echo $uRow["user_id"]; ?></td>
                                <td><?php echo htmlspecialchars($uRow["full_name"]); ?></td>
                                <td><?php echo htmlspecialchars($uRow["email"]); ?></td>
                                <td><span class="badge bg-dark"><?php echo htmlspecialchars($uRow["role"]); ?></span></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($uRow["account_status"]); ?></span></td>
                                <td>
                                    <form method="POST" action="admin_dashboard.php" class="d-flex gap-2">
                                        <input type="hidden" name="action" value="update_user_status">
                                        <input type="hidden" name="user_id" value="<?php echo $uRow["user_id"]; ?>">
                                        <select name="account_status" class="form-select form-select-sm">
                                            <option value="Active" <?php if ($uRow["account_status"] == "Active") { echo "selected"; } ?>>Active</option>
                                            <option value="Suspended" <?php if ($uRow["account_status"] == "Suspended") { echo "selected"; } ?>>Suspended</option>
                                            <option value="Inactive" <?php if ($uRow["account_status"] == "Inactive") { echo "selected"; } ?>>Inactive</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<!-- add new manager -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
            role="button" data-bs-toggle="collapse" data-bs-target="#collapseAddManager"
            aria-expanded="<?php echo ($openSection == "add_manager") ? "true" : "false"; ?>">
            <span class="fw-semibold">Create New Manager Account</span>
            <span class="badge bg-dark">Expand</span>
        </div>
        <div id="collapseAddManager" class="collapse <?php if ($openSection == "add_manager") { echo "show"; } ?>">
            <div class="card-body border-top">
                <form method="POST" action="admin_dashboard.php" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="add_manager">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Full Name *</label>
                        <input type="text" name="manager_name" class="form-control form-control-sm" placeholder="e.g. Kasun Perera" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Email Address *</label>
                        <input type="email" name="manager_email" class="form-control form-control-sm" placeholder="manager.kasun@aihub.lk" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1">Password *</label>
                        <input type="password" name="manager_password" class="form-control form-control-sm" placeholder="Min 6 chars" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark btn-sm w-100">Add Manager</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Section 2: AI Skills Management -->
<div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
            role="button" data-bs-toggle="collapse" data-bs-target="#collapseAddSkill"
            aria-expanded="<?php echo ($openSection == "add_skill") ? "true" : "false"; ?>">
            <span class="fw-semibold">Add New AI Skill</span>
            <span class="badge bg-success">Expand</span>
        </div>
        <div id="collapseAddSkill" class="collapse <?php if ($openSection == "add_skill") { echo "show"; } ?>">
            <div class="card-body border-top">
                <form method="POST" action="admin_dashboard.php" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="add_skill">
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Skill Name *</label>
                        <input type="text" name="skill_name" class="form-control form-control-sm" placeholder="e.g. LangChain & Vector Search" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">AI Domain *</label>
                        <select name="ai_domain" class="form-select form-select-sm">
                            <option value="Machine Learning">Machine Learning</option>
                            <option value="Deep Learning">Deep Learning</option>
                            <option value="NLP">NLP</option>
                            <option value="Computer Vision">Computer Vision</option>
                            <option value="Generative AI">Generative AI</option>
                            <option value="Data Analytics">Data Analytics</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Short description">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success btn-sm w-100">Add Skill</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
            role="button" data-bs-toggle="collapse" data-bs-target="#collapseSkillsTable"
            aria-expanded="<?php echo ($openSection == "skills") ? "true" : "false"; ?>">
            <span class="fw-semibold">Existing AI Skills Catalog</span>
            <span class="badge bg-info text-dark"><?php echo $totalSkills; ?> Skills</span>
        </div>
        <div id="collapseSkillsTable" class="collapse <?php if ($openSection == "skills") { echo "show"; } ?>">
            <div class="card-body border-top">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Skill Name</th>
                                <th>Domain</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $skillsSql = "SELECT skill_id, skill_name, ai_domain, description
                                            FROM ai_skills
                                            ORDER BY skill_id ASC";
                            $skillsRes = mysqli_query($conn, $skillsSql);
                            if ($skillsRes && mysqli_num_rows($skillsRes) > 0) {
                                while ($sRow = mysqli_fetch_assoc($skillsRes)) {
                            ?>
                                <tr>
                                    <td><?php echo $sRow["skill_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($sRow["skill_name"]); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($sRow["ai_domain"]); ?></span></td>
                                    <td><?php echo htmlspecialchars($sRow["description"]); ?></td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr><td colspan="4" class="text-center text-muted">No AI skills found.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<!-- Section 3: Disputes Resolution -->
<div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
            role="button" data-bs-toggle="collapse" data-bs-target="#collapseDisputesTable"
            aria-expanded="<?php echo ($openSection == "disputes") ? "true" : "false"; ?>">
            <span class="fw-semibold">Disputes & Resolutions</span>
            <span class="badge bg-warning text-dark"><?php echo $totalDisputes; ?> Disputes</span>
        </div>
        <div id="collapseDisputesTable" class="collapse <?php if ($openSection == "disputes") { echo "show"; } ?>">
            <div class="card-body border-top">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <span class="text-muted small">Filter disputes by status or update resolution notes below.</span>
                    <form method="GET" action="admin_dashboard.php" class="d-flex gap-2">
                        <select name="dispute_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="All" <?php if ($disputeFilter == "All") { echo "selected"; } ?>>All Statuses</option>
                            <option value="Open" <?php if ($disputeFilter == "Open") { echo "selected"; } ?>>Open</option>
                            <option value="Under Review" <?php if ($disputeFilter == "Under Review") { echo "selected"; } ?>>Under Review</option>
                            <option value="Resolved" <?php if ($disputeFilter == "Resolved") { echo "selected"; } ?>>Resolved</option>
                        </select>
                        <?php if ($disputeFilter != "All") { ?>
                            <a href="admin_dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <?php } ?>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Dispute #</th>
                                <th>Assignment #</th>
                                <th>Raised By</th>
                                <th>Reason</th>
                                <th>Current Status</th>
                                <th>Update & Resolution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $whereDispute = "";
                            if ($disputeFilter != "All") {
                                $safeDisputeStatus = mysqli_real_escape_string($conn, $disputeFilter);
                                $whereDispute = "WHERE d.dispute_status = '$safeDisputeStatus'";
                            }

                            $dispSql = "SELECT d.dispute_id, d.assignment_id, u.full_name AS raiser_name,
                                            d.reason, d.resolution_notes, d.dispute_status
                                        FROM disputes d
                                        JOIN users u ON d.raised_by = u.user_id
                                        $whereDispute
                                        ORDER BY d.dispute_id DESC";
                            $dispRes = mysqli_query($conn, $dispSql);
                            if ($dispRes && mysqli_num_rows($dispRes) > 0) {
                                while ($dRow = mysqli_fetch_assoc($dispRes)) {
                                    $st = $dRow["dispute_status"];
                                    $dispBadge = "bg-secondary";
                                    $btnClass = "btn-dark";
                                    if ($st == "Open") {
                                        $dispBadge = "bg-danger";
                                        $btnClass = "btn-danger";
                                    } elseif ($st == "Under Review") {
                                        $dispBadge = "bg-warning text-dark";
                                        $btnClass = "btn-warning text-dark";
                                    } elseif ($st == "Resolved") {
                                        $dispBadge = "bg-success";
                                        $btnClass = "btn-success";
                                    }
                            ?>
                                <tr>
                                    <td><?php echo $dRow["dispute_id"]; ?></td>
                                    <td><?php echo $dRow["assignment_id"]; ?></td>
                                    <td><?php echo htmlspecialchars($dRow["raiser_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($dRow["reason"]); ?></td>
                                    <td>
                                        <span class="badge <?php echo $dispBadge; ?>"><?php echo htmlspecialchars($st); ?></span>
                                    </td>
                                    <td>
                                        <form method="POST" action="admin_dashboard.php" class="row g-2">
                                            <input type="hidden" name="action" value="resolve_dispute">
                                            <input type="hidden" name="dispute_id" value="<?php echo $dRow["dispute_id"]; ?>">
                                            <div class="col-md-4">
                                                <select name="dispute_status" class="form-select form-select-sm">
                                                    <option value="Open" <?php if ($st == "Open") { echo "selected"; } ?>>Open</option>
                                                    <option value="Under Review" <?php if ($st == "Under Review") { echo "selected"; } ?>>Under Review</option>
                                                    <option value="Resolved" <?php if ($st == "Resolved") { echo "selected"; } ?>>Resolved</option>
                                                </select>
                                            </div>
                                            <div class="col-md-5">
                                                <input type="text" name="resolution_notes" class="form-control form-control-sm"
                                                    value="<?php echo htmlspecialchars($dRow["resolution_notes"]); ?>"
                                                    placeholder="Resolution notes...">
                                            </div>
                                            <div class="col-md-3">
                                                <button type="submit" class="btn btn-sm <?php echo $btnClass; ?> w-100">Update</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr><td colspan="6" class="text-center text-muted">No disputes found for this status.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<?php
include "../includes/footer.php"; 
?>