<?php include("admin_auth.php"); ?>
<?php
$msg = ""; $msgType = "";

// BAN / UNBAN
if (isset($_GET['ban'])) {
    $uid = (int)$_GET['ban'];
    if ($uid !== $user_id) {
        $conn->prepare("UPDATE users SET status='banned' WHERE id=?")->execute() ||
        $conn->query("UPDATE users SET status='banned' WHERE id={$uid}");
        $stmt = $conn->prepare("UPDATE users SET status='banned' WHERE id=?");
        $stmt->bind_param("i",$uid); $stmt->execute();
        $msg = "User banned."; $msgType = "error";
    }
}
if (isset($_GET['unban'])) {
    $uid = (int)$_GET['unban'];
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i",$uid); $stmt->execute();
    $msg = "User unbanned."; $msgType = "success";
}

// DELETE
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid !== $user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND is_admin=0");
        $stmt->bind_param("i",$uid); $stmt->execute();
        $msg = "User deleted."; $msgType = "error";
    }
}

// RESET PASSWORD
if (isset($_POST['reset_pass'])) {
    $uid = (int)$_POST['uid'];
    $np  = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si",$np,$uid); $stmt->execute();
    $msg = "Password reset successfully."; $msgType = "success";
}

// SEARCH
$search = trim($_GET['q'] ?? '');
if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE is_admin=0 AND (name LIKE ? OR email LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE is_admin=0 ORDER BY id DESC");
}
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
<style>
.search-bar { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
.search-bar input { flex:1; min-width:200px; padding:10px 16px; border:1.5px solid #e0e0e0; border-radius:10px; font-family:'Poppins',sans-serif; font-size:14px; outline:none; }
.search-bar input:focus { border-color:#667eea; }
.action-links a { font-size:12px; padding:4px 10px; border-radius:6px; text-decoration:none; display:inline-block; margin:2px; }
.ban-btn   { background:#fff3f3; color:#e74c3c; border:1px solid #e74c3c; }
.unban-btn { background:#eafaf1; color:#27ae60; border:1px solid #27ae60; }
.del-btn   { background:#f5f5f5; color:#555;    border:1px solid #ccc; }
.rp-btn    { background:#eef2ff; color:#667eea; border:1px solid #667eea; }
.user-pic  { width:34px; height:34px; border-radius:50%; object-fit:cover; }
</style>
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <h1>👥 Manage Users</h1>
    <?php if ($msg): ?><div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($search); ?>">
        <button class="btn" type="submit">🔍 Search</button>
        <?php if ($search): ?><a href="users.php" class="btn" style="background:#eee;color:#333;">✖ Clear</a><?php endif; ?>
    </form>

    <!-- Reset password modal -->
    <div id="rpModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:16px;padding:32px;width:340px;max-width:90vw;">
            <h3 style="margin-bottom:16px;">🔐 Reset Password</h3>
            <form method="POST">
                <input type="hidden" name="uid" id="rpUid">
                <label style="font-size:13px;font-weight:600;">New Password</label>
                <input type="text" name="new_password" required style="width:100%;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;margin:8px 0 16px;font-size:14px;font-family:Poppins,sans-serif;box-sizing:border-box;">
                <div style="display:flex;gap:10px;">
                    <button class="btn" name="reset_pass" style="flex:1;">Reset</button>
                    <button type="button" onclick="closeModal()" class="btn" style="flex:1;background:#eee;color:#333;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-box">
        <table>
            <tr><th>Photo</th><th>Name</th><th>Email</th><th>Phone</th><th>Badge</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            <?php if ($users && $users->num_rows > 0):
                while ($u = $users->fetch_assoc()):
                $pic = (!empty($u['profile_pic']) && file_exists("../uploads/".$u['profile_pic'])) ? "../uploads/".htmlspecialchars($u['profile_pic']) : "../uploads/default.png";
                $status = $u['status'] ?? 'active';
            ?>
            <tr>
                <td><img src="<?php echo $pic; ?>" class="user-pic"></td>
                <td><?php echo htmlspecialchars($u['name']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($u['badge'] ?? 'Beginner'); ?></td>
                <td><span class="status <?php echo $status==='active'?'completed':'pending'; ?>"><?php echo ucfirst($status); ?></span></td>
                <td><?php echo date("d M Y", strtotime($u['created_at'])); ?></td>
                <td class="action-links">
                    <?php if ($status === 'active'): ?>
                        <a class="ban-btn" href="?ban=<?php echo $u['id']; ?>" onclick="return confirm('Ban this user?')">🚫 Ban</a>
                    <?php else: ?>
                        <a class="unban-btn" href="?unban=<?php echo $u['id']; ?>">✅ Unban</a>
                    <?php endif; ?>
                    <a class="rp-btn" href="#" onclick="openModal(<?php echo $u['id']; ?>); return false;">🔐 Reset Pass</a>
                    <a class="del-btn" href="?delete=<?php echo $u['id']; ?>" onclick="return confirm('Permanently delete this user and all their data?')">🗑 Delete</a>
                </td>
            </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="8">No users found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<script>
function openModal(uid) {
    document.getElementById('rpUid').value = uid;
    document.getElementById('rpModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('rpModal').style.display = 'none';
}
</script>
<?php include("../footer.php"); ?>
</body>
</html>
