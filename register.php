<?php

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get values from HTML form
$admin_no = $_POST['admission_number'];
$name = $_POST['name'];
$email = $_POST['email'];
$batch = $_POST['batch'];
$course = $_POST['course'];
$pass = $_POST['password'];

// Insert data into students table
$sql = "INSERT INTO students (admin_no, name, email, batch, course, pass)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isssss",
    $admin_no,
    $name,
    $email,
    $batch,
    $course,
    $pass
);

// Execute query
if ($stmt->execute()) {
    echo "Registration successful!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();

?>
