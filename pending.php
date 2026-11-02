<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "firstdb");

$stmt = $conn->prepare("SELECT status FROM students WHERE admin_no = ?");
$stmt->bind_param("i", $_SESSION['admin_no']);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if ($row && $row['status'] === 'approved') {
    header("Location: feed.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="30">
<title>Approval Pending</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Arial,Helvetica,sans-serif;}
body{background:linear-gradient(rgba(0,51,102,.8),rgba(0,51,102,.8)),url("https://images.unsplash.com/photo-1523050854058-8df90110c9f1");background-size:cover;background-position:center;min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px;}
.box{width:420px;max-width:100%;background:#fff;padding:40px 35px;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.3);text-align:center;}
.box h2{color:#003366;margin-bottom:12px;}
.box p{color:#555;line-height:1.6;}
.logout{display:inline-block;margin-top:22px;padding:12px 28px;background:#ff9800;color:#fff;text-decoration:none;border-radius:5px;}
.logout:hover{background:#e68900;}
</style>
</head>
<body>
<div class="box">
    <h2>Your approval is pending</h2>
    <p>Your account is waiting for admin approval. This page refreshes automatically.</p>
    <a href="logout.php" class="logout">Logout</a>
</div>
</body>
</html>
