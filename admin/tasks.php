<?php include("admin_auth.php"); ?>
<?php
$msg = ""; $msgType = "";

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute();
    $msg = "Task deleted."; $msgType = "error";
}

// Filter
$filter_user = (int)($_GET['uid'] ?? 0);
$filter_status = $_GET['status'] ?? '';

$sql = "SELECT tasks.*, users.name AS uname, subjects.name AS sname FROM tasks
        JOIN users ON tasks.user_id = users.id
        JOIN subjects ON tasks.subject_id = subjects.id WHERE 1=1";
$params = []; $types = '';
if ($filter_user) { $sql .= " AND tasks.user_id=?"; $params[] = $filter_user; $types .= 'i'; }
if (in_array($filter_status, ['pending','completed'])) { $sql .= " AND tasks.status=?"; $params[] = $filter_status; $types .= 's'; }
$sql .= " ORDER BY tasks.id DESC";

$stmt = $conn->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$tasks = $stmt->get_result();

$allUsers = $conn->query("SELECT id, name FROM users WHERE is_admin=0 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Tasks | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <h1>📋 All Tasks</h1>
    <?php if ($msg): ?><div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <form method="GET" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <select name="uid" style="padding:10px;border:1.5px solid #e0e0e0;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;">
            <option value="">All Users</option>
            <?php while ($u = $allUsers->fetch_assoc()): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo $filter_user==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
            <?php endwhile; ?>
        </select>
        <select name="status" style="padding:10px;border:1.5px solid #e0e0e0;border-radius:10px;font-family:Poppins,sans-serif;font-size:14px;">
            <option value="">All Status</option>
            <option value="pending" <?php echo $filter_status==='pending'?'selected':''; ?>>Pending</option>
            <option value="completed" <?php echo $filter_status==='completed'?'selected':''; ?>>Completed</option>
        </select>
        <button class="btn" type="submit">Filter</button>
        <a href="tasks.php" class="btn" style="background:#eee;color:#333;">Clear</a>
    </form>

    <div class="table-box">
        <table>
            <tr><th>ID</th><th>Student</th><th>Subject</th><th>Task</th><th>Deadline</th><th>Status</th><th>Action</th></tr>
            <?php if ($tasks && $tasks->num_rows > 0):
                while ($t = $tasks->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $t['id']; ?></td>
                    <td><?php echo htmlspecialchars($t['uname']); ?></td>
                    <td><?php echo htmlspecialchars($t['sname']); ?></td>
                    <td><?php echo htmlspecialchars($t['task_name']); ?></td>
                    <td><?php echo date("d M Y", strtotime($t['deadline'])); ?></td>
                    <td><span class="status <?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                    <td><a href="?delete=<?php echo $t['id']; ?><?php echo $filter_user?"&uid={$filter_user}":""; ?>" onclick="return confirm('Delete task?')" style="color:#e74c3c;font-size:13px;">🗑 Delete</a></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7">No tasks found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php include("../footer.php"); ?>
</body>
</html>
