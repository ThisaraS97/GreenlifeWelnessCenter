<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid Therapists ID.");
}

$categories = [
    "Ayurvedic Therapy",
    "Yoga and Meditation Classes",
    "Nutrition and Diet Consultation",
    "Physiotherapy",
    "Massage Therapy"
];

 
$stmt = $conn->prepare("SELECT * FROM Therapists WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$Therapists = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $specialty = trim($_POST['specialty']);
    $bio = trim($_POST['bio']);
    $contact = trim($_POST['contact']);
    $category = trim($_POST['category']);

     
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir);
        $imagePath = $targetDir . basename($_FILES["image"]["full_name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    } else {
        $imagePath = $Therapists['image_path'];  
    }

     
    $update = $conn->prepare("UPDATE Therapists SET full_name=?, specialty=?, bio=?, contact=?, category=?, image_path=? WHERE id=?");
    $update->bind_param("ssssssi", $full_name, $specialty, $bio, $contact, $category, $imagePath, $id);

    if ($update->execute()) {
        header("Location: admin-therapist-list.php");
        exit();
    } else {
        echo "❌ Update failed.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Edit Therapist – GreenLife Admin</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 40px;
    }

    .container {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      text-align: center;
      color: #d60000;
      margin-bottom: 25px;
    }

    label {
      font-weight: 600;
      display: block;
      margin-bottom: 8px;
      color: #444;
    }

    input[type="text"],
    textarea,
    select {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 15px;
    }

    textarea {
      resize: vertical;
      height: 100px;
    }

    .image-preview {
      margin-bottom: 20px;
      text-align: center;
    }

    .image-preview img {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border: 3px solid #ddd;
      border-radius: 10px;
    }

    .btn-submit {
      display: block;
      width: 100%;
  background-color: #509956;
      color: white;
      border: none;
      padding: 12px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .btn-submit:hover {
  background-color: #509956;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>✏️ Edit Therapist Details</h2>

  <form method="POST" enctype="multipart/form-data">

    <label for="name">Therapist Name:</label>
    <input type="text" name="name" id="name" value="<?= htmlspecialchars($Therapists['full_name']) ?>" required>

    <label for="specialty">Specialty:</label>
    <input type="text" name="specialty" id="specialty" value="<?= htmlspecialchars($Therapists['specialty']) ?>" required>

    <label for="bio">Bio:</label>
    <textarea name="bio" id="bio"><?= htmlspecialchars($Therapists['bio']) ?></textarea>

    <label for="contact">Contact Info:</label>
    <input type="text" name="contact" id="contact" value="<?= htmlspecialchars($Therapists['contact']) ?>" required>

    <label for="category">Category:</label>
    <select name="category" id="category" required>
      <option value="">-- Select Category --</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= $cat ?>" <?= $cat === $Therapists['category'] ? 'selected' : '' ?>><?= $cat ?></option>
      <?php endforeach; ?>
    </select>

    <label>Current Therapist Image:</label>
    <div class="image-preview">
      <img src="<?= htmlspecialchars($Therapists['image_path']) ?>" alt="Therapist Image">
    </div>

    <label for="image">Change Image (optional):</label>
    <input type="file" name="image" id="image" accept="image/*">

    <button type="submit" class="btn-submit">💾 Save Changes</button>
  </form>
</div>

</body>
</html>
