<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$sender_admin_no = $_SESSION['admin_no'];

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: feed.php");
    exit();
}

$receiver_admin_no = intval($_POST['receiver_admin_no']);

if ($sender_admin_no == $receiver_admin_no) {
    header("Location: feed.php");
    exit();
}


// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


// Check whether a connection already exists
$check_sql = "
    SELECT id
    FROM connections
    WHERE
        (
            sender_admin_no = ?
            AND receiver_admin_no = ?
        )
        OR
        (
            sender_admin_no = ?
            AND receiver_admin_no = ?
        )
";

$check_stmt = $conn->prepare($check_sql);

$check_stmt->bind_param(
    "iiii",
    $sender_admin_no,
    $receiver_admin_no,
    $receiver_admin_no,
    $sender_admin_no
);

$check_stmt->execute();

$check_result = $check_stmt->get_result();


if ($check_result->num_rows == 0) {

    $sql = "
        INSERT INTO connections
        (sender_admin_no, receiver_admin_no, status)
        VALUES (?, ?, 'pending')
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "ii",
        $sender_admin_no,
        $receiver_admin_no
    );

    $stmt->execute();

    $stmt->close();
}


$check_stmt->close();
$conn->close();

header("Location: feed.php");
exit();

?>