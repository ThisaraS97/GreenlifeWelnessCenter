<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

     
    $stmt = $conn->prepare("SELECT id, password FROM clinet WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

     
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

       
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            header("Location: dashboard.php");
            exit();
        } else {
             
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: index.html");
            exit();
        }
    } else {
        
        $_SESSION['login_error'] = "No account found with that email.";
        header("Location: index.html");
        exit();
    }

    $stmt->close();
}
?>
