<?php include("admin_auth.php"); ?>
<?php
$msg = ""; $msgType = "";
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute();
    $msg = "Subject deleted."; $msgType = "error";
}
$subjects = $conn->query("SELECT subjects.*, users.name AS uname FROM subjects JOIN users ON subjects.user_id=users.id ORDER BY subjects.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>All Subjects | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <h1>📚 All Subjects</h1>
    <?php if ($msg): ?><div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
    <div class="table-box">
        <table>
            <tr><th>ID</th><th>Subject</th><th>Priority</th><th>Student</th><th>Created</th><th>Action</th></tr>
            <?php if ($subjects && $subjects->num_rows > 0):
                while ($s = $subjects->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $s['id']; ?></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><span class="priority <?php echo $s['priority']; ?>"><?php echo strtoupper($s['priority']); ?></span></td>
                    <td><?php echo htmlspecialchars($s['uname']); ?></td>
                    <td><?php echo date("d M Y", strtotime($s['created_at'])); ?></td>
                    <td><a href="?delete=<?php echo $s['id']; ?>" onclick="return confirm('Delete this subject?')" style="color:#e74c3c;font-size:13px;">🗑 Delete</a></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="6">No subjects found.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php include("../footer.php"); ?>
</body>
</html>
