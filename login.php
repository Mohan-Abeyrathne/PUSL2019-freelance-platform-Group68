<?php
include "config/db.php";
include "includes/header.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, trim($_POST["email"]));
    $password = $_POST["password"];

    if ($email == "" || $password == "") {
        $error = "Please enter both email and password.";
    } else {
        $sql = "SELECT user_id, full_name, email, password_hash, role, account_status
                FROM users
                WHERE email = '$email'
                LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);

            if ($user["account_status"] != "Active") {
                $error = "This account is currently " . $user["account_status"] . ".";
            } elseif (password_verify($password, $user["password_hash"]) || $password == $user["password_hash"]) {
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                // header("Location: index.php");
                if ($user["role"] == "Admin") {
                    header("Location: modules/admin_dashboard.php");
                }elseif ($user["role"] == "Manager") {
                    header("Location: modules/manager_reports.php");
                }
                else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Login</h1>

                <?php if ($error != "") { ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php } ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-dark w-100">Login</button>
                </form>

                <div class="text-center mt-3 small">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include "includes/footer.php";
?>