<?php
include "config/db.php";
include "includes/header.php";

// Get counts from tables
$resUsers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$rowUsers = mysqli_fetch_assoc($resUsers);
$totalUsers = $rowUsers["total"];

$resProjects = mysqli_query($conn, "SELECT COUNT(*) AS total FROM projects");
$rowProjects = mysqli_fetch_assoc($resProjects);
$totalProjects = $rowProjects["total"];

$resProposals = mysqli_query($conn, "SELECT COUNT(*) AS total FROM proposals");
$rowProposals = mysqli_fetch_assoc($resProposals);
$totalProposals = $rowProposals["total"];

$resPayments = mysqli_query($conn, "SELECT COUNT(*) AS total FROM payments");
$rowPayments = mysqli_fetch_assoc($resPayments);
$totalPayments = $rowPayments["total"];

// Get recent projects using View
$resRecent = mysqli_query($conn, "SELECT * FROM vw_ProjectDetails LIMIT 6");
?>

<div class="mb-4">
    <h1 class="h3 mb-1">AI Projects Freelancing Platform</h1>
    <p class="text-muted mb-0">Overview connected of ai_freelance_platform DB</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Registered Users</div>
                <div class="h2 fw-bold mt-1 mb-0"><?php echo $totalUsers; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">AI Projects</div>
                <div class="h2 fw-bold mt-1 mb-0"><?php echo $totalProjects; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Proposals</div>
                <div class="h2 fw-bold mt-1 mb-0"><?php echo $totalProposals; ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Payment Records</div>
                <div class="h2 fw-bold mt-1 mb-0"><?php echo $totalPayments; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h6 mb-0">Recent Projects</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Project Title</th>
                    <th>Client</th>
                    <th>Budget</th>
                    <th>Deadline</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($proj = mysqli_fetch_assoc($resRecent)) { ?>
                    <tr>
                        <td>#<?php echo $proj["project_id"]; ?></td>
                        <td class="fw-medium"><?php echo $proj["title"]; ?></td>
                        <td><?php echo $proj["client_name"]; ?></td>
                        <td>$<?php echo $proj["budget"], 2; ?></td>
                        <td><?php echo $proj["deadline"]; ?></td>
                        <td><span class="badge bg-secondary"><?php echo $proj["project_status"]; ?></span></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include "includes/footer.php";
?>