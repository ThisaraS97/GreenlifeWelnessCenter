<?php
include 'db_connect.php';

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

     
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format.";
    }
     
    elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match.";
    } else {
         
  $stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE members SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            if ($update->execute()) {
                $success = "✅ Password has been successfully updated.";
            } else {
                $error = "❌ Something went wrong. Please try again.";
            }
        } else {
            $error = "❌ No account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password – FitZone</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f5f5;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .reset-box {
      background-color: #fff;
      padding: 35px 30px;
      border-radius: 10px;
      width: 420px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
      text-align: center;
    }

    .reset-box h2 {
      margin-bottom: 20px;
  color: #509956;
    }

    input {
      width: 100%;
      padding: 14px;
      margin: 10px 0;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      width: 100%;
      padding: 14px;
  background-color: #509956;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
    }

    .message {
      margin-top: 20px;
      padding: 12px;
      border-radius: 6px;
    }

    .success { background: #e0fce0; color: green; }
    .error { background: #fde0e0; color: red; }
  </style>
</head>
<body>

  <div class="reset-box">
    <h2>🔐 Reset Password</h2>

    <?php if ($success): ?>
      <div class="message success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="email" name="email" placeholder="Enter your registered email" required>
      <input type="password" name="password" placeholder="New Password" required>
      <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
      <button type="submit">Update Password</button>
    </form>
  </div>

</body>
</html>
