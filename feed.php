<?php

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get students from database
$sql = "SELECT admin_no, name, batch, course FROM students";
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Alumni Connect Feed</title>

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

/* Header */

header{
    background:#003366;
    color:white;
    padding:20px 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.logo{
    font-size:26px;
    font-weight:bold;
}

/* Switch buttons */

.switch{
    display:flex;
    gap:10px;
}

.switch button{
    padding:10px 25px;
    border:none;
    border-radius:20px;
    cursor:pointer;
    font-size:16px;
}

.feed-btn{
    background:white;
    color:#003366;
}

.message-btn{
    background:#ff9800;
    color:white;
}

/* Main */

.container{
    width:90%;
    max-width:900px;
    margin:40px auto;
}

h2{
    color:#003366;
    margin-bottom:25px;
}

/* User cards */

.user-card{
    background:white;
    padding:25px;
    margin-bottom:20px;
    border-radius:12px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    box-shadow:0 5px 15px rgba(0,0,0,.1);
}

.user-info h3{
    color:#003366;
    margin-bottom:8px;
}

.user-info p{
    color:#666;
}

/* Connect button */

.connect{
    background:#007bff;
    color:white;

    border:none;
    padding:12px 25px;

    border-radius:25px;

    cursor:pointer;

    font-size:15px;

    transition:.3s;
}

.connect:hover{
    background:#0056b3;
}

.connected{
    background:#28a745 !important;
}

</style>

</head>

<body>

<header>

<div class="logo">
🎓 Alumni Connect
</div>

<div class="switch">

<button class="feed-btn">
Feed
</button>

<button class="message-btn">
Message
</button>

</div>

</header>

<div class="container">

<h2>
People You May Connect With
</h2>

<?php

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

?>

<div class="user-card">

    <div class="user-info">

        <h3>
            <?php echo htmlspecialchars($row['name']); ?>
        </h3>

        <p>
            <?php echo htmlspecialchars($row['course']); ?>
            | Batch
            <?php echo htmlspecialchars($row['batch']); ?>
        </p>

    </div>

    <button
        class="connect"
        onclick="connect(this)">
        Connect
    </button>

</div>

<?php

    }

} else {

?>

<p>No students found.</p>

<?php

}

$conn->close();

?>

</div>

<script>

function connect(button){

    if(button.classList.contains("connected"))
    {
        button.classList.remove("connected");
        button.innerHTML="Connect";
    }

    else
    {
        button.classList.add("connected");
        button.innerHTML="✓ request sent";
    }

}

</script>

</body>

</html>
