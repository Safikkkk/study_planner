<?php
include("config.php");

// If already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$email = "";
$loginMsg = "";

if (!empty($_SESSION['login_msg'])) {
    $loginMsg = $_SESSION['login_msg'];
    unset($_SESSION['login_msg']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please fill all fields!";
    } else {

        $stmt = $conn->prepare("SELECT id, name, email, password, status, is_admin FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // Check if banned
                $banStatus = $user['status'] ?? 'active';
                if ($banStatus === 'banned') {
                    $error = "Your account has been suspended. Please contact support.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];

                    // Redirect admin to admin panel
                    if (!empty($user['is_admin'])) {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                }

            } else {
                $error = "Invalid email or password!";
            }

        } else {
            $error = "Invalid email or password!";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">

<style>
.error {
    color: #e53935;
    font-size: 14px;
    margin-bottom: 10px;
    text-align: center;
    font-weight: 500;
}
</style>
</head>

<body class="login-page">

<div class="container">

    <div class="left">
        <div class="login-logo">
            <img src="images/app_logo.png" alt="Study Planner Logo">
            <span>Study Planner</span>
        </div>
        <h1>Study Smart 📚</h1>
        <p>
            Learning Reminder & Study Planner helps students organize their study schedule,
            set reminders, track progress, and stay productive.
        </p>
    </div>

    <div class="right">
        <h2>Student Login</h2>

        <?php if ($loginMsg != "") { ?>
            <div class="msg success" style="margin-bottom:16px;"><?php echo htmlspecialchars($loginMsg); ?></div>
        <?php } ?>

        <?php if ($error != "") { ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php } ?>

        <form method="POST">

            <div class="input-box">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="input-box">
                <label>Password</label>
                <input type="password" name="password" required>
                <span class="forgot-link"><a href="forgot_password.php">Forgot Password?</a></span>
            </div>

            <button class="btn" type="submit">Login</button>
        </form>

        <div class="extra">
            Don’t have an account? <a href="register.php">Register</a>
        </div>
    </div>

</div>

<?php include("footer.php"); ?>
</body>
</html>