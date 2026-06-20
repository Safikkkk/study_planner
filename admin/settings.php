<?php include("admin_auth.php"); include("../mailer.php"); ?>
<?php
$msg = ""; $msgType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $fields = ['site_name','contact_email','smtp_from_name','about_heading','about_text'];
    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param("sss", $f, $val, $val);
        $stmt->execute();
    }
    $msg = "Settings saved successfully!"; $msgType = "success";
}

// Send test email
if (isset($_POST['test_email'])) {
    $to = trim($_POST['test_to'] ?? '');
    if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $sent = sendMail($conn, $to, 'Admin', 'Study Planner – Test Email', '<p>This is a test email from Study Planner admin panel. Your email integration is working correctly! ✅</p>');
        $msg = $sent ? "Test email sent to $to ✅" : "Failed to send. Check your server mail() config.";
        $msgType = $sent ? "success" : "error";
    }
}

$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM site_settings");
while ($r = $res->fetch_assoc()) $settings[$r['setting_key']] = $r['setting_value'];
function sv($settings, $key, $def='') { return htmlspecialchars($settings[$key] ?? $def); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Settings | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
<style>
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.settings-card { background:#fff; border-radius:16px; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
.settings-card h3 { font-size:17px; font-weight:700; margin:0 0 20px; color:#333; }
.sf-group { margin-bottom:16px; }
.sf-group label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:6px; }
.sf-group input, .sf-group textarea {
    width:100%; padding:10px 14px; border:1.5px solid #e0e0e0; border-radius:10px;
    font-family:Poppins,sans-serif; font-size:14px; outline:none; box-sizing:border-box; transition:border 0.2s;
}
.sf-group input:focus, .sf-group textarea:focus { border-color:#667eea; }
.sf-group textarea { resize:vertical; min-height:100px; }
.info-box { background:#f0f4ff; border-left:4px solid #667eea; padding:14px 16px; border-radius:8px; font-size:13px; margin-bottom:20px; color:#555; line-height:1.6; }
@media(max-width:700px) { .settings-grid { grid-template-columns:1fr; } }
body.dark .settings-card { background:#2a2a3e; }
body.dark .settings-card h3 { color:#eee; }
</style>
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <h1>⚙ Site Settings</h1>
    <?php if ($msg): ?><div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <form method="POST">
    <div class="settings-grid">
        <!-- General -->
        <div class="settings-card">
            <h3>🌐 General Settings</h3>
            <div class="sf-group">
                <label>Site Name</label>
                <input type="text" name="site_name" value="<?php echo sv($settings,'site_name','Study Planner'); ?>">
            </div>
            <div class="sf-group">
                <label>Contact / From Email</label>
                <input type="email" name="contact_email" value="<?php echo sv($settings,'contact_email'); ?>" placeholder="admin@yoursite.com">
            </div>
            <div class="sf-group">
                <label>Email From Name</label>
                <input type="text" name="smtp_from_name" value="<?php echo sv($settings,'smtp_from_name','Study Planner'); ?>">
            </div>
            <button class="btn" name="save_settings" type="submit">💾 Save Settings</button>
        </div>

        <!-- About Page Content -->
        <div class="settings-card">
            <h3>📖 About Page Content</h3>
            <div class="sf-group">
                <label>About Page Heading</label>
                <input type="text" name="about_heading" value="<?php echo sv($settings,'about_heading','About Study Planner'); ?>">
            </div>
            <div class="sf-group">
                <label>About Text</label>
                <textarea name="about_text"><?php echo sv($settings,'about_text'); ?></textarea>
            </div>
            <button class="btn" name="save_settings" type="submit">💾 Save</button>
        </div>
    </div>
    </form>

    <!-- Email Test -->
    <div class="settings-card" style="margin-top:20px;">
        <h3>📧 Test Email</h3>
        <div class="info-box">
            This system uses PHP's built-in <code>mail()</code> function. For this to work, your server must have a mail server configured (most shared hosting providers support this). If you need SMTP via Gmail or external service, install PHPMailer via Composer and update <code>mailer.php</code>.
        </div>
        <form method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="email" name="test_to" placeholder="Send test email to..." required style="flex:1;min-width:220px;padding:10px 14px;border:1.5px solid #e0e0e0;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;outline:none;">
            <button class="btn" name="test_email">Send Test Email</button>
        </form>
    </div>
</div>
<?php include("../footer.php"); ?>
</body>
</html>
