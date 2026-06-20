<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();
include("config.php");
include("mailer.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$msg = "";
$msgType = "";

// Read flash session message
if (!empty($_SESSION['reminder_msg'])) {
    $msg = $_SESSION['reminder_msg'];
    $msgType = $_SESSION['reminder_msg_type'] ?? 'success';
    unset($_SESSION['reminder_msg'], $_SESSION['reminder_msg_type']);
}

// Fetch user info (name + email) once
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$stmt->close();
$userName = $me['name'] ?? 'Student';
$userEmail = $me['email'] ?? '';

// ── Pending tasks count ────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT COUNT(*) c FROM tasks WHERE user_id=? AND status='pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendingCount = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

// ── Build branded pending-tasks email body ─────────────────────────────────
function buildPendingEmail($conn, $user_id, $userName)
{
    $stmt = $conn->prepare("
        SELECT t.task_name, t.deadline, s.name AS subject_name,
               DATEDIFF(t.deadline, CURDATE()) AS days_left
        FROM tasks t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.user_id=? AND t.status='pending'
        ORDER BY t.deadline ASC LIMIT 25
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $tasks = [];
    while ($r = $res->fetch_assoc())
        $tasks[] = $r;
    $stmt->close();

    $total = count($tasks);
    $overdue = count(array_filter($tasks, fn($t) => (int) $t['days_left'] < 0));

    $rows = '';
    foreach ($tasks as $t) {
        $dl = (int) $t['days_left'];
        if ($dl < 0)
            $badge = "<span style='background:#e74c3c;color:#fff;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;'>Overdue</span>";
        elseif ($dl === 0)
            $badge = "<span style='background:#f39c12;color:#fff;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;'>Due Today</span>";
        elseif ($dl <= 3)
            $badge = "<span style='background:#e67e22;color:#fff;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;'>{$dl}d left</span>";
        else
            $badge = "<span style='background:#27ae60;color:#fff;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600;'>{$dl}d left</span>";
        $rows .= "<tr style='border-bottom:1px solid #f0f0f0;'>
          <td style='padding:11px 10px;font-size:13px;color:#555;'>" . htmlspecialchars($t['subject_name']) . "</td>
          <td style='padding:11px 10px;font-size:13px;color:#333;font-weight:500;'>" . htmlspecialchars($t['task_name']) . "</td>
          <td style='padding:11px 10px;font-size:13px;color:#555;'>" . date("d M Y", strtotime($t['deadline'])) . "</td>
          <td style='padding:11px 10px;text-align:center;'>$badge</td></tr>";
    }
    if (!$rows)
        $rows = "<tr><td colspan='4' style='padding:22px;text-align:center;color:#27ae60;font-weight:600;'>🎉 No pending tasks — you're all caught up!</td></tr>";

    $alert = $overdue > 0
        ? "<div style='background:#fdf0f0;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px;color:#c0392b;'><strong>⚠ $overdue overdue task(s)</strong> — please complete them as soon as possible!</div>"
        : "<div style='background:#eafaf1;border-left:4px solid #27ae60;padding:12px 16px;border-radius:8px;margin-bottom:18px;font-size:14px;color:#27ae60;'><strong>✅ No overdue tasks</strong> — great work staying on track!</div>";

    $year = date("Y");
    $name = htmlspecialchars($userName);
    $date = date("d M Y, h:i A");
    return "
    <div style='font-family:Poppins,Arial,sans-serif;max-width:640px;margin:0 auto;background:#f5f7ff;padding:24px;border-radius:14px;'>
      <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:28px 24px;text-align:center;margin-bottom:22px;'>
        <h1 style='color:#fff;margin:0;font-size:24px;font-weight:700;'>📚 Study Planner</h1>
        <p style='color:rgba(255,255,255,0.85);margin:6px 0 0;font-size:14px;'>Pending Tasks Report &mdash; $date</p>
      </div>
      <div style='background:#fff;border-radius:12px;padding:22px;margin-bottom:16px;box-shadow:0 2px 12px rgba(0,0,0,0.05);'>
        <p style='font-size:16px;margin:0 0 8px;'>Hi <strong>$name</strong> 👋</p>
        <p style='color:#666;font-size:14px;margin:0;'>You have <strong>$total pending task(s)</strong>. Here's your full summary — stay focused!</p>
      </div>
      $alert
      <div style='background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.05);margin-bottom:20px;'>
        <table width='100%' cellspacing='0' cellpadding='0' style='border-collapse:collapse;'>
          <thead>
            <tr style='background:linear-gradient(135deg,#667eea,#764ba2);'>
              <th style='padding:12px 10px;text-align:left;color:#fff;font-size:13px;'>Subject</th>
              <th style='padding:12px 10px;text-align:left;color:#fff;font-size:13px;'>Task</th>
              <th style='padding:12px 10px;text-align:left;color:#fff;font-size:13px;'>Deadline</th>
              <th style='padding:12px 10px;text-align:center;color:#fff;font-size:13px;'>Status</th>
            </tr>
          </thead>
          <tbody>$rows</tbody>
        </table>
      </div>
      <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:18px 22px;text-align:center;margin-bottom:20px;'>
        <p style='color:#fff;font-size:15px;font-style:italic;margin:0;'>&ldquo;Success is the sum of small efforts, repeated day in and day out.&rdquo;</p>
        <p style='color:rgba(255,255,255,0.7);font-size:12px;margin:5px 0 0;'>— Robert Collier</p>
      </div>
      <div style='text-align:center;color:#aaa;font-size:12px;border-top:1px solid #e0e0e0;padding-top:16px;'>
        <p style='margin:0;'>&copy; $year Study Planner System &mdash; All Rights Reserved</p>
        <p style='margin:4px 0 0;'><strong style='color:#667eea;'>Developed by Safik Sherasiya</strong> &mdash; Contributors: Nisar Badi, Azim Kadivar</p>
        <p style='margin:6px 0 0;font-size:11px;'>This email was sent because you requested a pending tasks report.</p>
      </div>
    </div>";
}

// ── SEND PENDING TASKS EMAIL ───────────────────────────────────────────────
if (isset($_POST['send_task_email'])) {
    if (!$userEmail) {
        $_SESSION['reminder_msg'] = "❌ No email on your account. Update it in Profile.";
        $_SESSION['reminder_msg_type'] = "error";
    } else {
        $body = buildPendingEmail($conn, $user_id, $userName);
        $sent = sendMail($conn, $userEmail, $userName, "📚 Your Pending Tasks – Study Planner", $body);
        $_SESSION['reminder_msg'] = $sent
            ? "✅ Pending tasks report emailed to $userEmail!"
            : "❌ Email failed. Ask admin to check SMTP settings in Admin → Settings.";
        $_SESSION['reminder_msg_type'] = $sent ? "success" : "error";
    }
    header("Location: reminders.php");
    exit();
}

// ── ADD REMINDER ───────────────────────────────────────────────────────────
if (isset($_POST['add_reminder'])) {
    $title = trim($_POST['title'] ?? "");
    $time = trim($_POST['reminder_time'] ?? "");
    $sendEmail = isset($_POST['email_confirm']) ? 1 : 0;

    if ($title === "" || $time === "") {
        $msg = "Please fill all fields.";
        $msgType = "error";
    } else {
        $time = str_replace("T", " ", $time) . ":00";
        if (strtotime($time) === false) {
            $msg = "Invalid date and time.";
            $msgType = "error";
        } elseif (strtotime($time) < time()) {
            $msg = "Past date/time is not allowed.";
            $msgType = "error";
        } else {
            $task_id = NULL;
            $stmt = $conn->prepare("INSERT INTO reminders (user_id,title,task_id,reminder_time,status) VALUES (?,?,?,?,'pending')");
            $stmt->bind_param("isis", $user_id, $title, $task_id, $time);
            $stmt->execute();
            $stmt->close();

            // Optional email confirmation
            if ($sendEmail && $userEmail) {
                $html = "
                <div style='font-family:Poppins,Arial,sans-serif;max-width:560px;margin:0 auto;background:#f5f7ff;padding:24px;border-radius:14px;'>
                  <div style='background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;padding:22px;text-align:center;margin-bottom:18px;'>
                    <h1 style='color:#fff;margin:0;font-size:20px;'>⏰ Reminder Set!</h1>
                    <p style='color:rgba(255,255,255,0.85);margin:5px 0 0;font-size:13px;'>Study Planner</p>
                  </div>
                  <div style='background:#fff;border-radius:12px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,0.05);margin-bottom:16px;'>
                    <p style='font-size:15px;margin:0 0 10px;'>Hi <strong>" . htmlspecialchars($userName) . "</strong> 👋</p>
                    <p style='color:#555;font-size:14px;margin:0 0 16px;'>Your reminder has been set successfully.</p>
                    <div style='background:#f0f4ff;border-radius:10px;padding:14px 18px;border-left:4px solid #667eea;'>
                      <p style='margin:0 0 5px;font-size:15px;font-weight:700;color:#333;'>" . htmlspecialchars($title) . "</p>
                      <p style='margin:0;font-size:13px;color:#777;'>📅 " . date("d M Y, h:i A", strtotime($time)) . "</p>
                    </div>
                  </div>
                  <div style='text-align:center;color:#aaa;font-size:12px;border-top:1px solid #e0e0e0;padding-top:14px;'>
                    &copy; " . date("Y") . " Study Planner &mdash;
                    <strong style='color:#667eea;'>Developed by Safik Sherasiya</strong>
                  </div>
                </div>";
                sendMail($conn, $userEmail, $userName, "⏰ Reminder Set: " . htmlspecialchars($title) . " – Study Planner", $html);
            }

            $_SESSION['reminder_msg'] = "✅ Reminder set!" . ($sendEmail && $userEmail ? " Confirmation emailed to $userEmail." : "");
            $_SESSION['reminder_msg_type'] = "success";
            header("Location: reminders.php");
            exit();
        }
    }
}

// ── MARK DONE ──────────────────────────────────────────────────────────────
if (isset($_GET['done'])) {
    $id = (int) $_GET['done'];
    $stmt = $conn->prepare("UPDATE reminders SET status='done' WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: reminders.php");
    exit();
}

// ── DELETE ─────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reminders WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    header("Location: reminders.php");
    exit();
}

// ── FETCH REMINDERS ────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM reminders WHERE user_id=? ORDER BY reminder_time ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reminders = $stmt->get_result();
$now = date("Y-m-d H:i:s");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reminders | Study Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=5">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon.png">
       
</head>

<body class="dashboard-page">
    <?php include("sidebar.php"); ?>

    <div class="main">
        <h1>Reminders ⏰</h1>

        <?php if ($msg !== ""): ?>
            <div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- ── EMAIL PENDING TASKS BANNER ──────────────────────────────────── -->
        <div class="email-banner">
            <div class="eb-text">
                <h3>📧 Email Pending Tasks Report</h3>
                <p>Get a complete list of your pending tasks sent to<br>
                    <strong
                        style="color:#fff;"><?php echo htmlspecialchars($userEmail ?: '— add email in Profile —'); ?></strong>
                </p>
                <span class="eb-badge">
                    <?php echo $pendingCount; ?> pending task<?php echo $pendingCount !== 1 ? 's' : ''; ?> right now
                </span>
            </div>
            <form method="POST">
                <button type="submit" name="send_task_email" class="eb-btn" <?php if (!$userEmail)
                    echo 'disabled title="Add your email in Profile first"'; ?>
                    onclick="return confirm('Send pending tasks report to <?php echo htmlspecialchars($userEmail); ?>?')">
                    📤 Email Me My Tasks
                </button>
            </form>
        </div>

        <!-- ── ADD REMINDER FORM ────────────────────────────────────────────── -->
        <div class="rem-form-card">
            <h3>➕ Set New Reminder</h3>
            <form method="POST">
                <div class="rf-group">
                    <label>Reminder Title</label>
                    <input type="text" name="title" placeholder="Eg: Complete OS Assignment" required>
                </div>
                <div class="rf-group">
                    <label>Reminder Date &amp; Time</label>
                    <input type="datetime-local" name="reminder_time" min="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
                <!-- Email confirmation checkbox -->
                <label class="email-opt" for="emailConfirmCb">
                    <input type="checkbox" name="email_confirm" id="emailConfirmCb" <?php if (!$userEmail)
                        echo 'disabled'; ?>>
                    <span>
                        📧 Send me an <strong>email confirmation</strong> for this reminder
                        <?php if ($userEmail): ?>
                            → <strong><?php echo htmlspecialchars($userEmail); ?></strong>
                        <?php else: ?>
                            <em style="color:#e74c3c;">(add email to your Profile first)</em>
                        <?php endif; ?>
                    </span>
                </label>
                <button class="btn" name="add_reminder" type="submit">Set Reminder</button>
            </form>
        </div>

        <!-- ── REMINDER LIST ────────────────────────────────────────────────── -->
        <div id="reminderList">
            <?php if ($reminders && $reminders->num_rows > 0):
                while ($r = $reminders->fetch_assoc()):
                    $status = $r['status'] ?? 'pending';
                    $isOverdue = ($status === 'pending' && $r['reminder_time'] < $now);
                    $cls = $status === 'done' ? 'is-done' : ($isOverdue ? 'is-overdue' : '');
                    ?>
                    <div class="rem-item <?php echo $cls; ?>" data-title="<?php echo htmlspecialchars($r['title'] ?? ''); ?>"
                        data-time="<?php echo htmlspecialchars($r['reminder_time']); ?>"
                        data-status="<?php echo htmlspecialchars($status); ?>">
                        <div class="rem-left">
                            <strong><?php echo htmlspecialchars($r['title'] ?? 'Reminder'); ?></strong>
                            <small>📅 <?php echo date("d M Y, h:i A", strtotime($r['reminder_time'])); ?></small>
                        </div>
                        <div class="rem-right">
                            <?php if ($status === 'done'): ?>
                                <span class="badge done">✔ Done</span>
                            <?php elseif ($isOverdue): ?>
                                <span class="badge overdue">⚠ Overdue</span>
                            <?php else: ?>
                                <span class="badge pending">🕐 Pending</span>
                            <?php endif; ?>
                            <span class="rem-btns">
                                <?php if ($status !== 'done'): ?>
                                    <a class="doneBtn" href="?done=<?php echo (int) $r['id']; ?>"
                                        onclick="return confirm('Mark as done?')">✔ Done</a>
                                <?php endif; ?>
                                <a class="delBtn" href="?delete=<?php echo (int) $r['id']; ?>"
                                    onclick="return confirm('Delete this reminder?')">🗑 Delete</a>
                            </span>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="empty-state">
                    <p>📭</p>
                    <p>No reminders yet. Set your first one above!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        if ("Notification" in window) Notification.requestPermission();
        let fired = new Set();
        function checkReminders() {
            document.querySelectorAll(".rem-item").forEach(item => {
                if (item.dataset.status !== "pending") return;
                const title = item.dataset.title || "Study Reminder";
                const timeStr = (item.dataset.time || "").replace(" ", "T");
                const t = new Date(timeStr).getTime();
                if (!isNaN(t) && t <= Date.now()) {
                    const key = title + "|" + timeStr;
                    if (fired.has(key)) return;
                    fired.add(key);
                    if (Notification.permission === "granted")
                        new Notification("Study Reminder 🔔", { body: title });
                }
            });
        }
        setInterval(checkReminders, 10000);
        checkReminders();
    </script>
    <?php include("footer.php"); ?>
</body>

</html>