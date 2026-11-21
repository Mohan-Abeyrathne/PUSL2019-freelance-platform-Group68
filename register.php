<?php
include "config/db.php";
include "includes/header.php";

$message = "";
$error = "";
$selectedRole = "Freelancer";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, trim($_POST["full_name"]));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $phone = mysqli_real_escape_string($conn, trim($_POST["phone"]));
    $role = mysqli_real_escape_string($conn, $_POST["role"]);
    $password = $_POST["password"];

    // Only allow Freelancer or Client
    if ($role != "Freelancer" && $role != "Client") {
        $role = "Freelancer";
    }
    $selectedRole = $role;

    if ($full_name == "" || $email == "" || $password == "") {
        $error = "Please fill in your full name, email, and password.";
    } else {
        // Check if email already exist
        $checkSql = "SELECT user_id FROM users WHERE email = '$email'";

        $checkRes = mysqli_query($conn, $checkSql);

        if (mysqli_num_rows($checkRes) > 0) {
            $error = "That email address is already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($phone == "") {
                    $phoneSql = "NULL";
                } else {
                    $phoneSql = "'$phone'";
                }

            $insertUserSql = "INSERT INTO users (full_name, email, password_hash, phone, role, account_status)
                                VALUES ('$full_name', '$email', '$password_hash', $phoneSql, '$role', 'Active')";

            if (mysqli_query($conn, $insertUserSql)) {
                $newUserId = mysqli_insert_id($conn);

                if ($role == "Freelancer") {
                    $headline = mysqli_real_escape_string($conn, trim($_POST["headline"]));
                    $bio = mysqli_real_escape_string($conn, trim($_POST["bio"]));
                    $hourly_rate = (float) $_POST["hourly_rate"];
                    $experience_years = (int) $_POST["experience_years"];
                    

                    // if ($headline == "") {
                    //     $headline = "AI & Data Specialist";
                    // }
                    // if ($bio == "") {
                    //     $bioSql = "NULL";
                    // } else {
                    //     $bioSql = "'$bio'";
                    // }

                    if ($headline == "" || $bio == "") {
                        $error = "Please enter both headline and bio for a Freelancer account.";
                    }else{
                    $bioSql = "'$bio'";

                    if ($hourly_rate <= 0) {
                        $hourly_rate = 25.00;
                    }
                    if ($experience_years < 0) {
                        $experience_years = 0;
                    }

                    $insertFreeSql = "INSERT INTO freelancers (freelancer_id, headline, bio, hourly_rate, experience_years, availability_status)
                                        VALUES ($newUserId, '$headline', $bioSql, $hourly_rate, $experience_years, 'Available')";                   
                    mysqli_query($conn, $insertFreeSql);
                    $message = "Account created successfully! You can now log in.";
                    }

                } else {
                    $client_type = mysqli_real_escape_string($conn, $_POST["client_type"]);
                    $org_name = mysqli_real_escape_string($conn, trim($_POST["organization_name"]));
                    $industry = mysqli_real_escape_string($conn, trim($_POST["industry"]));
                    $country = mysqli_real_escape_string($conn, trim($_POST["country"]));

                    if ($client_type != "Individual" && $client_type != "Organization") {
                        $client_type = "Individual";
                    }
                    if ($country == "") {
                        $country = "Sri Lanka";
                    }
                    if ($org_name == "") {
                        $orgSql = "NULL";
                    } else {
                        $orgSql = "'$org_name'";
                    }
                    if ($industry == "") {
                        $indSql = "NULL";
                    } else {
                        $indSql = "'$industry'";
                    }



                    $insertClientSql = "INSERT INTO clients (client_id, client_type, organization_name, industry, country)
                                        VALUES ($newUserId, '$client_type', $orgSql, $indSql, '$country')";                    
                    mysqli_query($conn, $insertClientSql);
                    $message = "Account created successfully! You can now log in.";
                }

                
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Create an Account</h1>

                <?php if ($error != "") { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>

                <?php if ($message != "") { ?>
                    <div class="alert alert-success">
                        <?php echo $message; ?>
                        <a href="login.php" class="alert-link ms-1">Go to Login</a>
                    </div>
                <?php } ?>

                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+9477...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <!-- <div class="mb-3">
                        <label class="form-label">Register As *</label>
                        <select name="role" class="form-select">
                            <option value="Freelancer">Freelancer</option>
                            <option value="Client">Client</option>
                        </select>
                    </div> -->

                    <div class="mb-3">
                        <label class="form-label">Register As *</label>
                        <select name="role" id="roleSelect" class="form-select" onchange="toggleRoleFields()">
                            <option value="Freelancer" <?php if ($selectedRole == "Freelancer") { echo "selected"; } ?>>Freelancer</option>
                            <option value="Client" <?php if ($selectedRole == "Client") { echo "selected"; } ?>>Client</option>
                        </select>
                    </div>

                    <!-- Freelancer-->
                    <div id="freelancerFields" class="border rounded p-3 bg-light mb-3">
                        <h2 class="h6 mb-3">Freelancer Details</h2>
                        <div class="mb-3">
                            <label class="form-label">Headline</label>
                            <input type="text" id="headlineInput" name="headline" class="form-control" placeholder="e.g. Machine Learning & NLP Engineer">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bio</label>
                            <textarea name="bio" id="bioInput" rows="2" class="form-control" placeholder="Short summary of your AI experience..."></textarea>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Hourly Rate ($)</label>
                                <input type="number" step="0.01" name="hourly_rate" class="form-control" value="35.00">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" name="experience_years" class="form-control" value="0">
                            </div>
                        </div>
                    </div>

                    <!-- Client-->
                    <div id="clientFields" class="border rounded p-3 bg-light mb-3" style="display: none;">
                        <h2 class="h6 mb-3">Client Details</h2>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Client Type</label>
                                <select name="client_type" class="form-select">
                                    <option value="Individual">Individual</option>
                                    <option value="Organization">Organization</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" class="form-control" value="Sri Lanka">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Organization Name (optional)</label>
                            <input type="text" name="organization_name" class="form-control">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Industry (optional)</label>
                            <input type="text" name="industry" class="form-control" placeholder="e.g. FinTech, Healthcare, Retail">
                        </div>
                    </div>

                    <!-- <hr>
                    <p class="small text-muted mb-2">Freelancer Details (if registering as Freelancer):</p>
                    <div class="mb-3">
                        <label class="form-label">Headline</label>
                        <input type="text" name="headline" class="form-control" placeholder="e.g. Machine Learning Engineer">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Hourly Rate ($)</label>
                            <input type="number" step="0.01" name="hourly_rate" class="form-control" value="30.00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Experience (Years)</label>
                            <input type="number" name="experience_years" class="form-control" value="1">
                        </div>
                    </div>

                    <hr>
                    <p class="small text-muted mb-2">Client Details (if registering as Client):</p>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Client Type</label>
                            <select name="client_type" class="form-select">
                                <option value="Individual">Individual</option>
                                <option value="Organization">Organization</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control" value="Sri Lanka">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Organization Name (optional)</label>
                        <input type="text" name="organization_name" class="form-control">
                    </div> -->

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                    <div class="text-center mt-3 small">
                    Already have an account? <a href="login.php">Login here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRoleFields() {
    var role = document.getElementById("roleSelect").value;
    var freelancerBox = document.getElementById("freelancerFields");
    var clientBox = document.getElementById("clientFields");
    var headlineInput = document.getElementById("headlineInput");
    var bioInput = document.getElementById("bioInput");

    if (role === "Client") {
        freelancerBox.style.display = "none";
        clientBox.style.display = "block";
        headlineInput.required = false;
        bioInput.required = false;
    } else {
        freelancerBox.style.display = "block";
        clientBox.style.display = "none";
        headlineInput.required = true;
        bioInput.required = true;
    }
}

toggleRoleFields();
</script>

<?php
include "includes/footer.php";
?>