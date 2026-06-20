<?php
include("config.php");
include("mailer.php");

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$msg = ""; $msgType = ""; $step = "email";

// ── STEP 1: Send OTP ──────────────────────────────────────────────────────────
if (isset($_POST['send_otp'])) {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address."; $msgType = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); $stmt->close();

        if (!$user) {
            $msg = "No account found with this email."; $msgType = "error";
        } else {
            // Generate 6-digit OTP, expire 15 min
            $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date("Y-m-d H:i:s", time() + 900);

            // Store in password_resets table (create if needed)
            $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                otp VARCHAR(10) NOT NULL,
                expires_at DATETIME NOT NULL,
                used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Remove old OTPs for this email
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email=?");
            $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close();

            // Insert new OTP
            $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at) VALUES (?,?,?)");
            $stmt->bind_param("sss", $email, $otp, $expires); $stmt->execute(); $stmt->close();

            // Send email
            $siteName = getSetting($conn, 'site_name', 'Study Planner');
            $html = "
            <div style='font-family:Poppins,Arial,sans-serif;max-width:520px;margin:0 auto;background:#f5f7ff;padding:28px;border-radius:14px;'>
              <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:24px;text-align:center;margin-bottom:22px;'>
                <h2 style='color:#fff;margin:0;font-size:22px;'>🔐 Password Reset</h2>
                <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:13px;'>$siteName</p>
              </div>
              <div style='background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.05);'>
                <p style='font-size:15px;margin:0 0 10px;'>Hi <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                <p style='color:#555;font-size:14px;margin:0 0 20px;'>Use this OTP to reset your password. It expires in <strong>15 minutes</strong>.</p>
                <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:22px;text-align:center;'>
                  <p style='color:rgba(255,255,255,0.8);font-size:13px;margin:0 0 8px;letter-spacing:1px;'>YOUR OTP CODE</p>
                  <p style='color:#fff;font-size:38px;font-weight:900;margin:0;letter-spacing:10px;font-family:monospace;'>$otp</p>
                </div>
                <p style='color:#888;font-size:12px;margin-top:18px;'>If you did not request this, ignore this email — your account is safe.</p>
              </div>
              <div style='text-align:center;color:#aaa;font-size:12px;margin-top:16px;'>
                &copy; " . date("Y") . " $siteName &mdash; <strong style='color:#667eea;'>Developed by Safik Sherasiya</strong>
              </div>
            </div>";

            $sent = sendMail($conn, $email, $user['name'], "🔐 Password Reset OTP – $siteName", $html);

            if ($sent) {
                $_SESSION['fp_email'] = $email;
                $msg = "✅ OTP sent to <strong>$email</strong>. Check your inbox (and spam folder).";
                $msgType = "success";
                $step = "otp";
            } else {
                // Delete the OTP if email failed
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE email=?");
                $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close();
                $msg = "❌ Email delivery failed. Please ask admin to check SMTP settings.";
                $msgType = "error";
            }
        }
    }
}

// ── STEP 2: Verify OTP ────────────────────────────────────────────────────────
if (isset($_POST['verify_otp'])) {
    $email    = $_SESSION['fp_email'] ?? '';
    $otp_in   = trim($_POST['otp'] ?? '');
    $step = "otp";

    if (!$email || !$otp_in) {
        $msg = "Invalid request. Please start again."; $msgType = "error";
    } else {
        $now  = date("Y-m-d H:i:s");
        $stmt = $conn->prepare("SELECT id FROM password_resets WHERE email=? AND otp=? AND expires_at>? AND used=0 LIMIT 1");
        $stmt->bind_param("sss", $email, $otp_in, $now);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

        if (!$row) {
            $msg = "❌ Invalid or expired OTP. Please try again."; $msgType = "error";
        } else {
            $_SESSION['fp_otp_verified'] = true;
            $msg = "✅ OTP verified! Set your new password below.";
            $msgType = "success";
            $step = "reset";
        }
    }
}

// ── STEP 3: Reset Password ────────────────────────────────────────────────────
if (isset($_POST['reset_password'])) {
    $email   = $_SESSION['fp_email']        ?? '';
    $verified= $_SESSION['fp_otp_verified'] ?? false;
    $step = "reset";

    if (!$email || !$verified) {
        header("Location: forgot_password.php"); exit();
    }

    $newPass  = $_POST['new_password']     ?? '';
    $confPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) {
        $msg = "Password must be at least 8 characters."; $msgType = "error";
    } elseif ($newPass !== $confPass) {
        $msg = "Passwords do not match."; $msgType = "error";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email); $stmt->execute(); $stmt->close();

        // Mark OTP as used
        $stmt = $conn->prepare("UPDATE password_resets SET used=1 WHERE email=?");
        $stmt->bind_param("s", $email); $stmt->execute(); $stmt->close();

        // Clear session
        unset($_SESSION['fp_email'], $_SESSION['fp_otp_verified']);

        $_SESSION['login_msg'] = "✅ Password reset successfully! Please log in.";
        header("Location: index.php"); exit();
    }
}

