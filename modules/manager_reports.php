<?php
// echo "Manager Reports Page";
$basePath = "../";
include "../config/db.php";
include "../includes/header.php";

$error = "";

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] != "Manager" && $_SESSION["role"] != "Admin")) {
    $error = "Access denied. Please log in with a Manager or Admin account to view reports.";
}

// Summary counters
$totalProjects = 0;
$totalAssignments = 0;
$totalServices = 0;
$openDisputes = 0;
$grossPayments = 0.00;
$totalFees = 0.00;
$netPayouts = 0.00;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Manager Reports & Analytics</h1>
        <p class="text-muted mb-0">Platform Overview</p>
    </div>
</div>

<?php if ($error != "") { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php } else { ?>

    //Activity Cards 
    <div class="row g-3 mb-4">
        <h3 class="h5 mb-1">Platform Activities</h3>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Projects</div>
                    <div class="h4 mb-0"><?php echo $totalProjects; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Active AI Services</div>
                    <div class="h4 mb-0"><?php echo $totalServices; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Project Assignments</div>
                    <div class="h4 mb-0"><?php echo $totalAssignments; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Open / Pending Disputes</div>
                    <div class="h4 mb-0"><?php echo $openDisputes; ?></div>
                </div>
            </div>
        </div>
    </div>

    //Revenue & Payout summary cards
    <div class="row g-3 mb-4">
        <h3 class="h5 mb-1">Revenue & Payout Summary</h3>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Completed Gross Payments</div>
                    <div class="h4 mb-0">$<?php echo number_format($grossPayments, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Platform Fees Collected</div>
                    <div class="h4 mb-0">$<?php echo number_format($totalFees, 2); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Net Freelancer Payouts</div>
                    <div class="h4 mb-0">$<?php echo number_format($netPayouts, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

<?php } ?>

<?php
include "../includes/footer.php";
?>