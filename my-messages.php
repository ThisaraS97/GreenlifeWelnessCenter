<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM queries WHERE id = ? AND clinet_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    header("Location: my-messages.php");
    exit();
}

// Fetch messages with admin replies (using correct column names: admin_reply and replied_at)
$stmt = $conn->prepare("SELECT id, category, message, admin_reply FROM queries WHERE client_id = ? AND admin_reply IS NOT NULL ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
  <title>My Messages – GreenLife</title>
  <style>
    body {
      font-family: 'Segoe UI';
      padding: 40px;
      background-color: #f5f5f5;
    }
    .container {
      max-width: 900px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
      color: #509956;
      margin-bottom: 30px;
    }
    .message-box {
      border-left: 5px solid #509956;
      padding: 20px;
      margin-bottom: 25px;
      background: #fdfdfd;
      border-radius: 8px;
      position: relative;
    }
    .message-box h4 {
      margin: 0 0 10px;
      color: #222;
    }
    .message-box p {
      margin: 5px 0;
    }
    .date {
      font-size: 14px;
      color: #888;
    }
    .delete-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: red;
      color: white;
      border: none;
      padding: 6px 10px;
      border-radius: 4px;
      cursor: pointer;
    }
    .delete-btn:hover {
      background: darkred;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>📬 Messages from Admin</h2>

  <?php if (count($messages)): ?>
    <?php foreach ($messages as $msg): ?>
      <div class="message-box">
        <form method="get">
          <input type="hidden" name="delete" value="<?= $msg['id'] ?>">
          <button class="delete-btn" onclick="return confirm('Delete this message?')">🗑️</button>
        </form>
        <h4>Type: <?= ucfirst($msg['category']) ?></h4>
        <p><strong>Your Message:</strong> <?= htmlspecialchars($msg['message']) ?></p>
        <p><strong>Admin Response:</strong> <?= nl2br(htmlspecialchars($msg['admin_reply'])) ?></p>
        <p class="date">🕒 Replied on: <?= date("F j, Y", strtotime($msg['replied_at'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No messages yet. When admin replies, you’ll see them here. ✅</p>
  <?php endif; ?>
</div>

</body>
</html>
