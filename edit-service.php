<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
  header("Location: admin-services.php");
  exit();
}

// Fetch existing service
$stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();

if (!$service) {
    echo "service not found!";
    exit();
}

$success = $error = "";
$serviceCategories = ["Ayurvedic Therapy", "Yoga and Meditation Classes", "Nutrition and Diet Consultation", "Physiotherapy", "Massage Therapy"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_service'])) {
  // Map form inputs to DB columns
  $services_name = trim($_POST['services_name'] ?? '');
  $category = $_POST['category'] ?? '';
  $description = trim($_POST['description'] ?? '');
  $trainer_name = $_POST['trainer'] ?? '';
  $day = $_POST['day'] ?? '';
  $start = $_POST['start_time'] ?? '';
  $end = $_POST['end_time'] ?? '';
  $location = trim($_POST['location'] ?? '');
  $capacity = (int)($_POST['capacity'] ?? 0);
  $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;

    // Check overlapping with other services (exclude current one)
  $check = $conn->prepare("
    SELECT * FROM services 
    WHERE trainer_name = ? AND day_of_week = ? 
    AND (? < end_time AND ? > start_time)
    AND id != ?
  ");
  $check->bind_param("ssssi", $trainer_name, $day, $start, $end, $id);
    $check->execute();
    $conflict = $check->get_result();

    if ($conflict->num_rows > 0) {
        $error = "❌ Time conflict with another service.";
    } else {
    $update = $conn->prepare("
      UPDATE services 
      SET services_name=?, category=?, description=?, trainer_name=?, day_of_week=?, start_time=?, end_time=?, location=?, max_capacity=?, price=? 
      WHERE id=?
    ");
    $update->bind_param("ssssssssidi", $services_name, $category, $description, $trainer_name, $day, $start, $end, $location, $capacity, $price, $id);

        if ($update->execute()) {
            $success = "✅ service updated successfully!";
            // Reload latest data
      $service = [
        'services_name' => $services_name,
        'category' => $category,
        'description' => $description,
        'trainer_name' => $trainer_name,
        'day_of_week' => $day,
        'start_time' => $start,
        'end_time' => $end,
        'location' => $location,
        'max_capacity' => $capacity,
        'price' => $price
      ];
        } else {
            $error = "❌ Failed to update service.";
        }
        $update->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Class</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script>
    function loadtherapist(category) {
      if (!category) return;
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "get-therapist.php?category=" + encodeURIComponent(category), true);
      xhr.onload = function () {
        if (xhr.status === 200) {
          document.getElementById("therapist-dropdown").innerHTML = xhr.responseText;
          // restore selected trainer
          document.getElementById("therapist-dropdown").value = "<?= htmlspecialchars($service['trainer_name'] ?? '') ?>";
        }
      };
      xhr.send();
    }

    window.onload = function () {
      loadtherapist("<?= htmlspecialchars($service['category'] ?? '') ?>");
    };
  </script>
</head>
<body class="bg-light py-4">
<div class="container">
  <div class="bg-white rounded shadow p-4">
    <h3 class="mb-4 text-danger"><i class="bi bi-pencil-square me-2"></i>Edit Service</h3>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php elseif ($error): ?>
      <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Service Title</label>
          <input type="text" name="services_name" class="form-control" value="<?= htmlspecialchars($service['services_name'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Category</label>
          <select name="category" class="form-select" required onchange="loadtherapist(this.value)">
            <option value="">-- Select Category --</option>
            <?php foreach ($serviceCategories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= ($service['category'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Therapist</label>
          <select name="trainer_name" id="therapist-dropdown" class="form-select" required>
            <option value="<?= htmlspecialchars($service['trainer_name'] ?? '') ?>"><?= htmlspecialchars($service['trainer_name'] ?? '') ?></option>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Day</label>
          <select name="day" class="form-select" required>
            <?php foreach (["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"] as $d): ?>
              <option value="<?= $d ?>" <?= ($service['day_of_week'] ?? '') === $d ? 'selected' : '' ?>><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Start Time</label>
          <input type="time" name="start_time" class="form-control" value="<?= htmlspecialchars($service['start_time'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">End Time</label>
          <input type="time" name="end_time" class="form-control" value="<?= htmlspecialchars($service['end_time'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($service['location'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Max Capacity</label>
          <input type="number" name="max_capacity" class="form-control" value="<?= (int)($service['max_capacity'] ?? 0) ?>" min="1" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Price</label>
          <input type="number" step="0.01" name="price" class="form-control" value="<?= htmlspecialchars($service['price'] ?? '') ?>">
        </div>

        <div class="col-12">
          <button type="submit" name="update_service" class="btn btn-danger w-100">
            <i class="bi bi-check-circle me-2"></i>Update Service
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
</body>
</html>
