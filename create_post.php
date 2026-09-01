<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$logged_in_admin_no = $_SESSION['admin_no'];


// =========================
// ONLY ALLOW POST REQUESTS
// =========================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: feed.php");
    exit();
}


// =========================
// DATABASE CONNECTION
// =========================

$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


// =========================
// READ + VALIDATE CAPTION
// =========================

$caption = trim($_POST['caption'] ?? '');

if ($caption === '' && empty($_FILES['image']['name'])) {
    // Nothing to post at all
    header("Location: feed.php?error=empty");
    exit();
}

if (strlen($caption) > 2000) {
    header("Location: feed.php?error=caption_too_long");
    exit();
}


// =========================
// HANDLE IMAGE UPLOAD (OPTIONAL)
// =========================

$image_path = null;

if (!empty($_FILES['image']['name'])) {

    $file = $_FILES['image'];

    // Basic upload error check
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header("Location: feed.php?error=upload_failed");
        exit();
    }

    // Limit file size to 5MB
    $max_size = 5 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        header("Location: feed.php?error=too_large");
        exit();
    }

    // Verify it's actually an image (not just a renamed file)
    $image_info = getimagesize($file['tmp_name']);

    if ($image_info === false) {
        header("Location: feed.php?error=not_image");
        exit();
    }

    // Only allow specific image types
    $allowed_mime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $mime = $image_info['mime'];

    if (!isset($allowed_mime[$mime])) {
        header("Location: feed.php?error=bad_type");
        exit();
    }

    $extension = $allowed_mime[$mime];

    // Generate a safe, unique filename (never trust the original name)
    $new_filename = 'post_' . $logged_in_admin_no . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;

    $upload_dir = __DIR__ . '/uploads/';

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $destination = $upload_dir . $new_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        header("Location: feed.php?error=upload_failed");
        exit();
    }

    $image_path = 'uploads/' . $new_filename;
}


// =========================
// INSERT POST
// =========================

$sql = "
    INSERT INTO posts (admin_no, content, image_path, created_at)
    VALUES (?, ?, ?, NOW())
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iss",
    $logged_in_admin_no,
    $caption,
    $image_path
);

$stmt->execute();

$stmt->close();
$conn->close();

header("Location: feed.php");
exit();
