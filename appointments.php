<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
// Show success after redirect to avoid resubmission
$success = (isset($_GET['success']) && $_GET['success'] == '1') ? true : false;

// ✅ Fetch services from services table
$services = $conn->query("SELECT id, services_name FROM services");

// ✅ Fetch therapists from therapists table
$therapists = $conn->query("SELECT id, full_name FROM therapists");

// Allow preselecting service and therapist when redirected from services page
$pre_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
$pre_therapist_id = isset($_GET['therapist_id']) ? (int)$_GET['therapist_id'] : 0;

// Plans removed for this project

// ✅ Handle booking submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $therapist_id = isset($_POST['therapist_id']) ? (int)$_POST['therapist_id'] : 0;
    // Plans removed from UI — DB requires a non-null plan_id (existing schema).
    // Use a sensible default plan id (1) if none is provided. Adjust later if you change schema.
    $plan_id = isset($_POST['plan_id']) && is_numeric($_POST['plan_id']) ? (int)$_POST['plan_id'] : 1;
    $date = $_POST['date'];
    $time_slot = $_POST['time_slot'];

    // Insert includes plan_id because the current DB schema requires it (NOT NULL + FK).
    $stmt = $conn->prepare("INSERT INTO appointments (client_id, service_id, therapist_id, plan_id, date, time_slot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        // prepare failed - show DB error for debugging and avoid fatal bind_param on bool
        die('Database prepare failed: ' . htmlspecialchars($conn->error));
    }

    // types: client_id (i), service_id (i), therapist_id (i), plan_id (i), date (s), time_slot (s)
    $stmt->bind_param("iiiiss", $user_id, $service_id, $therapist_id, $plan_id, $date, $time_slot);
    if ($stmt->execute()) {
        // Redirect to avoid resubmission and show success state
        header("Location: appointments.php?success=1");
        exit();
    } else {
        // show execute error for debugging (helpful locally); convert to friendly message in production
        die('Database execute failed: ' . htmlspecialchars($stmt->error));
    }
}

// ✅ Fetch member's appointments
$sql = "SELECT a.*, s.services_name, t.full_name AS therapist_name
  FROM appointments a
  JOIN services s ON a.service_id = s.id
  JOIN therapists t ON a.therapist_id = t.id
  WHERE a.client_id = ?
  ORDER BY a.date DESC, a.time_slot";

$appointments = $conn->prepare($sql);
if (!$appointments) {
    die('Database prepare failed: ' . htmlspecialchars($conn->error));
}
$appointments->bind_param("i", $user_id);
$appointments->execute();
$result = $appointments->get_result();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Book Appointment – GreenLife Wellness Center</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f4f4;
      padding: 40px;
    }
    form {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      max-width: 500px;
      margin: auto;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    h2 {
  color: #509956;
      text-align: center;
      margin-bottom: 20px;
    }
    .back-link {
      text-align: left;
      margin: 12px 0 18px 0;
    }

    .back-link a {
      color: #509956;
      text-decoration: none;
      font-weight: bold;
    }
    label {
      display: block;
      margin-top: 15px;
      font-weight: bold;
    }
    select, input[type="date"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }
    button {
      margin-top: 20px;
      padding: 12px;
  background-color: #509956;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      width: 100%;
    }
    .success {
      text-align: center;
      color: green;
      margin-top: 20px;
      font-weight: bold;
    }

    .appointment-list {
      max-width: 950px;
      margin: 50px auto 0;
      background: #fff;
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: left;
    }

    th {
  background-color: #509956;
      color: white;
    }

    td.status-pending {
      color: #ff9500;
      font-weight: bold;
    }
    td.status-confirmed {
      color: green;
      font-weight: bold;
    }
    td.status-completed {
      color: blue;
      font-weight: bold;
    }
    td.status-cancelled {
      color: gray;
      font-weight: bold;
    }
  </style>
</head>
<body>

<!-- 📅 Appointment Booking Form -->
<form method="POST">
  <h2>🗓️ Book a Wellness Appointment</h2>

  <?php if ($success): ?>
    <p class="success">✅ Appointment booked successfully! Await confirmation.</p>
  <?php endif; ?>

  <label for="service_id">Select Service</label>
  <select name="service_id" required>
    <option value="">-- Choose Service --</option>
    <?php while ($row = $services->fetch_assoc()): ?>
      <option value="<?= $row['id'] ?>" <?= ($pre_service_id && $pre_service_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['services_name']) ?></option>
    <?php endwhile; ?>
  </select>

  <label for="therapist_id">Select Therapist</label>
  <select name="therapist_id" required>
    <option value="">-- Choose Therapist --</option>
    <?php while ($row = $therapists->fetch_assoc()): ?>
      <option value="<?= $row['id'] ?>" <?= ($pre_therapist_id && $pre_therapist_id == $row['id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['full_name']) ?></option>
    <?php endwhile; ?>
  </select>

  <!-- Plans removed for this project -->

  <label for="date">Select Date</label>
  <input type="date" name="date" required min="<?= date('Y-m-d') ?>">

  <label for="time_slot">Time Slot</label>
  <select name="time_slot" required>
    <option value="">-- Choose Time Slot --</option>
    <option value="6am - 7am">6am - 7am</option>
    <option value="8am - 9am">8am - 9am</option>
    <option value="5pm - 6pm">5pm - 6pm</option>
    <option value="7pm - 8pm">7pm - 8pm</option>
  </select>

  <input type="submit" value="Book Appointment" style="background-color: #509956; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; width: 100%;">
  <div class="back-link" style="max-width:500px;margin:14px auto 0;text-align:center;">
    <a href="dashboard.php">← Back to Dashboard</a>
  </div>

</form>



<!-- 📋 Display Appointments -->
<?php if ($result->num_rows > 0): ?>
  <div class="appointment-list">
    <h2>📋 Your Appointments</h2>
    <table>
      <tr>
        <th>Service</th>
        <th>Therapist</th>
  <!-- Plan column removed -->
        <th>Date</th>
        <th>Time</th>
        <th>Status</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['services_name']) ?></td>
          <td><?= htmlspecialchars($row['therapist_name']) ?></td>
          <!-- Plan column removed -->
          <td><?= htmlspecialchars($row['date']) ?></td>
          <td><?= htmlspecialchars($row['time_slot']) ?></td>
          <td class="status-<?= strtolower($row['status']) ?>">
            <?= ucfirst($row['status']) ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
<?php endif; ?>

</body>
</html>
