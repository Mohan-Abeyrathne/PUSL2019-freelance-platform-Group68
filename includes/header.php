<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//variable for manage paths
if (!isset($basePath)) {
    $basePath = "";
}

$isLoggedIn = isset($_SESSION["user_id"]);
$currentRole = $isLoggedIn ? $_SESSION["role"] : "Guest";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Freelance Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
        position: relative;
        background-color: #f8fafca4;
        color: #0f172a;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        z-index: 0;
    }
    body::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image: url('/PUSL2019-freelance-platform/assets/images/page-bg2.jpg');
        background-position: center top;
        background-repeat: no-repeat;
        background-size: cover;
        filter: blur(1.5px);
        pointer-events: none;
        z-index: -2;
    }
    body::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(
            rgba(248, 250, 252, 0.68),
            rgba(241, 245, 249, 0.78)
        );
        pointer-events: none;
        z-index: -1;
    }

    main, .container:first-of-type {
        flex: 1;
    }

/* cards and tables */
.card, .table-responsive {
    background-color: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(10px);
    border-color: rgb(255, 255, 255);
    border: 1px solid rgba(0, 0, 0, 0.14) !important;
}

/* glass effect for Client & Project Review cards */
.glass-card,
#bestReviewsCarousel .card {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0),
        rgba(248, 250, 252, 0)
    );
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgb(255, 255, 255) !important;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.3);
}

/* glass effect for the About & Contact section */
section.border-top {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0),
        rgba(248, 250, 252, 0)
    );
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-radius: 0.75rem;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
}

    /* .card, .table-responsive {
        background-color: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(6px);
        border-color: rgba(148, 163, 184, 0.25);
    }
    section.border-top {
        background-color: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(6px);
        border-radius: 0.75rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
    } */

</style>

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
                <!-- <li class="nav-item"><a class="nav-link" href="<?php //echo $basePath; ?>index.php">Overview</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php //echo $basePath; ?>freelancers.php">Freelancers & AI Skills</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php //echo $basePath; ?>projects.php">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php //echo $basePath; ?>proposals.php">Proposals & Assignments</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php //echo $basePath; ?>payments_reports.php">Payments & Reports</a></li>
                    <li class="nav-item"><a class="nav-link text-warning" href="modules/admin_dashboard.php">Admin Panel</a></li> -->

                <!--everyone -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>index.php">Overview</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>modules/freelancers.php">Freelancers & AI Skills</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>modules/projects.php">Projects</a>
                </li>

                <!--Freelancer and Client -->
                <?php if ($currentRole == "Freelancer" || $currentRole == "Client") { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>modules/proposals.php">Proposals & Assignments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $basePath; ?>modules/payments_reviews.php">Payments & Reviews</a>
                    </li>
                <?php } ?>

                <!-- Manager and Admin -->
                <?php if ($currentRole == "Manager" || $currentRole == "Admin") { ?>
                    <li class="nav-item">
                        <a class="nav-link text-info" href="<?php echo $basePath; ?>modules/manager_reports.php">Manager Reports</a>
                    </li>
                <?php } ?>

                <!--Admin only -->
                <?php if ($currentRole == "Admin") { ?>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="<?php echo $basePath; ?>modules/admin_dashboard.php">Admin Panel</a>
                    </li>
                <?php } ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php //if (isset($_SESSION["user_id"])) { ?>
                <?php if ($isLoggedIn) { ?>
                    <span class="text-light small">
                        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                        <?php //echo htmlspecialchars($_SESSION["role"]); ?>

                        (<?php echo htmlspecialchars($currentRole); ?>)
                    </span>
                    <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
                <?php } else { ?>
                    <a href="<?php echo $basePath; ?>login.php" class="btn btn-outline-light btn-sm">Login</a>
                    <a href="<?php echo $basePath; ?>register.php" class="btn btn-primary btn-sm">Register</a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>
<main class="container my-4 flex-grow-1">