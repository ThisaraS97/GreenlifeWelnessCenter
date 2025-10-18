<?php
include 'db_connect.php';

$category = $_GET['category'] ?? '';

if ($category) {
    $stmt = $conn->prepare("SELECT name FROM therapists WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<option value=''>-- Select therapists --</option>";
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . htmlspecialchars($row['name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
    }
}
?>
