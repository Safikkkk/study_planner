<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

$stmt = $conn->prepare("SELECT profile_pic, name FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

function activeLink($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}
?>

<!-- Hamburger button (mobile only) -->
<button class="hamburger" id="hamburgerBtn" aria-label="Open menu">&#9776;</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="mainSidebar">

    <div class="sidebar-logo">
        <img src="images/app_logo.png" alt="Study Planner Logo">
        <span>Study Planner</span>
    </div>

    <div class="profile-box">
        <?php if (!empty($user['profile_pic']) && file_exists("uploads/" . $user['profile_pic'])) { ?>
            <img src="uploads/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile">
        <?php } else { ?>
            <img src="uploads/default.png" alt="Profile">
        <?php } ?>
        <h3><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h3>
    </div>

    <button id="darkToggleBtn" class="theme-btn">🌙 Toggle Dark Mode</button>

    <a href="dashboard.php"  class="<?php echo activeLink('dashboard.php',  $current_page); ?>">🏠 Dashboard</a>
    <a href="subject.php"    class="<?php echo activeLink('subject.php',    $current_page); ?>">📚 Subjects</a>
    <a href="planner.php"    class="<?php echo activeLink('planner.php',    $current_page); ?>">🗓 Study Planner</a>
    <a href="reminders.php"  class="<?php echo activeLink('reminders.php',  $current_page); ?>">⏰ Reminders</a>
    <a href="progress.php"   class="<?php echo activeLink('progress.php',   $current_page); ?>">📊 Progress</a>
    <a href="rewards.php"    class="<?php echo activeLink('rewards.php',    $current_page); ?>">🏆 Rewards</a>
    <a href="profile.php"    class="<?php echo activeLink('profile.php',    $current_page); ?>">👤 Profile</a>
    <a href="about.php"    class="<?php echo activeLink('about.php',    $current_page); ?>">ℹ️ About & Contact</a>
    <?php
    // Show admin link only if user is admin
    $stmt2 = $conn->prepare("SELECT is_admin FROM users WHERE id=? LIMIT 1");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $adminCheck = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if (!empty($adminCheck['is_admin'])): ?>
        <a href="admin/index.php">⚙ Admin Panel</a>
    <?php endif; ?>
    <a href="logout.php">🚪 Logout</a>

</div>

<script>
(function () {
    const btn      = document.getElementById("darkToggleBtn");
    const hamburger = document.getElementById("hamburgerBtn");
    const sidebar   = document.getElementById("mainSidebar");
    const overlay   = document.getElementById("sidebarOverlay");

    /* Dark mode */
    const saved = localStorage.getItem("darkMode");
    if (saved === "on") document.body.classList.add("dark");

    function setBtnText() {
        if (!btn) return;
        btn.innerText = document.body.classList.contains("dark")
            ? "☀ Light Mode"
            : "🌙 Toggle Dark Mode";
    }
    setBtnText();

    if (btn) {
        btn.addEventListener("click", function () {
            document.body.classList.toggle("dark");
            localStorage.setItem("darkMode",
                document.body.classList.contains("dark") ? "on" : "off");
            setBtnText();
        });
    }

    /* Mobile sidebar toggle */
    function openSidebar()  { sidebar.classList.add("open"); overlay.classList.add("open"); }
    function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); }

    if (hamburger) hamburger.addEventListener("click", openSidebar);
    if (overlay)   overlay.addEventListener("click", closeSidebar);

    /* Close sidebar when a nav link is tapped on mobile */
    sidebar.querySelectorAll("a").forEach(function(link) {
        link.addEventListener("click", function() {
            if (window.innerWidth <= 1000) closeSidebar();
        });
    });
})();
</script>
