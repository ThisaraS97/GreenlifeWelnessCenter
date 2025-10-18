<?php
session_start();
include 'db_connect.php';

// Therapist categories
$categories = [
  "Ayurvedic Therapy",
  "Yoga and Meditation Classes", 
  "Nutrition and Diet Consultation",
  "Physiotherapy",
  "Massage Therapy",
];

// Get selected filter
$selected = $_GET['category'] ?? 'all';

// Fetch therapists from DB and group by category so we can display them like services
$therapists = [];
if ($selected && $selected !== 'all') {
  $stmt = $conn->prepare("SELECT * FROM therapists WHERE category = ? ORDER BY full_name ASC");
  $stmt->bind_param("s", $selected);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $cat = $row['category'] ?? 'Uncategorized';
    $therapists[$cat][] = $row;
  }
} else {
  $res = $conn->query("SELECT * FROM therapists ORDER BY category, full_name ASC");
  while ($row = $res->fetch_assoc()) {
    $cat = $row['category'] ?? 'Uncategorized';
    $therapists[$cat][] = $row;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Our Therapists - GreenLife Wellness Center</title>
  <style>
    /* Base and layout */
    body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fff; color: #333; }

    nav {
      background-color: #000;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    nav .logo {
      color: #fff;
      font-size: 28px;
      font-weight: bold;
    }

    nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
    }

    nav ul li a {
      color: #fff;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    nav ul li a:hover {
  color: #509956;
    }

    nav ul li a.active {
  color: #509956;
      font-weight: bold;
    }

    .tagline-banner {
  background-color: #509956;
      color: white;
      text-align: center;
      padding: 20px 10px;
      font-size: 28px;
      font-weight: bold;
      animation: slideIn 1s ease-out, pulse 2s infinite ease-in-out;
      text-transform: uppercase;
      letter-spacing: 2px;
    }

    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.03); }
    }

    .therapists-hero {
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/istockphoto-869062004-612x612.jpg');
      background-size: cover;
      background-position: center;
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .therapists-hero h1 {
      font-size: 48px;
      margin: 0;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }

    .therapists-hero p {
      font-size: 20px;
      margin: 10px 0 0 0;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px 20px;
    }

    .filter-section {
      background-color: #f8f9fa;
      padding: 30px;
      border-radius: 15px;
      margin-bottom: 50px;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .filter-section h3 {
  color: #509956;
      font-size: 24px;
      margin-bottom: 20px;
      font-weight: bold;
    }

    .filter-form select {
      padding: 12px 20px;
      font-size: 16px;
  border: 2px solid #509956;
      border-radius: 25px;
      background-color: white;
      color: #333;
      margin-right: 15px;
      min-width: 200px;
    }

    .filter-form button {
      padding: 12px 25px;
      font-size: 16px;
  background-color: #509956;
      color: white;
      border: none;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .filter-form button:hover {
  background-color: #417243;
    }

    /* Grid / Card styles (matching admin) */
    .therapists-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 22px; margin-bottom: 30px; }

    .therapist-card { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: 22px; display:flex; flex-direction:column; align-items:center; text-align:center; position:relative; transition:all 0.25s ease; border: 1px solid #f0f0f0; }

    .therapist-card:hover { transform: translateY(-6px); box-shadow: 0 10px 30px rgba(0,0,0,0.12); border-color: #e6efe6; }

    .image-frame { width: 120px; height: 120px; border: 3px solid #509956; border-radius: 50%; overflow: hidden; margin-bottom: 16px; }
    .image-frame img { width: 100%; height: 100%; object-fit: cover; display:block; }

    .therapist-card h3 { margin: 6px 0 4px; color: #222; font-size: 20px; font-weight: 600; }
    .category { font-size: 12px; color: #509956; background: rgba(80,154,87,0.08); padding: 6px 12px; border-radius: 20px; margin-bottom: 12px; font-weight:600; }

    .therapist-info { width:100%; flex-grow:1; }
    .therapist-info .specialty { color: #509956; font-weight:600; margin-bottom:8px; }
    .therapist-bio { color:#666; font-size:13px; line-height:1.4; max-height:3.6em; overflow:hidden; margin-bottom:10px; }
    .therapist-contact { font-size:13px; color:#444; background:#f7f9f7; padding:8px; border-radius:8px; border-left:4px solid #509956; }

    .no-therapists {
      text-align: center;
      padding: 60px 20px;
      color: #666;
    }

    .no-therapists h3 {
      font-size: 24px;
  color: #509956;
      margin-bottom: 15px;
    }

    .no-therapists p {
      font-size: 16px;
      margin-bottom: 20px;
    }

    footer {
      background-color: #000;
      color: white;
      padding: 40px 20px 20px 20px;
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
      text-align: center;
    }

    footer h3 {
      margin-bottom: 10px;
      font-size: 24px;
    }

    footer p {
      margin: 5px 0;
    }

    footer a {
      color: #fff;
      text-decoration: underline;
    }

    footer a:hover {
  color: #509956;
    }

    .footer-bottom {
      background-color: #111;
      padding: 10px;
      font-size: 14px;
      color: white;
      text-align: center;
      margin-top: 20px;
    }

    @media (max-width: 768px) {
      .therapists-grid {
        grid-template-columns: 1fr;
      }
      
      .therapists-hero h1 {
        font-size: 36px;
      }
      
      .filter-form select {
        margin-bottom: 15px;
        margin-right: 0;
        width: 100%;
      }
    }
  </style>
</head>
<body>

  <nav>
    <div class="logo">GreenLife Wellness Center</div>
    <ul>
      <li><a href="index.html">Home</a></li>
      <li><a href="services.php">Services</a></li>
      <li><a href="therapists.php" class="active">Therapists</a></li>
  <?php if (!isset($_SESSION['user_id'])): ?>
  <li><a href="register.html">Register</a></li>
  <li><a href="user-login.php">Login</a></li>
  <?php else: ?>
  <li><a href="dashboard.php">Dashboard</a></li>
  <li><a href="logout.php">Logout</a></li>
  <?php endif; ?>
    </ul>
  </nav>

  <div class="tagline-banner">
    Wellness Beyond Treatment
  </div>

  <div class="therapists-hero">
    <div>
      <h1>Meet Our Expert Therapists</h1>
      <p>Healing hands, caring hearts, transformative wellness</p>
    </div>
  </div>

  <div class="container">
    <div class="filter-section">
      <h3>Filter by Specialization</h3>
      <div class="filter-form">
        <form method="GET" action="therapists.php">
          <select name="category">
            <option value="all" <?= ($selected === 'all') ? 'selected' : '' ?>>-- All Specializations --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat) ?>" <?= ($selected === $cat) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Filter Therapists</button>
        </form>
      </div>
    </div>

    <!-- Therapists Grid grouped by category -->
    <?php if (!empty($therapists)): ?>
      <?php foreach ($therapists as $category => $rows): ?>
        <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
        <div class="therapists-grid">
          <?php foreach ($rows as $row): ?>
            <div class="therapist-card">
                <div class="image-frame">
                    <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                        <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['full_name']) ?>">
                    <?php else: ?>
                        <img src="data:image/svg+xml;base64,<?= base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" fill="#f0f0f0"/><circle cx="60" cy="40" r="20" fill="#ccc"/><ellipse cx="60" cy="95" rx="30" ry="20" fill="#ccc"/></svg>') ?>" alt="Default Avatar">
                    <?php endif; ?>
                </div>

                <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                <div class="category"><?= htmlspecialchars($row['category']) ?></div>

                <div class="therapist-info">
                    <?php if (!empty($row['specialty'])): ?>
                        <div class="specialty"><strong>Specialty:</strong> <?= htmlspecialchars($row['specialty']) ?></div>
                    <?php endif; ?>

                    <?php if (!empty($row['bio'])): ?>
                        <div class="therapist-bio"><?= nl2br(htmlspecialchars(substr($row['bio'],0,220))) ?><?= strlen($row['bio']) > 220 ? '...' : '' ?></div>
                    <?php endif; ?>

                    <?php if (!empty($row['contact'])): ?>
                        <div class="therapist-contact"><strong>📞 Contact:</strong> <?= htmlspecialchars($row['contact']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-therapists">
        <h3>No Therapists Found</h3>
        <p>We couldn't find any therapists in the selected category.</p>
        <p>Please try selecting a different specialization or check back later.</p>
      </div>
    <?php endif; ?>
  </div>

  <footer>
    <div style="padding: 20px; background-color: #000; color: white;">
      <h3>Contact Us</h3>
      <p><strong>Address:</strong> No. 24 Siebel Avenue, Kirulapone, Sri Lanka</p>
      <p><strong>Phone:</strong> +94 71 523 7478</p>
      <p><strong>Email:</strong> <a href="mailto:info@GreenLife.lk" style="color: #fff; text-decoration: underline;">info@GreenLife.lk</a></p>
      <div style="margin-top: 15px;">
        <p><a href="admin-login.php" style="color: #509956; font-weight: bold;">🔒 Admin / Staff Login</a></p>
          <p><a href="therapist-login.php" style="color: #509956; font-weight: bold;">🌿 Therapist Portal</a></p>
      </div>
    </div>
    <div style="background-color: #111; padding: 10px; font-size: 14px; color: white; text-align: center;">
      &copy; 2025 GreenLife Wellness Center. All rights reserved.

      
    </div>
  </footer>

</body>
</html>
