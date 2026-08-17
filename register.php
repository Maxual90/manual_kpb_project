<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $conn = mysqli_connect("localhost","root","","firstdb");
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    $name=mysqli_real_escape_string($conn,$_POST['name']);
    $admin_no=mysqli_real_escape_string($conn,$_POST['admission_number']);
    $email=mysqli_real_escape_string($conn,$_POST['email']);
    $batch=mysqli_real_escape_string($conn,$_POST['batch']);
    $course=mysqli_real_escape_string($conn,$_POST['course']);
    $pass=mysqli_real_escape_string($conn,$_POST['password']);
    $sql="INSERT INTO students (name,admin_no,email,batch,course,pass) VALUES('$name','$admin_no','$email','$batch','$course','$pass')";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('successfully added new user');</script>";
    } else {
        echo "<script>alert('oops something went wrong: " . $conn->error . "');</script>";
    }
}
?>
