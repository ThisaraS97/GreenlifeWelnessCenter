<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: index.html");
  exit();
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// Fetch current user info
$stmt = $conn->prepare("SELECT full_name, email, phone FROM members WHERE id = ?");
if (!$stmt) {
  $error = 'Database error: ' . $conn->error;
  $user = ['full_name' => '', 'email' => '', 'phone' => ''];
} else {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();
  $stmt->close();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $new_name = trim($_POST['fullname']);
  $new_email = trim($_POST['email']);
  $new_phone = trim($_POST['phone']);
  $new_password = $_POST['password'];

  // Update password if not empty
  if (!empty($new_password)) {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
  $update = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
    $update->bind_param("ssssi", $new_name, $new_email, $new_phone, $hashed, $user_id);
  } else {
  $update = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $update->bind_param("sssi", $new_name, $new_email, $new_phone, $user_id);
  }

  if ($update->execute()) {
    $success = "✅ Profile updated successfully.";
    $user['full_name'] = $new_name;
    $user['email'] = $new_email;
    $user['phone'] = $new_phone;
  } else {
    $error = "❌ Something went wrong. Try again.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile – FitZone</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: url('images/register-bg.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    .overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }

    .container {
      max-width: 600px;
      margin: 80px auto;
      background: rgba(255,255,255,0.96);
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }

    h2 {
      text-align: center;
  color: #509956;
      margin-bottom: 30px;
    }

    label {
      font-weight: 500;
      display: block;
      margin-bottom: 6px;
    }

    input {
      width: 100%;
      padding: 12px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-bottom: 20px;
    }

    .submit-btn {
  background-color: #509956;
      color: white;
      font-size: 18px;
      font-weight: bold;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .submit-btn:hover {
  background-color: #417243;
    }

    .msg {
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }

    .msg.success { color: green; }
    .msg.error { color: red; }

    .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .back-link a {
  color: #509956;
      text-decoration: none;
      font-weight: bold;
    }

  </style>
</head>
<body>
  <div class="overlay"></div>
  <div class="container">
    <h2>Edit Your Profile</h2>

    <?php if ($success): ?>
      <div class="msg success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="msg error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="fullname">Full Name *</label>
      <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($user['full_name']) ?>" required>

      <label for="email">Email *</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <label for="phone">Phone *</label>
      <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>

      <label for="password">Change Password (leave blank to keep)</label>
      <input type="password" id="password" name="password">

      <button type="submit" class="submit-btn">Update Profile</button>
    </form>

    <div class="back-link">
      <a href="dashboard.php">← Back to Dashboard</a>
    </div>
  </div>
</body>
</html>
