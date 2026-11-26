<?php
include "config/db.php";
include "includes/header.php";



// Get counts from tables
// $resUsers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
// $rowUsers = mysqli_fetch_assoc($resUsers);
// $totalUsers = $rowUsers["total"];

// $resProjects = mysqli_query($conn, "SELECT COUNT(*) AS total FROM projects");
// $rowProjects = mysqli_fetch_assoc($resProjects);
// $totalProjects = $rowProjects["total"];

// $resProposals = mysqli_query($conn, "SELECT COUNT(*) AS total FROM proposals");
// $rowProposals = mysqli_fetch_assoc($resProposals);
// $totalProposals = $rowProposals["total"];

// $resPayments = mysqli_query($conn, "SELECT COUNT(*) AS total FROM payments");
// $rowPayments = mysqli_fetch_assoc($resPayments);
// $totalPayments = $rowPayments["total"];

// // Get recent projects using View
// $resRecent = mysqli_query($conn, "SELECT * FROM vw_ProjectDetails LIMIT 6");

// Get counts from tables
$totalUsers = 0;
$resUsers = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($resUsers && ($row = mysqli_fetch_assoc($resUsers))) {
    $totalUsers = (int) $row["total"];
}

$totalProjects = 0;
$resProjects = mysqli_query($conn, "SELECT COUNT(*) AS total FROM projects");
if ($resProjects && ($row = mysqli_fetch_assoc($resProjects))) {
    $totalProjects = (int) $row["total"];
}

$totalServices = 0;
$resServices = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ai_services WHERE is_active = 1");
if ($resServices && ($row = mysqli_fetch_assoc($resServices))) {
    $totalServices = (int) $row["total"];
}

$totalProposals = 0;
$resProposals = mysqli_query($conn, "SELECT COUNT(*) AS total FROM proposals");
if ($resProposals && ($row = mysqli_fetch_assoc($resProposals))) {
    $totalProposals = (int) $row["total"];
}

// get top 3 freelancers ranked using fn_FreelancerAvgRating
$topFreelancers = array();
$topFreeSql = "SELECT u.user_id, u.full_name, f.hourly_rate, f.experience_years, f.availability_status,
                    fn_FreelancerAvgRating(u.user_id) AS avg_rating
                FROM freelancers f
                JOIN users u ON f.freelancer_id = u.user_id
                WHERE u.account_status = 'Active'
                ORDER BY avg_rating DESC, f.experience_years DESC
                LIMIT 3";
