<?php include("admin_auth.php"); include("../mailer.php"); ?>
<?php
$msg = ""; $msgType = "";

// Mark read
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $conn->prepare("UPDATE contact_messages SET status='read' WHERE id=?")->execute() ||
    $conn->query("UPDATE contact_messages SET status='read' WHERE id=$id");
    $stmt = $conn->prepare("UPDATE contact_messages SET status='read' WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute();
    header("Location: messages.php"); exit();
}
// Delete
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
    $stmt->bind_param("i",$id); $stmt->execute();
    $msg = "Message deleted."; $msgType = "error";
}

// View single + reply
$viewMsg = null;
if (isset($_GET['view'])) {
    $id = (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT * FROM contact_messages WHERE id=? LIMIT 1");
    $stmt->bind_param("i",$id); $stmt->execute();
    $viewMsg = $stmt->get_result()->fetch_assoc();
    if ($viewMsg && $viewMsg['status'] === 'unread') {
        $conn->query("UPDATE contact_messages SET status='read' WHERE id=$id");
        $viewMsg['status'] = 'read';
    }
}

// Send reply
if (isset($_POST['send_reply']) && $viewMsg) {
    $replyText = trim($_POST['reply_text'] ?? '');
    if ($replyText !== '') {
        $html = "<div style='font-family:Poppins,sans-serif;max-width:600px;margin:0 auto;background:#f5f7ff;padding:30px;border-radius:14px;'>
          <div style='background:linear-gradient(135deg,#667eea,#764ba2);padding:20px;border-radius:10px;'>
            <h2 style='color:#fff;margin:0;'>Study Planner – Reply</h2>
          </div>
          <div style='padding:24px 0;'>
            <p>Hi <strong>" . htmlspecialchars($viewMsg['name']) . "</strong>,</p>
            <p>Regarding your message about <em>" . htmlspecialchars($viewMsg['subject']) . "</em>:</p>
            <div style='background:#fff;padding:16px;border-radius:8px;border:1px solid #e0e0e0;'>
              <p style='margin:0;'>" . nl2br(htmlspecialchars($replyText)) . "</p>
            </div>
            <p style='color:#888;font-size:13px;margin-top:20px;'>Team Study Planner</p>
          </div>
          <div style='text-align:center;color:#aaa;font-size:12px;'>&copy; " . date("Y") . " Study Planner</div>
        </div>";
        sendMail($conn, $viewMsg['email'], $viewMsg['name'], "Re: " . $viewMsg['subject'], $html);
        $conn->query("UPDATE contact_messages SET status='replied' WHERE id=" . (int)$viewMsg['id']);
        $viewMsg['status'] = 'replied';
        $msg = "Reply sent to " . htmlspecialchars($viewMsg['email']); $msgType = "success";
    }
}

$messages = $conn->query("SELECT * FROM contact_messages ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/style.css">
<link rel="icon" type="image/png" href="../images/favicon.png">
<style>
.msg-panel { display:grid; grid-template-columns:320px 1fr; gap:20px; }
.msg-list-panel { background:#fff; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,0.06); overflow:hidden; }
.msg-item { padding:14px 16px; border-bottom:1px solid #f0f0f0; cursor:pointer; transition:background 0.2s; }
.msg-item:hover { background:#f5f7ff; }
.msg-item.active { background:#eef1ff; border-left:3px solid #667eea; }
.msg-item.unread { font-weight:600; }
.msg-item h4 { margin:0 0 4px; font-size:14px; }
.msg-item p  { margin:0; font-size:12px; color:#888; }
.msg-detail { background:#fff; border-radius:16px; padding:28px; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
.msg-detail h3 { font-size:18px; margin:0 0 6px; }
.msg-detail .meta { color:#888; font-size:13px; margin-bottom:18px; }
.msg-detail .body-text { background:#f5f7ff; border-radius:12px; padding:18px; line-height:1.7; font-size:15px; color:#444; margin-bottom:20px; }
.reply-box textarea { width:100%; box-sizing:border-box; padding:14px; border:1.5px solid #e0e0e0; border-radius:10px; font-family:Poppins,sans-serif; font-size:14px; min-height:100px; resize:vertical; }
.reply-box textarea:focus { border-color:#667eea; outline:none; }
@media(max-width:700px) { .msg-panel { grid-template-columns:1fr; } }
body.dark .msg-list-panel, body.dark .msg-detail { background:#2a2a3e; }
body.dark .msg-item:hover, body.dark .msg-item.active { background:#32325a; }
</style>
</head>
<body class="dashboard-page">
<?php include("sidebar.php"); ?>
<div class="main">
    <h1>📬 Contact Messages</h1>
    <?php if ($msg): ?><div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

    <div class="msg-panel">
        <!-- LIST -->
        <div class="msg-list-panel">
            <?php if ($messages && $messages->num_rows > 0):
                while ($m = $messages->fetch_assoc()):
                $isActive = $viewMsg && $viewMsg['id'] == $m['id'];
            ?>
                <div class="msg-item <?php echo $isActive?'active':''; ?> <?php echo $m['status']==='unread'?'unread':''; ?>">
                    <a href="?view=<?php echo $m['id']; ?>" style="text-decoration:none;color:inherit;">
                        <h4><?php echo htmlspecialchars($m['name']); ?>
                            <?php if ($m['status']==='unread'): ?><span style="color:#e74c3c;font-size:11px;"> ● new</span><?php endif; ?>
                        </h4>
                        <p><?php echo htmlspecialchars(substr($m['subject'],0,40)); ?></p>
                        <p><?php echo date("d M Y", strtotime($m['created_at'])); ?></p>
                    </a>
                </div>
            <?php endwhile; else: ?>
                <div class="msg-item"><p>No messages yet.</p></div>
            <?php endif; ?>
        </div>

        <!-- DETAIL -->
        <div class="msg-detail">
            <?php if ($viewMsg): ?>
                <h3><?php echo htmlspecialchars($viewMsg['subject']); ?></h3>
                <div class="meta">
                    From: <strong><?php echo htmlspecialchars($viewMsg['name']); ?></strong>
                    &lt;<?php echo htmlspecialchars($viewMsg['email']); ?>&gt; &nbsp;|&nbsp;
                    <?php echo date("d M Y, h:i A", strtotime($viewMsg['created_at'])); ?> &nbsp;|&nbsp;
                    <span class="status <?php echo $viewMsg['status']==='unread'?'pending':($viewMsg['status']==='replied'?'completed':''); ?>"><?php echo ucfirst($viewMsg['status']); ?></span>
                </div>
                <div class="body-text"><?php echo nl2br(htmlspecialchars($viewMsg['message'])); ?></div>

                <form method="POST" class="reply-box">
                    <label style="font-weight:600;font-size:14px;display:block;margin-bottom:8px;">✉ Send Reply</label>
                    <textarea name="reply_text" placeholder="Type your reply here..." required></textarea>
                    <div style="display:flex;gap:10px;margin-top:12px;">
                        <button class="btn" name="send_reply">Send Reply</button>
                        <a href="?del=<?php echo $viewMsg['id']; ?>" onclick="return confirm('Delete this message?')" class="btn" style="background:#e74c3c;">🗑 Delete</a>
                        <a href="messages.php" class="btn" style="background:#eee;color:#333;">← Back</a>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align:center;padding:60px 20px;color:#aaa;">
                    <p style="font-size:40px;">📭</p>
                    <p>Select a message to view and reply.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include("../footer.php"); ?>
</body>
</html>
