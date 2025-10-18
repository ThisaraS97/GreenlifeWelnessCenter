<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

$categories = [
  "Ayurvedic Therapy",
  "Yoga and Meditation Classes",
  "Nutrition and Diet Consultation",
  "Physiotherapy",
  "Massage Therapy"
];

$addError = "";
$successMsg = "";

 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_Therapist'])) {
    $full_name = trim($_POST['full_name']);
    $specialty = trim($_POST['specialty']);
    $bio = trim($_POST['bio']);
    $contact = trim($_POST['contact']);
    $category = trim($_POST['category']);

    $imagePath = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $targetDir = "uploads/";
        if (!file_exists($targetDir)) mkdir($targetDir);
        $imagePath = $targetDir . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    }

    $stmt = $conn->prepare("INSERT INTO Therapists (full_name, specialty, bio, image_path, contact, category) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $full_name, $specialty, $bio, $imagePath, $contact, $category);

    if ($stmt->execute()) {
        $successMsg = "✅ Therapist added successfully!";
    } else {
        $addError = "❌ Failed to add Therapist.";
    }

    $stmt->close();
}

// Search & Filter
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['filter_category'] ?? '';

$query = "SELECT * FROM Therapists WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR specialty LIKE ?)";
    $param = "%" . $search . "%";
    $params[] = $param;
    $params[] = $param;
    $types .= 'ss';
}

if (!empty($filterCategory)) {
    $query .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= 's';
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Therapist – Admin</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
      padding: 30px;
    }

    h1 {
      color: #509956;
    }

    form {
      background: #fff;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 1px 5px rgba(0,0,0,0.1);
      margin-bottom: 25px;
    }

    input, textarea, select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }

    button {
      padding: 10px 15px;
      background: #509956;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .message {
      padding: 10px;
      background: #e0ffe0;
      color: green;
      margin-bottom: 15px;
      border-left: 4px solid green;
    }

    .error {
      padding: 10px;
      background: #ffe0e0;
      color: red;
      margin-bottom: 15px;
      border-left: 4px solid red;
    }

    .trainer-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
    }

    .trainer-card {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 1px 6px rgba(0,0,0,0.1);
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      position: relative;
    }

    .trainer-card .image-frame {
      width: 160px;
      height: 160px;
      border: 3px solid #ddd;
      border-radius: 12px;
      overflow: hidden;
      margin-bottom: 15px;
    }

    .trainer-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .trainer-card h3 {
      margin: 10px 0 5px;
      color: #222;
      font-size: 20px;
    }

    .trainer-card .category {
      font-size: 13px;
      color: #666;
      background: #f1f1f1;
      padding: 3px 10px;
      border-radius: 12px;
      margin-bottom: 10px;
    }

    .trainer-card p {
      font-size: 14px;
      color: #555;
      margin: 5px 0;
    }

    .trainer-actions {
      margin-top: 10px;
    }

    .trainer-actions a {
      margin: 0 8px;
      text-decoration: none;
      font-size: 14px;
    }

    .trainer-actions .edit {
      color: blue;
    }

    .trainer-actions .delete {
      color: red;
    }

    .filter-form {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 30px;
    }

    .filter-form input,
    .filter-form select {
      padding: 8px 12px;
      border-radius: 5px;
      font-size: 14px;
    }
  </style>
</head>
<body>

<h1>👨‍🏫 Add New Therapist </h1>

<?php if ($successMsg): ?><div class="message"><?= $successMsg ?></div><?php endif; ?>
<?php if ($addError): ?><div class="error"><?= $addError ?></div><?php endif; ?>

<form method="POST" action="admin-add - Therapist.php" enctype="multipart/form-data">
  <input type="hidden" name="add_Therapist" value="1">
  <label>Therapist Name:</label>
  <input type="text" name="full_name" required>

  <label>Specialty:</label>
  <input type="text" name="specialty" required>

  <label>Bio:</label>
  <textarea name="bio" rows="4" required></textarea>

  <label>Contact Info:</label>
  <input type="text" name="contact" required>

  <label>Category:</label>
  <select name="category" required>
    <option value="">-- Select Category --</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat ?>"><?= $cat ?></option>
    <?php endforeach; ?>
  </select>

  <label>Therapist Image:</label>
  <input type="file" name="image" accept="image/*">

  <button type="submit">➕ Add Therapist</button>
</form>

<hr><br>
<h1>🔍 Search & View Therapist</h1>

<form method="GET" action="admin-Therapists.php" class="filter-form">
  <input type="text" name="search" placeholder="Search Therapists..." value="<?= htmlspecialchars($search) ?>">
  
  <select name="filter_category">
    <option value="">-- Filter by Category --</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
    <?php endforeach; ?>
  </select>
  
  <button type="submit">Search</button>
</form>

<!-- Therapist Display Grid -->
<div class="Therapist-grid">
  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="Therapist-card">
        <div class="image-frame">
          <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Therapist Image">
        </div>
        <h3><?= htmlspecialchars($row['full_name']) ?></h3>
        <div class="category"><?= htmlspecialchars($row['category']) ?></div>
        <p><strong>Specialty:</strong> <?= htmlspecialchars($row['specialty']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($row['contact']) ?></p>
        <p><?= nl2br(htmlspecialchars($row['bio'])) ?></p>

        <div class="Therapist-actions">
          <a class="edit" href="admin-edit-Therapist.php?id=<?= $row['id'] ?>">✏️ Edit</a>
          <a class="delete" href="admin-delete-therapists.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this Therapist?')">🗑️ Delete</a>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <p>No Therapists found.</p>
  <?php endif; ?>
</div>

</body>
</html>
