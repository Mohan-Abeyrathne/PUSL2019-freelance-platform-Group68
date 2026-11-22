<?php

//echo "admin dashboard";
$basePath = "../";
include "../config/db.php";
include "../includes/header.php";

global $conn;
$message = "";
$error = "";

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

<!-- Section 1: User Account Management -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">User Accounts Management</h2>
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
                        $usersSql = "SELECT user_id, full_name, email, role, account_status
                                        FROM users
                                        ORDER BY user_id ASC";
                        $usersRes = mysqli_query($conn, $usersSql);
                        if ($usersRes && mysqli_num_rows($usersRes) > 0) {
                            while ($uRow = mysqli_fetch_assoc($usersRes)) {
                        ?>
                            <tr>
                                <td><?php echo $uRow["user_id"]; ?></td>
                                <td><?php echo htmlspecialchars($uRow["full_name"]); ?></td>
                                <td><?php echo htmlspecialchars($uRow["email"]); ?></td>
                                <td><?php echo htmlspecialchars($uRow["role"]); ?></td>
                                <td><?php echo htmlspecialchars($uRow["account_status"]); ?></td>
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

<!-- Section 2: AI Skills Management -->
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Add AI Skill</h2>
                    <form method="POST" action="admin_dashboard.php">
                        <input type="hidden" name="action" value="add_skill">
                        <div class="mb-3">
                            <label class="form-label">Skill Name *</label>
                            <input type="text" name="skill_name" class="form-control" placeholder="e.g. LangChain & Vector Search" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">AI Domain *</label>
                            <select name="ai_domain" class="form-select">
                                <option value="Machine Learning">Machine Learning</option>
                                <option value="Deep Learning">Deep Learning</option>
                                <option value="NLP">NLP</option>
                                <option value="Computer Vision">Computer Vision</option>
                                <option value="Generative AI">Generative AI</option>
                                <option value="Data Analytics">Data Analytics</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" placeholder="Short description of this skill">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Add AI Skill</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Existing AI Skills</h2>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
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
                                        <td><?php echo htmlspecialchars($sRow["ai_domain"]); ?></td>
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
    </div>

<!-- Section 3: Disputes Resolution -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Disputes & Resolutions</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Dispute #</th>
                            <th>Assignment #</th>
                            <th>Raised By</th>
                            <th>Reason</th>
                            <th>Status & Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $dispSql = "SELECT d.dispute_id, d.assignment_id, u.full_name AS raiser_name,
                                            d.reason, d.resolution_notes, d.dispute_status
                                    FROM disputes d
                                    JOIN users u ON d.raised_by = u.user_id
                                    ORDER BY d.dispute_id DESC";
                        $dispRes = mysqli_query($conn, $dispSql);
                        if ($dispRes && mysqli_num_rows($dispRes) > 0) {
                            while ($dRow = mysqli_fetch_assoc($dispRes)) {
                        ?>
                            <tr>
                                <td><?php echo $dRow["dispute_id"]; ?></td>
                                <td><?php echo $dRow["assignment_id"]; ?></td>
                                <td><?php echo htmlspecialchars($dRow["raiser_name"]); ?></td>
                                <td><?php echo htmlspecialchars($dRow["reason"]); ?></td>
                                <td>
                                    <form method="POST" action="admin_dashboard.php" class="row g-2">
                                        <input type="hidden" name="action" value="resolve_dispute">
                                        <input type="hidden" name="dispute_id" value="<?php echo $dRow["dispute_id"]; ?>">
                                        <div class="col-md-4">
                                            <select name="dispute_status" class="form-select form-select-sm">
                                                <option value="Open" <?php if ($dRow["dispute_status"] == "Open") { echo "selected"; } ?>>Open</option>
                                                <option value="Under Review" <?php if ($dRow["dispute_status"] == "Under Review") { echo "selected"; } ?>>Under Review</option>
                                                <option value="Resolved" <?php if ($dRow["dispute_status"] == "Resolved") { echo "selected"; } ?>>Resolved</option>
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" name="resolution_notes" class="form-control form-control-sm"
                                                value="<?php echo htmlspecialchars($dRow["resolution_notes"]); ?>"
                                                placeholder="Resolution notes...">
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-sm btn-dark w-100">Update</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr><td colspan="5" class="text-center text-muted">No disputes recorded.</td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php } ?>

<?php
include "../includes/footer.php"; 
?>