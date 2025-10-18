<?php
session_start();
include 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

  $stmt = $conn->prepare("SELECT id, password FROM members WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
      session_regenerate_id(true);
      $_SESSION["user_id"] = $user_id;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ Email not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title> Login – Greenlife Wellness Center </title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(to right, #509956, #2f6b4a);
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .login-container {
      background: #fff;
      padding: 40px 30px;
      border-radius: 12px;
      width: 380px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
      from { transform: translateY(-30px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    h2 {
      text-align: center;
      color: #509956;
      margin-bottom: 25px;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 12px 0;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #509956;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 10px;
    }

    .footer-links {
      margin-top: 15px;
      text-align: center;
      font-size: 14px;
    }

    .footer-links a {
      color: #509956;
      text-decoration: none;
      margin: 0 5px;
    }

    .footer-links a:hover {
      text-decoration: underline;
    }

    .logo-text {
      text-align: center;
      font-size: 28px;
      font-weight: bold;
      margin-bottom: 10px;
      color: #509956;
    }

    .back-home {
      text-align: center;
      margin-top: 15px;
    }

    .back-home a {
      color: #555;
      text-decoration: none;
      font-size: 14px;
    }

    .error-msg {
      background: #fff0f0;
      color: #a33b3b;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      font-size: 14px;
      text-align: center;
    }
  </style>
</head>
<body>

  <div class="login-container">
  <div class="logo-text">GreenLife</div>
    <h2>Member Login</h2>

    <?php if (!empty($error)) : ?>
      <div class="error-msg"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="email" name="email" placeholder="Email address" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    
    <div class="footer-links">
      <a href="reset-password-direct.php">Forgot Password?</a> /
      <a href="register.html">Register</a>
    </div>

    <div class="back-home">
      <a href="index.html">← Back to Home</a>
    </div>
  </div>

</body>
</html>
