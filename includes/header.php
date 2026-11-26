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

        <?php if ($currentRole === "Freelancer") { ?>
            <a href="<?php echo htmlspecialchars($basePath, ENT_QUOTES, "UTF-8"); ?>modules/freelancer_profile.php?id=<?php echo (int) ($_SESSION["user_id"] ?? 0); ?>" class="btn btn-outline-info btn-sm">
                My Profile
            </a>
        <?php } ?>

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