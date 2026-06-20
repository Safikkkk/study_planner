<?php
include("config.php");
include("mailer.php");

$msg = "";
$msgType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $cname    = trim($_POST['c_name'] ?? '');
    $cemail   = trim($_POST['c_email'] ?? '');
    $csubject = trim($_POST['c_subject'] ?? '');
    $cmessage = trim($_POST['c_message'] ?? '');

    if ($cname === '' || $cemail === '' || $csubject === '' || $cmessage === '') {
        $msg = "Please fill all fields.";
        $msgType = "error";
    } elseif (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid email address.";
        $msgType = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $cname, $cemail, $csubject, $cmessage);
        $stmt->execute();
        $stmt->close();

        // Send emails
        sendContactConfirmation($conn, $cname, $cemail, $csubject);
        sendAdminNotification($conn, $cname, $cemail, $csubject, $cmessage);

        $msg = "✅ Message sent! We'll get back to you within 24-48 hours.";
        $msgType = "success";
    }
}

$siteName = getSetting($conn, 'site_name', 'Study Planner');
$aboutHeading = getSetting($conn, 'about_heading', 'About Study Planner');
$aboutText    = getSetting($conn, 'about_text', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About & Contact | <?php echo htmlspecialchars($siteName); ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
<style>
.about-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    text-align: center;
    padding: 60px 20px 50px;
    position: relative;
    overflow: hidden;
}
.about-hero::before {
    content: '';
    position: absolute;
    width: 300px; height: 300px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    top: -80px; right: -60px;
}
.about-hero img { width: 90px; border-radius: 20px; margin-bottom: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.25); }
.about-hero h1 { font-size: 32px; font-weight: 700; margin: 0 0 10px; }
.about-hero p { opacity: 0.9; max-width: 560px; margin: 0 auto; font-size: 16px; }

.about-nav {
    background: #fff;
    display: flex;
    justify-content: center;
    gap: 6px;
    padding: 12px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    position: sticky;
    top: 0;
    z-index: 10;
    flex-wrap: wrap;
}
.about-nav a {
    text-decoration: none;
    color: #555;
    padding: 8px 18px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}
.about-nav a:hover, .about-nav a.active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}

