<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//variable for manage paths
if (!isset($basePath)) {
    $basePath = "";
}
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
        <a class="navbar-brand fw-semibold" href="<?php echo $basePath; ?>index.php">AI Freelance.com</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>index.php">Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>freelancers.php">Freelancers & AI Skills</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>projects.php">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>proposals.php">Proposals & Assignments</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $basePath; ?>payments_reports.php">Payments & Reports</a></li>
                <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == "Admin") { ?>
                    <li class="nav-item"><a class="nav-link text-warning" href="<?php echo $basePath; ?>modules/admin_dashboard.php">Admin Panel</a></li>
                <?php } ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isset($_SESSION["user_id"])) { ?>
                    <span class="text-light small">
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                        (<?php echo htmlspecialchars($_SESSION["role"]); ?>)
                    </span>
                    <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                <?php } else { ?>
                    <a href="<?php echo $basePath; ?>login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="<?php echo $basePath; ?>register.php" class="btn btn-warning btn-sm">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>
<main class="container my-4 flex-grow-1">