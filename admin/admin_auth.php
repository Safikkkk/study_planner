<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include("../config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php"); exit();
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT is_admin, status FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$u || !$u['is_admin']) {
    header("Location: ../dashboard.php"); exit();
}
if ($u['status'] === 'banned') {
    session_destroy();
    header("Location: ../index.php?e=banned"); exit();
}

$admin_name = $_SESSION['user_name'] ?? 'Admin';
$current_admin_page = basename($_SERVER['PHP_SELF']);