// Restore step if session active
if (empty($step) || $step === "email") {
    if (!empty($_SESSION['fp_otp_verified']))     $step = "reset";
    elseif (!empty($_SESSION['fp_email']))         $step = "otp";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password | Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
<style>
.fp-card {
  width: min(440px, 94vw);
  background: #fff;
  border-radius: 22px;
  padding: 40px 38px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.18);
}
.fp-card h2   { text-align: center; color: #111827; margin-bottom: 6px; font-size: 22px; }
.fp-card .sub { text-align: center; color: #6b7280; font-size: 14px; margin-bottom: 24px; }
.fp-group { margin-bottom: 16px; }
.fp-group label { display: block; font-size: 13px; font-weight: 600; color: #4b5563; margin-bottom: 6px; }
.fp-group input {
  width: 100%; padding: 11px 14px;
  border: 1.5px solid #e0e0e0; border-radius: 12px;
  font-family: "Poppins", sans-serif; font-size: 14px;
  outline: none; box-sizing: border-box; transition: border .2s;
  background: #fff; color: #111;
}
.fp-group input:focus { border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,0.18); }
.otp-input {
  font-size: 28px !important;
  font-weight: 900 !important;
  text-align: center !important;
  letter-spacing: 12px !important;
  font-family: monospace !important;
}
.fp-back { display: block; text-align: center; margin-top: 16px; font-size: 13px; color: #6b7280; }
.fp-back a { color: #667eea; font-weight: 600; text-decoration: none; }
.fp-back a:hover { text-decoration: underline; }
.step-dots { display: flex; justify-content: center; gap: 8px; margin-bottom: 22px; }
.dot { width: 10px; height: 10px; border-radius: 50%; background: #e0e0e0; transition: background .3s; }
.dot.active { background: #667eea; }
.msg { padding: 12px 14px; border-radius: 12px; margin-bottom: 16px; font-size: 14px; }
.msg.success { background: rgba(46,204,113,0.10); color: #1e7e34; border: 1px solid rgba(46,204,113,0.25); }
.msg.error   { background: rgba(231,76,60,0.10); color: #b02a37; border: 1px solid rgba(231,76,60,0.25); }
</style>
</head>
<body class="login-page">

<div class="fp-card">
  <div style="text-align:center;margin-bottom:20px;">
    <img src="images/app_logo.png" alt="Logo" style="width:52px;height:52px;border-radius:14px;box-shadow:0 6px 18px rgba(0,0,0,0.18);">
  </div>

  <!-- Step dots -->
  <div class="step-dots">
    <div class="dot <?php echo in_array($step,['email','otp','reset'])?'active':''; ?>"></div>
    <div class="dot <?php echo in_array($step,['otp','reset'])?'active':''; ?>"></div>
    <div class="dot <?php echo $step==='reset'?'active':''; ?>"></div>
  </div>

  <?php if ($msg): ?>
    <div class="msg <?php echo $msgType; ?>"><?php echo $msg; ?></div>
  <?php endif; ?>

  <?php if ($step === "email"): ?>
    <!-- STEP 1 -->
    <h2>Forgot Password?</h2>
    <p class="sub">Enter your registered email. We'll send you a 6-digit OTP.</p>
    <form method="POST">
      <div class="fp-group">
        <label>Registered Email</label>
        <input type="email" name="email" placeholder="you@gmail.com" required autofocus>
      </div>
      <button class="btn" name="send_otp" type="submit">Send OTP 📧</button>
    </form>

  <?php elseif ($step === "otp"): ?>
    <!-- STEP 2 -->
    <h2>Enter OTP</h2>
    <p class="sub">We sent a 6-digit code to <strong><?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?></strong></p>
    <form method="POST">
      <div class="fp-group">
        <label>6-Digit OTP</label>
        <input type="text" name="otp" class="otp-input" maxlength="6" placeholder="000000" required autofocus inputmode="numeric">
      </div>
      <button class="btn" name="verify_otp" type="submit">Verify OTP ✅</button>
    </form>
    <form method="POST" style="margin-top:12px;">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($_SESSION['fp_email'] ?? ''); ?>">
      <button class="btn btnGhost" name="send_otp" type="submit" style="font-size:13px;padding:10px;">Resend OTP</button>
    </form>

  <?php elseif ($step === "reset"): ?>
    <!-- STEP 3 -->
    <h2>Set New Password</h2>
    <p class="sub">Create a strong password for your account.</p>
    <form method="POST">
      <div class="fp-group">
        <label>New Password</label>
        <input type="password" name="new_password" id="newPass" placeholder="Min 8 characters" required autofocus oninput="fpStrength(this.value)">
        <div id="fpStrengthBar" style="height:4px;background:#e0e0e0;border-radius:99px;margin-top:6px;overflow:hidden;">
          <div id="fpFill" style="height:100%;width:0;border-radius:99px;transition:width .3s,background .3s;"></div>
        </div>
      </div>
      <div class="fp-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat password" required>
      </div>
      <button class="btn" name="reset_password" type="submit">Reset Password 🔐</button>
    </form>
  <?php endif; ?>

  <span class="fp-back">
    Remember your password? <a href="index.php">Back to Login</a>
  </span>
</div>

<script>
function fpStrength(p) {
  const fill = document.getElementById('fpFill');
  if (!fill) return;
  let s = 0;
  if (p.length >= 8)             s += 25;
  if (/[a-z]/.test(p))           s += 20;
  if (/[A-Z]/.test(p))           s += 20;
  if (/[0-9]/.test(p))           s += 20;
  if (/[^A-Za-z0-9]/.test(p))    s += 15;
  fill.style.width = Math.min(s,100) + '%';
  fill.style.background = s < 40 ? '#e74c3c' : s < 70 ? '#f39c12' : '#2ecc71';
}
// Only allow digits in OTP field
document.querySelector('.otp-input')?.addEventListener('input', function(){
  this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

<?php include("footer.php"); ?>
</body>
</html>
