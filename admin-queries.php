<?php
include 'db_connect.php';

 
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM queries WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header("Location: admin-queries.php");
    exit();
}

 
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['reply'])) {
    $reply = trim($_POST['admin_reply']);
    $query_id = intval($_POST['query_id']);

    $stmt = $conn->prepare("UPDATE queries SET admin_reply = ?, replied_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $reply, $query_id);
    $stmt->execute();
    header("Location: admin-queries.php");
    exit();
}

 
$result = $conn->query("
    SELECT q.*, m.full_name
    FROM queries q
  LEFT JOIN members m ON q.client_id = m.id
    ORDER BY q.id DESC
");
?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin – Inquiries & Feedback</title>
  <style>
    body {
      font-family: 'Segoe UI';
      padding: 40px;
      background: #f4f4f4;
    }
    .query-box {
      background: white;
      padding: 25px;
      margin-bottom: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      position: relative;
    }
    h2 {
  color: #509956;
      margin-bottom: 30px;
    }
    .meta {
      font-size: 14px;
      color: #555;
      margin-bottom: 10px;
    }
    textarea {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border-radius: 5px;
    }
    button {
      padding: 10px 20px;
  background: #509956;
      color: white;
      border: none;
      border-radius: 5px;
      margin-top: 10px;
    }
    .replied {
      background: #e0fbe0;
      padding: 15px;
      border-radius: 5px;
      margin-top: 10px;
      color: green;
    }
    .delete-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      background: red;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 6px 10px;
      cursor: pointer;
    }
    .delete-btn:hover {
      background: darkred;
    }
  </style>
</head>
<body>

<h2>📩 Member Inquiries & Feedback</h2>

<?php while ($row = $result->fetch_assoc()): ?>
  <div class="query-box">
    <!-- Delete form -->
    <form method="get" onsubmit="return confirm('Are you sure you want to delete this message?');">
      <input type="hidden" name="delete" value="<?= $row['id'] ?>">
      <button class="delete-btn">🗑️ Delete</button>
    </form>

    <div class="meta">
      <strong>Type:</strong> <?= ucfirst($row['category']) ?> |
      <strong>From:</strong> <?= $row['is_anonymous'] ? 'Anonymous' : htmlspecialchars($row['full_name']) ?> |
      <strong>Submitted:</strong> <?= $row['submitted_at'] ?>
    </div>
    <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>

    <?php if ($row['admin_reply']): ?>
      <div class="replied">
        <strong>Replied:</strong> <?= $row['replied_at'] ?><br>
        <?= nl2br(htmlspecialchars($row['admin_reply'])) ?>
      </div>
    <?php else: ?>
      <form method="post">
        <input type="hidden" name="query_id" value="<?= $row['id'] ?>">
        <textarea name="admin_reply" rows="4" placeholder="Write your reply..." required></textarea>
        <button type="submit" name="reply">Send Reply</button>
      </form>
    <?php endif; ?>
  </div>
<?php endwhile; ?>

</body>
</html>
