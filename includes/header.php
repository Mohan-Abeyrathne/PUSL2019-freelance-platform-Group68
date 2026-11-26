<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($basePath)) {
    $basePath = "";
}

$isLoggedIn = isset($_SESSION["user_id"]);
$currentRole = $isLoggedIn ? (string) ($_SESSION["role"] ?? "Guest") : "Guest";
$currentName = $isLoggedIn ? (string) ($_SESSION["full_name"] ?? "User") : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Freelance Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>index.php">AI Freelance.com</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>index.php">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/freelancers.php">Freelancers &amp; AI Skills</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/projects.php">Projects</a>
                </li>

                <?php if ($currentRole === "Freelancer" || $currentRole === "Client") { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/proposals.php">Proposals &amp; Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/payments_reviews.php">Payments &amp; Reviews</a>
                    </li>
                <?php } ?>

                <?php if ($currentRole === "Manager" || $currentRole === "Admin") { ?>
                    <li class="nav-item">
                        <a class="nav-link text-info" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/manager_reports.php">Manager Reports</a>
                    </li>
                <?php } ?>

                <?php if ($currentRole === "Admin") { ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/admin_dashboard.php">Admin Panel</a>
                    </li>
                <?php } ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($isLoggedIn) { ?>
                    <span class="text-light small">
                        <?php echo htmlspecialchars($currentName, ENT_QUOTES, "UTF-8"); ?>
                        (<?php echo htmlspecialchars($currentRole, ENT_QUOTES, "UTF-8"); ?>)
                    </span>
                    <a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                <?php } else { ?>
                    <a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>register.php" class="btn btn-primary btn-sm">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>
<main class="container my-4 flex-grow-1">
