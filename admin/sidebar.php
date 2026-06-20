<?php
function adminActive($page, $cur) {
    return basename($cur) === $page ? 'active' : '';
}
$cur = $_SERVER['PHP_SELF'];
?>
<button class="hamburger" id="hamburgerBtn" aria-label="Open menu">&#9776;</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="mainSidebar">
    <div class="sidebar-logo">
        <img src="../images/app_logo.png" alt="Logo">
        <span>Admin Panel</span>
    </div>

    <div class="profile-box">
        <img src="../uploads/default.png" alt="Admin">
        <h3><?php echo htmlspecialchars($admin_name); ?></h3>
        <small style="color:rgba(255,255,255,0.6);font-size:11px;">Administrator</small>
    </div>

    <a href="index.php"    class="<?php echo adminActive('index.php',$cur); ?>">📊 Dashboard</a>
    <a href="users.php"    class="<?php echo adminActive('users.php',$cur); ?>">👥 Users</a>
    <a href="tasks.php"    class="<?php echo adminActive('tasks.php',$cur); ?>">📋 All Tasks</a>
    <a href="subjects.php" class="<?php echo adminActive('subjects.php',$cur); ?>">📚 Subjects</a>
    <a href="messages.php" class="<?php echo adminActive('messages.php',$cur); ?>">📬 Messages</a>
    <a href="settings.php" class="<?php echo adminActive('settings.php',$cur); ?>">⚙ Settings</a>
    <hr style="border:1px solid rgba(255,255,255,0.1);margin:10px 0;">
    <a href="../dashboard.php">🏠 Student View</a>
    <a href="../logout.php">🚪 Logout</a>
</div>

<script>
(function(){
    const hamburger = document.getElementById("hamburgerBtn");
    const sidebar   = document.getElementById("mainSidebar");
    const overlay   = document.getElementById("sidebarOverlay");

    const saved = localStorage.getItem("darkMode");
    if (saved === "on") document.body.classList.add("dark");

    function openSidebar()  { sidebar.classList.add("open"); overlay.classList.add("open"); }
    function closeSidebar() { sidebar.classList.remove("open"); overlay.classList.remove("open"); }

    if (hamburger) hamburger.addEventListener("click", openSidebar);
    if (overlay)   overlay.addEventListener("click", closeSidebar);
    sidebar.querySelectorAll("a").forEach(l => l.addEventListener("click", () => {
        if (window.innerWidth <= 1000) closeSidebar();
    }));
})();
</script>
