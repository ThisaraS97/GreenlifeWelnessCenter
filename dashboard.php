<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email, phone FROM members WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// $start_date = new DateTime($user['start_date']);
// $end_date = clone $start_date;
// $end_date->modify("+" . $user['duration'] . " months");

// 🔔 Fetch admin-replied message count (handle if table doesn't exist)
$msg_count = 0;
try {
    $msg_check = $conn->prepare("SELECT COUNT(*) AS total FROM queries WHERE client_id = ? AND admin_reply IS NOT NULL");
    $msg_check->bind_param("i", $user_id);
    $msg_check->execute();
    $msg_count = $msg_check->get_result()->fetch_assoc()['total'];
} catch (mysqli_sql_exception $e) {
    // Queries table doesn't exist yet, set count to 0
    $msg_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard – GreenLife</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
      background: url('images/dashboard-bg.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    .sidebar {
      width: 240px;
      background-color: #000;
      padding: 30px 20px;
      color: #fff;
      display: flex;
      flex-direction: column;
    }

    .sidebar h2 {
      color: #509956;
      margin-bottom: 40px;
      font-size: 24px;
    }

    .sidebar a {
      color: #fff;
      text-decoration: none;
      font-size: 16px;
      margin-bottom: 18px;
      display: block;
      padding: 8px 10px;
      border-radius: 4px;
      transition: background 0.3s;
    }

    .sidebar a:hover {
      background-color: #509956;
    }

    .overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.6);
      z-index: -1;
    }

    .main {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
      background-color: rgba(255, 255, 255, 0.97);
    }

    .main h1 {
      font-size: 32px;
      color: #509956;
      margin-bottom: 30px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
      margin-bottom: 40px;
    }

    .card {
      background: #fff;
      padding: 30px 20px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      transition: transform 0.3s;
    }

    .card:hover {
      transform: scale(1.04);
    }

    .card-icon {
      font-size: 40px;
      color: #509956;
      margin-bottom: 10px;
    }

    .card-title {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .card a, .cancel-btn {
      text-decoration: none;
      background: #509956;
      color: #fff;
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: bold;
      display: inline-block;
      margin-top: 10px;
      border: none;
      cursor: pointer;
    }

    .cancel-btn:disabled {
      background-color: #888;
      cursor: not-allowed;
    }

    .info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
    }

    .info-card {
      background-color: #f9f9f9;
      padding: 10px;
      border-left: 6px solid #509956;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .info-card h3 {
      margin-bottom: 8px;
      color: #222;
    }

    .info-card p {
      margin: 0;
      color: #555;
    }

    .query-form {
      background: #fefefe;
      padding: 30px;
      border-radius: 10px;
      max-width: 600px;
      margin: 60px auto 40px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .query-form h3 {
      margin-bottom: 20px;
      color: #509956;
    }

    .query-form label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
    }

    .query-form select, .query-form textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 16px;
    }

    .query-form input[type="checkbox"] {
      margin-right: 8px;
    }

    .query-form button {
      background-color: #509956;
      color: white;
      border: none;
      padding: 12px 20px;
      font-size: 16px;
      border-radius: 6px;
      cursor: pointer;
      width: 100%;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      body { flex-direction: column; }
      .sidebar {
        width: 100%;
        flex-direction: row;
        justify-content: space-around;
        padding: 20px;
      }
      .sidebar h2 { display: none; }
      .main { padding: 30px 20px; }
    }
  </style>
</head>
<body>

<div class="overlay"></div>

<div class="sidebar">
  <h2>GreenLife</h2>
  <a href="dashboard.php"> Dashboard</a>
  <a href="edit-profile.php"> Edit Profile</a>
  <a href="services.php"> Services</a>
  <a href="my-messages.php"> Messages 
    <?php if ($msg_count > 0): ?>
      <strong style="color:yellow;">(<?= $msg_count ?>)</strong>
    <?php endif; ?>
  </a>
  <a href="logout.php">Logout</a>
</div>

<div class="main">
  <h1>Welcome, <?= htmlspecialchars($user['full_name']) ?> 👋</h1>

  <div class="cards">

    <div class="card">
     <div class="card-title">Edit Profile</div>
      <a href="edit-profile.php">Edit Info</a>
    </div>

    <div class="card">
      <div class="card-title">View Services</div>
      <a href="services.php">Explore</a>
    </div>

    <div class="card">
     <div class="card-title">Therapists</div>
      <a href="therapists.php">Explore</a>
    </div>

    <div class="card">
      <div class="card-title">Book Appointment</div>
      <a href="appointments.php">Book Now</a>
    </div>

    <div class="card">
      <div class="card-title">Logout</div>
      <a href="logout.php">Sign Out</a>
    </div>

    </div>

    <div class="info-grid">
    <div class="info-card"><h3>Email Address</h3><p><?= htmlspecialchars($user['email']) ?></p></div>
    <div class="info-card"><h3>Phone Number</h3><p><?= htmlspecialchars($user['phone']) ?></p></div>
  </div>

  <div class="query-form">
    <h3>Submit Feedback / Inquiry</h3>
    <form action="submit-query.php" method="post">
      <input type="hidden" name="member_id" value="<?= $user_id ?>">
      <label for="category">Select Type</label>
      <select name="category" id="category" required>
        <option value="">-- Choose --</option>
        <option value="feedback">Feedback</option>
        <option value="question">Question</option>
        <option value="inquiry">Inquiry</option>
      </select>

      <label for="message">Your Message</label>
      <textarea name="message" id="message" rows="5" placeholder="Write your message here..." required></textarea>

      <label><input type="checkbox" name="anonymous" value="1"> Submit anonymously</label>

      <button type="submit">Send</button>
    </form>
  </div>
</div>

</body>
</html>