.about-section {
    max-width: 980px;
    margin: 0 auto;
    padding: 50px 20px;
}
.about-section h2 {
    font-size: 26px;
    font-weight: 700;
    margin-bottom: 18px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.about-section p {
    color: #555;
    line-height: 1.8;
    font-size: 15px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
    margin-top: 30px;
}
.feature-item {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    border: 1px solid #f0f0f0;
    transition: transform 0.2s, box-shadow 0.2s;
}
.feature-item:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(102,126,234,0.15); }
.feature-item .icon { font-size: 36px; margin-bottom: 12px; }
.feature-item h3 { font-size: 16px; font-weight: 600; margin-bottom: 8px; color: #333; }
.feature-item p { font-size: 13px; color: #777; margin: 0; }

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 24px;
    margin-top: 30px;
}
.team-card {
    background: #fff;
    border-radius: 16px;
    padding: 28px 20px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}
.team-card .avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex; align-items: center; justify-content: center;
    font-size: 28px;
    margin: 0 auto 14px;
    color: #fff;
}
.team-card h3 { font-size: 16px; font-weight: 600; margin: 0 0 4px; }
.team-card p { font-size: 13px; color: #888; margin: 0; }

.contact-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 6px 30px rgba(0,0,0,0.07);
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.contact-left {
    background: linear-gradient(160deg, #667eea, #764ba2);
    padding: 40px 32px;
    color: #fff;
}
.contact-left h3 { font-size: 22px; font-weight: 700; margin-bottom: 14px; }
.contact-left p { opacity: 0.88; line-height: 1.7; margin-bottom: 24px; }
.contact-info-item {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 16px; font-size: 14px;
}
.contact-info-item span:first-child { font-size: 20px; }
.contact-right { padding: 36px 32px; }
.contact-right h3 { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #333; }
.cf-group { margin-bottom: 16px; }
.cf-group label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
.cf-group input, .cf-group textarea, .cf-group select {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e0e0e0;
    border-radius: 10px;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border 0.2s;
    box-sizing: border-box;
}
.cf-group input:focus, .cf-group textarea:focus { border-color: #667eea; }
.cf-group textarea { resize: vertical; min-height: 110px; }

.msg { padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 500; }
.msg.success { background: #eafaf1; color: #27ae60; border: 1px solid #a9dfbf; }
.msg.error   { background: #fdf0f0; color: #e74c3c; border: 1px solid #f1948a; }

.about-footer {
    background: #1e2038;
    color: rgba(255,255,255,0.75);
    text-align: center;
    padding: 28px 20px;
    font-size: 13px;
}
.about-footer a { color: #a29bfe; text-decoration: none; }

@media (max-width: 700px) {
    .contact-card { grid-template-columns: 1fr; }
    .about-hero h1 { font-size: 24px; }
}
</style>
</head>
<body style="margin:0; background:#f5f7ff; font-family:'Poppins',sans-serif;">

<!-- HERO -->
<div class="about-hero">
    <img src="images/app_logo.png" alt="Logo">
    <h1><?php echo htmlspecialchars($aboutHeading); ?></h1>
    <p>Helping students study smarter, stay organized, and achieve more.</p>
</div>

<!-- NAV -->
<nav class="about-nav">
    <a href="#about" class="active">About</a>
    <a href="#features">Features</a>
    <a href="#team">Team</a>
    <a href="#contact">Contact</a>
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="dashboard.php">← Dashboard</a>
    <?php else: ?>
        <a href="index.php">Login</a>
        <a href="register.php">Register</a>
    <?php endif; ?>
</nav>

<!-- ABOUT -->
<div id="about" class="about-section">
    <h2>📖 Who We Are</h2>
    <p><?php echo nl2br(htmlspecialchars($aboutText)); ?></p>
    <p style="margin-top:14px;">Built with passion by students, for students — our platform combines smart scheduling, real-time progress tracking, rewards, and reminders in one beautiful, mobile-friendly interface.</p>
</div>

<!-- FEATURES -->
<div id="features" style="background:#fff; padding:20px 0;">
<div class="about-section" style="padding-top:30px; padding-bottom:40px;">
    <h2>🚀 Key Features</h2>
    <div class="feature-grid">
        <div class="feature-item"><div class="icon">📚</div><h3>Subject Management</h3><p>Organize all your subjects by priority and track each one individually.</p></div>
        <div class="feature-item"><div class="icon">🗓</div><h3>Study Planner</h3><p>Add tasks, set deadlines, and start a live study timer for each task.</p></div>
        <div class="feature-item"><div class="icon">⏰</div><h3>Smart Reminders</h3><p>Get browser notifications for upcoming tasks and deadlines.</p></div>
        <div class="feature-item"><div class="icon">📊</div><h3>Progress Analytics</h3><p>Visual charts and PDF reports to see how far you've come.</p></div>
        <div class="feature-item"><div class="icon">🏆</div><h3>Rewards & Badges</h3><p>Earn points and unlock badges as you complete tasks.</p></div>
        <div class="feature-item"><div class="icon">🌙</div><h3>Dark Mode</h3><p>Switch between light and dark themes for comfortable studying.</p></div>
    </div>
</div>
</div>

<!-- TEAM -->
<div id="team" class="about-section">
    <h2>👥 Our Team</h2>
    <div class="team-grid">
        <div class="team-card">
            <div class="avatar">SS</div>
            <h3>Safik Sherasiya</h3>
            
        </div>
        <div class="team-card">
            <div class="avatar">NB</div>
            <h3>Nisar Badi</h3>
            
        </div>
        <div class="team-card">
            <div class="avatar">AK</div>
            <h3>Azim Kadivar</h3>
            
        </div>
    </div>
</div>

<!-- CONTACT -->
<div id="contact" style="background:#fff; padding:20px 0;">
<div class="about-section" style="padding-top:30px; padding-bottom:50px;">
    <h2>📬 Contact Us</h2>

    <?php if ($msg): ?>
        <div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>

    <div class="contact-card">
        <div class="contact-left">
            <h3>Get In Touch</h3>
            <p>Have questions, feedback, or want to collaborate? We'd love to hear from you. Fill out the form and we'll respond within 24–48 hours.</p>
            <div class="contact-info-item">
                <span>📧</span>
                <span><?php echo htmlspecialchars(getSetting($conn, 'contact_email', 'studyplanner@gmail.com')); ?></span>
            </div>
            <div class="contact-info-item">
                <span>📍</span>
                <span>Gujarat, India</span>
            </div>
            <div class="contact-info-item">
                <span>🕐</span>
                <span>Response time: 24–48 hours</span>
            </div>
        </div>

        <div class="contact-right">
            <h3>Send a Message</h3>
            <form method="POST">
                <div class="cf-group">
                    <label>Your Name</label>
                    <input type="text" name="c_name" placeholder="Full Name" required>
                </div>
                <div class="cf-group">
                    <label>Email Address</label>
                    <input type="email" name="c_email" placeholder="you@email.com" required>
                </div>
                <div class="cf-group">
                    <label>Subject</label>
                    <input type="text" name="c_subject" placeholder="How can we help?" required>
                </div>
                <div class="cf-group">
                    <label>Message</label>
                    <textarea name="c_message" placeholder="Write your message here..." required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn" style="width:100%;margin-top:4px;">Send Message 📤</button>
            </form>
        </div>
    </div>
</div>
</div>

<!-- FOOTER -->
<div class="about-footer">
    <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($siteName); ?> &mdash; All Rights Reserved.<br>
    Developed by Safik Sherasiya, Nisar Badi &amp; Azim Kadivar &nbsp;|&nbsp;
    <a href="index.php">Login</a> &nbsp;|&nbsp; <a href="register.php">Register</a>
    </p>
</div>

<script>
// Smooth scroll for nav links
document.querySelectorAll('.about-nav a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const el = document.querySelector(a.getAttribute('href'));
        if (el) el.scrollIntoView({ behavior: 'smooth' });
        document.querySelectorAll('.about-nav a').forEach(l => l.classList.remove('active'));
        a.classList.add('active');
    });
});
</script>
</body>
</html>
