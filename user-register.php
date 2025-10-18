<?php
session_start();
include 'db_connect.php';

$success = "";
$error = "";

$planList = $conn->query("SELECT * FROM plans");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm = $_POST['confirmpassword'];

    if ($password !== $confirm) {
        $error = "❌ Passwords do not match!";
    } else {
  $check = $conn->prepare("SELECT id FROM members WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "❌ Email already exists!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO members (full_name, email, phone, password)
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $fullname, $email, $phone, $hashed);

      if ($stmt->execute()) {
        // registration successful — redirect to login
        header("Location: user-login.php");
        exit();
      } else {
                $error = "❌ Something went wrong. Try again.";
            }

            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register – Greenlife Wellness Center</title>
  <style>
    * { box-sizing: border-box; }
    body, html {
      margin: 0;
      padding: 0;
      font-family: 'Segoe UI', sans-serif;
      height: 100%;
      background: url('images/register-bg.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    .overlay {
      position: absolute;
      top: 0; left: 0;
      height: 100%; width: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }

    .container {
      max-width: 700px;
      margin: 60px auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 0 20px rgba(0,0,0,0.3);
      position: relative;
      z-index: 1;
      animation: slideUp 1s ease-out;
    }

    @keyframes slideUp {
      from { transform: translateY(100px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    h2 {
      text-align: center;
      color: #509956;
      margin-bottom: 30px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #222;
    }

    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border-radius: 5px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    .radio-group {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
    }

    .submit-btn {
      background-color: #509956;
      color: white;
      border: none;
      font-size: 18px;
      padding: 12px 20px;
      width: 100%;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .submit-btn:hover { background-color: #417243; }

    .terms {
      margin-bottom: 20px;
    }

    .terms input {
      width: auto;
      margin-right: 10px;
    }

    .message {
      text-align: center;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .success { color: green; }
    .error { color: red; }

    .plan-info {
      background: #f7f7f7;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .plan-info strong {
      color: #509956;
    }

    @media (max-width: 768px) {
      .container { margin: 30px 20px; padding: 25px; }
    }
  </style>
</head>
<body>

<div class="overlay"></div>

<div class="container">
  <h2>Register for FitZone Membership</h2>

  <?php if ($success): ?>
    <div class="message success"><?php echo $success; ?></div>
  <?php elseif ($error): ?>
    <div class="message error"><?php echo $error; ?></div>
  <?php endif; ?>

  <form action="" method="post">

    <label for="fullname">Full Name *</label>
    <input type="text" id="fullname" name="fullname" required>

    <label for="email">Email Address *</label>
    <input type="email" id="email" name="email" required>

    <label for="phone">Phone Number *</label>
    <input type="tel" id="phone" name="phone" required>

    <label for="password">Create Password *</label>
    <input type="password" id="password" name="password" required>

    <label for="confirmpassword">Confirm Password *</label>
    <input type="password" id="confirmpassword" name="confirmpassword" required>

    <div class="terms">
  <label><input type="checkbox" required> I agree to the <a href="#" style="color: #509956;">Terms & Conditions</a></label>
    </div>

    <button type="submit" class="submit-btn">Register Now</button>
  </form>
</div>

</body>
</html>
