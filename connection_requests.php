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


/*
    Get pending requests received by current user.
*/

$sql = "
    SELECT
        connections.id,
        students.admin_no,
        students.name,
        students.email,
        students.batch,
        students.course

    FROM connections

    INNER JOIN students
        ON connections.sender_admin_no = students.admin_no

    WHERE connections.receiver_admin_no = ?
      AND connections.status = 'pending'

    ORDER BY connections.created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $admin_no);

$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Connection Requests</title>

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
    max-width:800px;
    margin:40px auto;
}

h2{
    color:#003366;
    margin-bottom:25px;
}

.request{
    background:white;
    padding:22px;

    margin-bottom:18px;

    border-radius:12px;

    box-shadow:0 4px 12px rgba(0,0,0,.08);

    display:flex;
    justify-content:space-between;
    align-items:center;
}

.info h3{
    color:#003366;
    margin-bottom:7px;
}

.info p{
    color:#777;
    margin-bottom:4px;
}

.buttons{
    display:flex;
    gap:10px;
}

.accept,
.reject{

    border:none;

    padding:10px 18px;

    border-radius:6px;

    cursor:pointer;

    color:white;
}

.accept{
    background:#28a745;
}

.reject{
    background:#dc3545;
}

.empty{
    background:white;
    padding:40px;
    text-align:center;
    border-radius:12px;
    color:#777;
}

</style>

</head>

<body>

<header>

<div class="logo">
🎓 Alumni Connect
</div>

<nav class="nav">

<a href="feed.php">
Feed
</a>

<a href="messages.php">
Message
</a>

<a href="profile.php">
Profile
</a>

</nav>

</header>


<div class="container">

<h2>
Connection Requests
</h2>


<?php

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

?>

<div class="request">

<div class="info">

<h3>
<?php echo htmlspecialchars($row['name']); ?>
</h3>

<p>
<?php echo htmlspecialchars($row['course']); ?>
</p>

<p>
Batch <?php echo htmlspecialchars($row['batch']); ?>
</p>

</div>


<div class="buttons">

<form action="request_action.php" method="POST">

<input
    type="hidden"
    name="connection_id"
    value="<?php echo $row['id']; ?>"
>

<input
    type="hidden"
    name="action"
    value="accept"
>

<button class="accept">
Accept
</button>

</form>


<form action="request_action.php" method="POST">

<input
    type="hidden"
    name="connection_id"
    value="<?php echo $row['id']; ?>"
>

<input
    type="hidden"
    name="action"
    value="reject"
>

<button class="reject">
Reject
</button>

</form>

</div>

</div>

<?php

    }

} else {

?>

<div class="empty">

No connection requests.

</div>

<?php

}

?>

</div>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>