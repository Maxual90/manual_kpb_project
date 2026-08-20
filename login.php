<?php

session_start();

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

// Check that the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST["email"];
    $pass = $_POST["pass"];

    // Check if user exists
    $sql = "SELECT * FROM students WHERE email = ? AND pass = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $pass);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        // Get user data FIRST
        $student = $result->fetch_assoc();

        // Store user information in session
        $_SESSION['admin_no'] = $student['admin_no'];
        $_SESSION['name'] = $student['name'];
        $_SESSION['email'] = $student['email'];

        // NOW redirect
        header("Location: feed.php");
        exit();

    } else {

        echo "<script>
                alert('Invalid username or password');
                window.location.href='login.html';
              </script>";
    }

    $stmt->close();
}

$conn->close();

?>