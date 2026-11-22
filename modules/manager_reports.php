<?php
// echo "Manager Reports Page";
$basePath = "../";
include "../config/db.php";
include "../includes/header.php";

global $conn;
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

$minBudgetFilter = "";
if (isset($_GET["min_budget"]) && trim($_GET["min_budget"]) != "") {
    $minBudgetFilter = (float) $_GET["min_budget"];
}

if ($error == "") {
    // get activity counts
    $r1 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM projects");
    if ($r1) { $row1 = mysqli_fetch_assoc($r1); 
    $totalProjects = (int) $row1["cnt"]; 
    }

    $r2 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM project_assignments");
    if ($r2) { $row2 = mysqli_fetch_assoc($r2); 
    $totalAssignments = (int) $row2["cnt"]; 
    }

    $r3 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM ai_services WHERE is_active = 1");
    if ($r3) { $row3 = mysqli_fetch_assoc($r3); 
    $totalServices = (int) $row3["cnt"]; 
    }

    $r4 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM disputes WHERE dispute_status != 'Resolved'");
    if ($r4) { $row4 = mysqli_fetch_assoc($r4); 
    $openDisputes = (int) $row4["cnt"]; 
    }
    // Financial summary using udf fn_CalculateNetPayout
    $paySumSql = "SELECT
                    COALESCE(SUM(amount), 0) AS gross_amt,
                    COALESCE(SUM(platform_fee), 0) AS fee_amt,
                    COALESCE(SUM(fn_CalculateNetPayout(amount, platform_fee)), 0) AS net_amt
                    FROM payments
                    WHERE payment_status = 'Completed'";
    $r5 = mysqli_query($conn, $paySumSql);
    if ($r5) {
        $row5 = mysqli_fetch_assoc($r5);
        $grossPayments = (float) $row5["gross_amt"];
        $totalFees = (float) $row5["fee_amt"];
        $netPayouts = (float) $row5["net_amt"];
    }

    }

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

    <!-- Activity Cards  -->
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

    <!-- Revenue & Payout summary cards -->
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

    <!-- Projects & Budget Report (vw_ProjectDetails + sp_GetProjectsByMinBudget) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Projects & Budget Report</h2>
                <form method="GET" action="manager_reports.php" class="d-flex gap-2">
                    <input type="number" step="0.01" name="min_budget" class="form-control form-control-sm"
                            value="<?php echo htmlspecialchars($minBudgetFilter); ?>"
                            placeholder="Min budget ($)...">
                    <button type="submit" class="btn btn-sm btn-dark">Filter</button>
                    <?php if ($minBudgetFilter !== "") { ?>
                        <a href="manager_reports.php" class="btn btn-sm btn-outline-secondary">Reset</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Project Title</th>
                            <th>Client</th>
                            <th>Budget ($)</th>
                            <th>Status</th>
                            <th>Deadline</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $projRows = array();

                        if ($minBudgetFilter !== "") {
                            // Call stored procedure sp_GetProjectsByMinBudget
                            $spRes = mysqli_query($conn, "CALL sp_GetProjectsByMinBudget($minBudgetFilter)");
                            if ($spRes) {
                                while ($r = mysqli_fetch_assoc($spRes)) {
                                    $projRows[] = $r;
                                }
                                mysqli_free_result($spRes);
                                while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
                                    $extra = mysqli_use_result($conn);
                                    if ($extra) { mysqli_free_result($extra); }
                                }
                            }
                        } else {
                            // query vw_ProjectDetails for default values
                            $vwRes = mysqli_query($conn, "SELECT * FROM vw_ProjectDetails ORDER BY project_id DESC");
                            if ($vwRes) {
                                while ($r = mysqli_fetch_assoc($vwRes)) {
                                    $projRows[] = $r;
                                }
                            }
                        }

                        if (count($projRows) > 0) {
                            foreach ($projRows as $pRow) {
                                $pId       = $pRow["project_id"] ?? "-";
                                $pTitle    = $pRow["title"] ?? $pRow["project_title"] ?? "-";
                                // $pClient   = $pRow["client_name"] ?? "-";
                                $pBudget   = (float)($pRow["budget"] ?? 0);
                                $pStatus   = $pRow["project_status"] ?? "-";
                                $pDeadline = $pRow["deadline"] ?? "-";

                                $pClient = $pRow["client_name"] ?? "";
                                if ($pClient == "") {
                                    if (isset($pRow["client_id"]) && (int)$pRow["client_id"] > 0) {
                                        $cid = (int)$pRow["client_id"];
                                        $cRes = mysqli_query($conn, "SELECT u.full_name FROM users u WHERE u.user_id = $cid LIMIT 1");
                                        if ($cRes && mysqli_num_rows($cRes) == 1) {
                                            $cRow = mysqli_fetch_assoc($cRes);
                                            $pClient = $cRow["full_name"];
                                        }
                                    } elseif ((int)$pId > 0) {
                                        $pidInt = (int)$pId;
                                        $cRes = mysqli_query($conn, "SELECT u.full_name
                                                                    FROM projects p
                                                                    JOIN users u ON p.client_id = u.user_id
                                                                    WHERE p.project_id = $pidInt
                                                                    LIMIT 1");
                                        if ($cRes && mysqli_num_rows($cRes) == 1) {
                                            $cRow = mysqli_fetch_assoc($cRes);
                                            $pClient = $cRow["full_name"];
                                        }
                                    }
                                }
                                if ($pClient == "") {
                                    $pClient = "-";
                                }

                        ?>
                            <tr>
                                <td><?php echo $pId; ?></td>
                                <td><?php echo htmlspecialchars($pTitle); ?></td>
                                <td><?php echo htmlspecialchars($pClient); ?></td>
                                <td>$<?php echo number_format($pBudget, 2); ?></td>
                                <td><?php echo htmlspecialchars($pStatus); ?></td>
                                <td><?php echo htmlspecialchars($pDeadline); ?></td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr><td colspan="6" class="text-center text-muted">No projects found matching this criteria.</td></tr>
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