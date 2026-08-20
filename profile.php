<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$admin_no = $_SESSION['admin_no'];

$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$sql = "SELECT admin_no, name, email, batch, course FROM students WHERE admin_no = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_no);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    die("User not found.");
}

$student = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Profile | Alumni Connect</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f4f7fb;
}

header{
    background:#003366;
    color:white;
    padding:20px 40px;

    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo{
    font-size:25px;
    font-weight:bold;
}

.nav{
    display:flex;
    gap:10px;
}

.nav a{
    color:white;
    text-decoration:none;
    padding:10px 18px;
    border-radius:6px;
}

.nav a:hover{
    background:#00508f;
}

.container{
    width:90%;
    max-width:700px;
    margin:40px auto;
}

.profile-card{
    background:white;
    border-radius:12px;
    padding:35px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
}

.profile-icon{
    width:80px;
    height:80px;

    background:#003366;
    color:white;

    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:30px;
    font-weight:bold;

    margin:0 auto 20px;
}

h2{
    text-align:center;
    color:#003366;
    margin-bottom:30px;
}

.detail{
    padding:15px 0;
    border-bottom:1px solid #eee;
}

.detail:last-child{
    border-bottom:none;
}

.label{
    color:#777;
    font-size:13px;
    margin-bottom:5px;
}

.value{
    color:#003366;
    font-size:17px;
    font-weight:bold;
}

.logout{
    display:block;
    width:100%;

    margin-top:30px;

    padding:13px;

    background:#dc3545;
    color:white;

    text-align:center;
    text-decoration:none;

    border-radius:6px;

    font-size:16px;
}

.logout:hover{
    background:#b02a37;
}

</style>

</head>

<body>

<header>

<div class="logo">
🎓 Alumni Connect
</div>

<nav class="nav">

<a href="feed.php">Feed</a>

<a href="messages.php">Message</a>

<a href="connection_requests.php">
Connection Requests
</a>

</nav>

</header>


<div class="container">

<div class="profile-card">

<div class="profile-icon">

<?php
echo strtoupper(substr($student['name'], 0, 1));
?>

</div>

<h2>
<?php echo htmlspecialchars($student['name']); ?>
</h2>


<div class="detail">

<div class="label">
Admin Number
</div>

<div class="value">
<?php echo htmlspecialchars($student['admin_no']); ?>
</div>

</div>


<div class="detail">

<div class="label">
Name
</div>

<div class="value">
<?php echo htmlspecialchars($student['name']); ?>
</div>

</div>


<div class="detail">

<div class="label">
Email
</div>

<div class="value">
<?php echo htmlspecialchars($student['email']); ?>
</div>

</div>


<div class="detail">

<div class="label">
Batch
</div>

<div class="value">
<?php echo htmlspecialchars($student['batch']); ?>
</div>

</div>


<div class="detail">

<div class="label">
Course
</div>

<div class="value">
<?php echo htmlspecialchars($student['course']); ?>
</div>

</div>


<a href="logout.php" class="logout">
Logout
</a>

</div>

</div>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>