$topFreeRes = mysqli_query($conn, $topFreeSql);
if ($topFreeRes && mysqli_num_rows($topFreeRes) > 0) {
    while ($fRow = mysqli_fetch_assoc($topFreeRes)) {
        $fid = (int) $fRow["user_id"];
        $skillList = array();
        $skRes = mysqli_query($conn, "SELECT s.skill_name
                                        FROM freelancer_skills fs
                                        JOIN skills s ON fs.skill_id = s.skill_id
                                        WHERE fs.freelancer_id = $fid
                                        LIMIT 3");
        if ($skRes) {
            while ($sk = mysqli_fetch_assoc($skRes)) {
                $skillList[] = $sk["skill_name"];
            }
        }
        $fRow["skills"] = $skillList;
        $topFreelancers[] = $fRow;
    }
}

// get recent projects using vw_ProjectDetails
$openProjects = array();
$resRecent = mysqli_query($conn, "SELECT * FROM vw_ProjectDetails WHERE project_status = 'Open' ORDER BY project_id DESC LIMIT 6");
if (!$resRecent || mysqli_num_rows($resRecent) == 0) {
    // get latest projects if none are open
    $resRecent = mysqli_query($conn, "SELECT * FROM vw_ProjectDetails ORDER BY project_id DESC LIMIT 6");
}
if ($resRecent) {
    while ($pRow = mysqli_fetch_assoc($resRecent)) {
        $openProjects[] = $pRow;
    }
}

// get highest-rated reviews
$bestReviews = array();
$revSql = "SELECT r.review_id, r.rating, r.comment, r.reviewed_at,
                u1.full_name AS client_name,
                u2.full_name AS freelancer_name
        FROM reviews r
        JOIN users u1 ON r.reviewer_id = u1.user_id
        JOIN users u2 ON r.reviewee_id = u2.user_id
        WHERE u1.role = 'Client'
            AND r.comment IS NOT NULL
            AND TRIM(r.comment) != ''
        ORDER BY r.rating DESC, r.reviewed_at DESC
        LIMIT 6";
$revRes = mysqli_query($conn, $revSql);
if ($revRes && mysqli_num_rows($revRes) > 0) {
    while ($rv = mysqli_fetch_assoc($revRes)) {
        $bestReviews[] = $rv;
    }
}

?>

<!-- hero section -->
<div class="rounded-3 overflow-hidden shadow-sm mb-5 position-relative text-white"
    style="background: linear-gradient(rgba(15, 23, 42, 0.78), rgba(120, 126, 142, 0.85)), url('assets/images/hero-banner.jpg') center/cover no-repeat; background-color: #0f172a;">
    <div class="p-4 p-md-5">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-6 fw-bold mb-3">Hire AI engineers &amp; data specialists for real production work.</h1>
                <p class="lead text-light opacity-75 mb-4">
                    Post custom machine learning, NLP, computer vision, or automation projects—or book fixed-price AI services directly from experienced freelancers.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <?php if (isset($_SESSION["user_id"])) { ?>
                        <a href="projects.php" class="btn btn-primary btn-lg px-4">Explore Projects</a>
                        <a href="services.php" class="btn btn-outline-light btn-lg px-4">Browse AI Services</a>
                    <?php } else { ?>
                        <a href="register.php" class="btn btn-primary btn-lg px-4">Get Started</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4">Sign In</a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- summary inside hero -->
        <div class="row g-3 mt-4 pt-4 border-top border-secondary border-opacity-50">
            <div class="col-6 col-md-3">
                <div class="h3 fw-bold mb-0"><?php echo $totalUsers; ?>+</div>
                <div class="small text-light opacity-75">Registered Members</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="h3 fw-bold mb-0"><?php echo $totalProjects; ?></div>
                <div class="small text-light opacity-75">AI Projects Posted</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="h3 fw-bold mb-0"><?php echo $totalServices; ?></div>
                <div class="small text-light opacity-75">Active AI Services</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="h3 fw-bold mb-0"><?php echo $totalProposals; ?></div>
                <div class="small text-light opacity-75">Proposals Submitted</div>
            </div>
        </div>
    </div>
</div>

<!-- <div class="mb-4">
    <h1 class="h3 mb-1">AI Projects Freelancing Platform</h1>
    <p class="text-muted mb-0">Overview connected of ai_freelance_platform DB</p>
</div> -->

<!-- <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Registered Users</div>
                
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">AI Projects</div>
                
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Proposals</div>
                
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Payment Records</div>
                
            </div>
        </div>
    </div>
</div> -->

<!-- <div class="card border-0 shadow-sm">
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

                    <tr>

                    </tr>

            </tbody>
        </table>
    </div>
</div> -->

<!-- top-rated freelancers section -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Top-Rated AI Freelancers</h2>
            <p class="text-muted small mb-0">Ranked by verified client review ratings</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (count($topFreelancers) > 0) { ?>
            <?php foreach ($topFreelancers as $tf) {
                $name = (string) $tf["full_name"];
                $initials = strtoupper(substr($name, 0, 1));
                $ratingDisplay = ($tf["avg_rating"] !== null)
                    ? number_format((float) $tf["avg_rating"], 1) . " / 5.0"
                    : "New";
                $avail = (string) ($tf["availability_status"] ?? "Available");
                $availClass = (strcasecmp($avail, "Available") == 0) ? "bg-success-subtle text-success border border-success-subtle" : "bg-secondary-subtle text-secondary";
            ?>
                <div class="col-md-4">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold"
                                    style="width: 48px; height: 48px; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>
                                <div>
                                    <h3 class="h6 fw-bold mb-0"><?php echo htmlspecialchars($name); ?> 	&#10070;</h3>
                                    <div class="small text-muted"><?php echo (int) $tf["experience_years"]; ?>+ yrs experience</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                <div>
                                    <span class="fw-semibold text-dark">&#9733; <?php echo htmlspecialchars($ratingDisplay); ?></span>
                                </div>
                                <div class="fw-bold text-primary">
                                    $<?php echo number_format((float) $tf["hourly_rate"], 2); ?><span class="text-muted fw-normal small">/hr</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <?php if (count($tf["skills"]) > 0) { ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($tf["skills"] as $skName) { ?>
                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($skName); ?></span>
                                        <?php } ?>
                                    </div>
                                <?php } else { ?>
                                    <span class="text-muted small">AI &amp; Data Specialist</span>
                                <?php } ?>
                            </div>

                            <div class="mt-auto d-flex justify-content-between align-items-center pt-2">
                                <span class="badge <?php echo $availClass; ?>"><?php echo htmlspecialchars($avail); ?></span>
                                <a href="freelancers.php" class="btn btn-sm btn-outline-dark">View Profile</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="alert alert-light border text-muted mb-0">No active freelancers available yet.</div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- open projects cards -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Current Open Projects</h2>
            <p class="text-muted small mb-0">Latest client opportunities ready for proposals</p>
        </div>
        <a href="projects.php" class="btn btn-sm btn-outline-primary">View All Projects</a>
    </div>

    <div class="row g-3">
        <?php if (count($openProjects) > 0) { ?>
            <?php foreach ($openProjects as $proj) {
                $pTitle = $proj["title"] ?? $proj["project_title"] ?? "Untitled Project";
                $pClient = $proj["client_name"] ?? "Client";
                $pStatus = $proj["project_status"] ?? "Open";
                $statusBadge = ($pStatus == "Open") ? "bg-success" : "bg-secondary";
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($pStatus); ?></span>
                                <span class="fw-bold text-dark">$<?php echo number_format((float) $proj["budget"], 2); ?></span>
                            </div>
                            <h3 class="h6 fw-bold mb-1"><?php echo htmlspecialchars($pTitle); ?></h3>
                            <p class="text-muted small mb-3">Posted by <?php echo htmlspecialchars($pClient); ?></p>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center small text-muted">
                                <span>Deadline: <?php echo htmlspecialchars((string) $proj["deadline"]); ?></span>
                                <a href="projects.php" class="text-decoration-none fw-medium">Details &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="col-12">
                <div class="alert alert-light border text-muted mb-0">No projects posted yet.</div>
            </div>
        <?php } ?>
    </div>
</div>

<!-- best reviews slider section -->
<div class="mb-5">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="h4 fw-bold mb-1">Client &amp; Project Reviews</h2>
            <p class="text-muted small mb-0">Top ratings from completed work on aifreelance.com</p>
        </div>
    </div>

    <?php if (count($bestReviews) > 0) { ?>
        <div id="bestReviewsCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
            <div class="carousel-inner">
                <?php foreach ($bestReviews as $idx => $rv) {
                    $starsCount = max(1, min(5, (int) $rv["rating"]));
                    $starsText = str_repeat("&#9733;", $starsCount);
                    $clientName = !empty($rv["client_name"]) ? $rv["client_name"] : "Client";
                    $freelancerName = !empty($rv["freelancer_name"]) ? $rv["freelancer_name"] : "Freelancer";
                ?>
                    <div class="carousel-item <?php echo ($idx === 0) ? "active" : ""; ?>">
                        <div class="card border shadow-sm">
                            <div class="card-body p-4 p-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-warning fs-5" aria-label="<?php echo $starsCount; ?> out of 5 stars"><?php echo $starsText; ?></span>
                                    <span class="badge bg-light text-dark border"><?php echo $starsCount; ?>.0 / 5.0</span>
                                </div>
                                <blockquote class="blockquote mb-3">
                                    <p class="mb-0 fst-italic">&ldquo;<?php echo htmlspecialchars((string) $rv["comment"]); ?>&rdquo;</p>
                                </blockquote>
                                <div class="small text-muted">
                                    <span class="fw-medium text-dark">&mdash; <?php echo htmlspecialchars($clientName); ?></span>
                                    <span> reviewed freelancer <strong><?php echo htmlspecialchars($freelancerName); ?></strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <!-- using slide changing button -->
            <?php if (count($bestReviews) > 1) { ?>
                <button class="carousel-control-prev" type="button" data-bs-target="#bestReviewsCarousel" data-bs-slide="prev" style="width: 3rem;">
                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#bestReviewsCarousel" data-bs-slide="next" style="width: 3rem;">
                    <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-light border text-muted mb-0">No reviews recorded yet.</div>
    <?php } ?>
</div>


<!-- About section -->
<section class="border-top mt-5 pt-5 pb-4">
    <div class="text-center mx-auto mb-4 pb-4 border-bottom">
        <h2 class="h4 fw-bold text-dark mb-2">About AIfreelance.com</h2>
        <p class="text-muted mb-0" style="line-height: 1.75;">
            AIfreelance.com is a focused marketplace built for real AI and data engineering collaboration. 
            <br>Organizations and independent clients can publish scoped projects, compare proposals from verified freelancers, and 
            <br>manage milestones, reviews, and payouts in one place.
        </p>
    </div>

    <div class="row g-4 justify-content-center text-center">
        <div class="col-6 col-md-4">
            <h3 class="h6 fw-semibold text-uppercase text-dark mb-3" style="letter-spacing: 0.04em;">Explore</h3>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                <li><a href="projects.php" class="text-muted text-decoration-none">Open Projects</a></li>
                <li><a href="services.php" class="text-muted text-decoration-none">AI Services</a></li>
                <li><a href="freelancers.php" class="text-muted text-decoration-none">Top Freelancers</a></li>
            </ul>
        </div>

        <div class="col-6 col-md-4">
            <h3 class="h6 fw-semibold text-uppercase text-dark mb-3" style="letter-spacing: 0.04em;">Account</h3>
            <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                <?php if (isset($_SESSION["user_id"])) { ?>
                    <li><a href="projects.php" class="text-muted text-decoration-none">Post or Manage Projects</a></li>
                    <li><a href="services.php" class="text-muted text-decoration-none">Browse Service Packages</a></li>
                <?php } else { ?>
                    <li><a href="register.php" class="text-muted text-decoration-none">Create an Account</a></li>
                    <li><a href="login.php" class="text-muted text-decoration-none">Sign In</a></li>
                <?php } ?>
            </ul>
        </div>

        <div class="col-12 col-md-4">
            <h3 class="h6 fw-semibold text-uppercase text-dark mb-3" style="letter-spacing: 0.04em;">Contact</h3>
            <ul class="list-unstyled text-muted mb-0 d-flex flex-column gap-2">
                <li><a href="mailto:support@aifreelance.com" class="text-muted text-decoration-none">support@aifreelance.com</a></li>
                <li>Colombo, Sri Lanka</li>
                <li>Mon &ndash; Fri, 9:00 AM &ndash; 6:00 PM</li>
            </ul>
        </div>
    </div>
</section>
<?php
include "includes/footer.php";
?>