<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'db_connect.php'; 

$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Sanitize input
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "❌ Email and password are required.";
    } else {
        // Prepare and execute the SQL query
        $query = "SELECT id, full_name, email, password FROM admins WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Check if admin exists
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();

                // Verify password hash
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['user_name'] = $admin['full_name'];
                    $_SESSION['role'] = 'admin';

                    header("Location: admin-dashboard.php");
                    exit();
                } else {
                    $error = "❌ Incorrect password.";
                }
            } else {
                $error = "❌ Admin not found.";
            }
            $stmt->close();
        } else {
            $error = "❌ Server error. Try again later.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login – GreenLife</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .login-box {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    .login-box h2 {
      margin-bottom: 20px;
      color: #509956;
      text-align: center;
    }
    .login-box input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 16px;
    }
    .login-box button {
      width: 100%;
      padding: 12px;
      background-color: #509956;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      font-weight: bold;
    }
    .error {
      color: red;
      font-weight: bold;
      text-align: center;
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

  <div class="login-box">
    <h2>Admin Login</h2>
    <?php if (!empty($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST" action="admin-login.php">
      <input type="email" name="email" placeholder="Admin Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
  </div>

</body>
</html>
