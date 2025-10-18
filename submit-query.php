<?php
session_start();
include 'db_connect.php';

$category = $_POST['category'];
$message = trim($_POST['message']);
$is_anonymous = isset($_POST['anonymous']) ? 1 : 0;

// If not anonymous, get clinet ID from hidden input or session
$clinet_id = $is_anonymous == true ? $_POST['clinet_id'] : $_SESSION['user_id'];

$stmt = $conn->prepare("INSERT INTO queries (client_id, category, message, is_anonymous) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $clinet_id, $category, $message, $is_anonymous);

if ($stmt->execute()) {
    header("Location: dashboard.php?query=sent");
    exit();
} else {
    echo "❌ Failed to send your message. Please try again.";
}
?>
