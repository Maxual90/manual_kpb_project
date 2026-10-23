<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$logged_in_admin_no = (int)$_SESSION['admin_no'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: feed.php");
    exit();
}

$token = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $token)
) {
    header("Location: feed.php?error=delete_failed");
    exit();
}

$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

if ($post_id <= 0) {
    header("Location: feed.php?error=delete_failed");
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$select_stmt = $conn->prepare(
    "SELECT image_path FROM posts WHERE id = ? AND admin_no = ?"
);

$select_stmt->bind_param("ii", $post_id, $logged_in_admin_no);
$select_stmt->execute();

$post = $select_stmt->get_result()->fetch_assoc();

$select_stmt->close();

if (!$post) {
    $conn->close();
    header("Location: feed.php?error=delete_failed");
    exit();
}

$delete_stmt = $conn->prepare(
    "DELETE FROM posts WHERE id = ? AND admin_no = ?"
);

$delete_stmt->bind_param("ii", $post_id, $logged_in_admin_no);
$delete_stmt->execute();

$deleted = ($delete_stmt->affected_rows === 1);

$delete_stmt->close();
$conn->close();

if ($deleted && !empty($post['image_path'])) {

    $file = __DIR__ . '/uploads/' . basename($post['image_path']);

    if (is_file($file)) {
        unlink($file);
    }
}

header("Location: feed.php");
exit();
