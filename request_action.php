<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$admin_no = $_SESSION['admin_no'];

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: connection_requests.php");
    exit();
}

$connection_id = intval($_POST['connection_id']);
$action = $_POST['action'];

if ($action != "accept" && $action != "reject") {
    header("Location: connection_requests.php");
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


if ($action == "accept") {

    $status = "accepted";

} else {

    $status = "rejected";

}


/*
    Important:
    Only the receiver of the request can
    accept/reject it.
*/

$sql = "
    UPDATE connections
    SET status = ?
    WHERE id = ?
      AND receiver_admin_no = ?
      AND status = 'pending'
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sii",
    $status,
    $connection_id,
    $admin_no
);

$stmt->execute();

$stmt->close();
$conn->close();

header("Location: connection_requests.php");
exit();

?>