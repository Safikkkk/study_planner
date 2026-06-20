<?php include("admin_auth.php"); include("../mailer.php"); ?>
<?php
// Stats
$stats = [];
foreach ([
    'total_users'    => "SELECT COUNT(*) c FROM users WHERE is_admin=0",
    'total_tasks'    => "SELECT COUNT(*) c FROM tasks",
    'completed'      => "SELECT COUNT(*) c FROM tasks WHERE status='completed'",
    'total_subjects' => "SELECT COUNT(*) c FROM subjects",
    'unread_msgs'    => "SELECT COUNT(*) c FROM contact_messages WHERE status='unread'",
    'total_reminders'=> "SELECT COUNT(*) c FROM reminders",
] as $key => $sql) {
    $r = $conn->query($sql);
    $stats[$key] = $r ? (int)$r->fetch_assoc()['c'] : 0;
}

// Recent users
$recentUsers = $conn->query("SELECT id, name, email, created_at, badge FROM users WHERE is_admin=0 ORDER BY id DESC LIMIT 5");
// Recent messages
$recentMsgs  = $conn->query("SELECT id, name, subject, status, created_at FROM contact_messages ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | Study Planner</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
<style>
.admin-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.admin-header h1 { margin:0; }
.stat-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:28px; }
.stat-card { background:#fff; border-radius:16px; padding:22px 18px; box-shadow:0 4px 20px rgba(0,0,0,0.06); text-align:center; border-top:4px solid #667eea; }
.stat-card h3 { font-size:13px; color:#888; margin:0 0 8px; font-weight:500; }
.stat-card p  { font-size:32px; font-weight:700; margin:0; color:#333; }
.stat-card.red   { border-top-color:#e74c3c; }
.stat-card.green { border-top-color:#2ecc71; }
.stat-card.orange{ border-top-color:#f39c12; }
.stat-card.purple{ border-top-color:#9b59b6; }
.stat-card p.unread { color:#e74c3c; }
body.dark .stat-card { background:#2a2a3e; }
body.dark .stat-card p { color:#eee; }
</style>
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <div class="admin-header">
        <h1>⚙ Admin Dashboard</h1>
        <span style="color:#888;font-size:14px;"><?php echo date("l, d M Y"); ?></span>
    </div>

    <div class="stat-cards">
        <div class="stat-card"><h3>Total Students</h3><p><?php echo $stats['total_users']; ?></p></div>
        <div class="stat-card green"><h3>Completed Tasks</h3><p><?php echo $stats['completed']; ?></p></div>
        <div class="stat-card orange"><h3>Total Tasks</h3><p><?php echo $stats['total_tasks']; ?></p></div>
        <div class="stat-card purple"><h3>Subjects</h3><p><?php echo $stats['total_subjects']; ?></p></div>
        <div class="stat-card red"><h3>Unread Messages</h3><p class="<?php echo $stats['unread_msgs'] > 0 ? 'unread' : ''; ?>"><?php echo $stats['unread_msgs']; ?></p></div>
        <div class="stat-card"><h3>Reminders</h3><p><?php echo $stats['total_reminders']; ?></p></div>
    </div>

    <div class="table-box">
        <h3 style="margin-bottom:14px;">Recent Students</h3>
        <table>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Badge</th><th>Joined</th></tr>
            <?php if ($recentUsers && $recentUsers->num_rows > 0):
                while ($u = $recentUsers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['badge']); ?></td>
                    <td><?php echo date("d M Y", strtotime($u['created_at'])); ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">No students yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="table-box" style="margin-top:20px;">
        <h3 style="margin-bottom:14px;">Recent Contact Messages
            <?php if ($stats['unread_msgs'] > 0): ?>
                <span style="background:#e74c3c;color:#fff;padding:2px 10px;border-radius:20px;font-size:12px;margin-left:8px;"><?php echo $stats['unread_msgs']; ?> new</span>
            <?php endif; ?>
        </h3>
        <table>
            <tr><th>From</th><th>Subject</th><th>Status</th><th>Date</th><th>Action</th></tr>
            <?php if ($recentMsgs && $recentMsgs->num_rows > 0):
                while ($m = $recentMsgs->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($m['name']); ?></td>
                    <td><?php echo htmlspecialchars($m['subject']); ?></td>
                    <td><span class="status <?php echo $m['status'] === 'unread' ? 'pending' : 'completed'; ?>"><?php echo ucfirst($m['status']); ?></span></td>
                    <td><?php echo date("d M Y", strtotime($m['created_at'])); ?></td>
                    <td><a href="messages.php?view=<?php echo $m['id']; ?>" class="btn" style="padding:4px 12px;font-size:12px;">View</a></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">No messages yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php include("../footer.php"); ?>
</body>
</html>
