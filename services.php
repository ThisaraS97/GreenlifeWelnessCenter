<?php
session_start();
include 'db_connect.php';

$result = $conn->query("SELECT * FROM services ORDER BY category, services_name");
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[$row['category']][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Services - GreenLife Wellness Center</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #fff;
      color: #333;
    }

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

    .services-hero {
      background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/bham-14-2048x1365.jpg');
      background-size: cover;
      background-position: center;
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .services-hero h1 {
      font-size: 48px;
      margin: 0;
      text-shadow: 2px 2px 6px rgba(0,0,0,0.6);
    }

    .services-hero p {
      font-size: 20px;
      margin: 10px 0 0 0;
    }

    .services-container {
      padding: 60px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .category-section {
      margin-bottom: 60px;
    }

    .category-title {
      font-size: 32px;
  color: #509956;
      text-align: center;
      margin-bottom: 40px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 30px;
      margin-bottom: 40px;
    }

    .service-card {
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 2px solid transparent;
    }

    .service-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 35px rgba(80, 154, 87, 0.2);
  border-color: #509956;
    }

    .service-header {
  background: linear-gradient(135deg, #509956, #7fbf8b);
      color: white;
      padding: 25px;
      text-align: center;
    }

    .service-title {
      font-size: 24px;
      font-weight: bold;
      margin: 0 0 10px 0;
    }

    .service-therapist {
      font-size: 16px;
      opacity: 0.9;
      margin: 0;
    }

    .service-body {
      padding: 25px;
    }

    .service-description {
      font-size: 16px;
      line-height: 1.6;
      margin-bottom: 20px;
      color: #555;
    }

    .service-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
      margin-bottom: 20px;
    }

    .detail-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      color: #666;
    }

    .detail-item i {
  color: #509956;
      font-weight: bold;
    }

    .book-btn {
      width: 100%;
      padding: 12px;
  background-color: #509956;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
      display: block;
      text-align: center;
    }

    .book-btn:hover {
      background-color: #3d7a44;
    }

    .no-services {
      text-align: center;
      padding: 40px;
      color: #666;
      font-size: 18px;
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
      .services-grid {
        grid-template-columns: 1fr;
      }
      
      .services-hero h1 {
        font-size: 36px;
      }
      
      .service-details {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <nav>
    <div class="logo">GreenLife Wellness Center</div>
    <ul>
      <li><a href="index.html">Home</a></li>
      <li><a href="services.php" class="active">Services</a></li>
      <li><a href="therapists.php">Therapists</a></li>
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

  <div class="services-hero">
    <div>
      <h1>Our Wellness Services</h1>
      <p>Discover the path to holistic healing and wellness</p>
    </div>
  </div>

  <div class="services-container">
    <?php if (empty($services)): ?>
      <div class="no-services">
        <h2>Services Coming Soon</h2>
        <p>We're preparing amazing wellness services for you. Please check back soon!</p>
      </div>
    <?php else: ?>
      <?php foreach ($services as $category => $categoryServices): ?>
        <div class="category-section">
          <h2 class="category-title"><?= htmlspecialchars($category) ?></h2>
          <div class="services-grid">
            <?php foreach ($categoryServices as $service): ?>
              <div class="service-card">
                <div class="service-header">
                  <h3 class="service-title"><?= htmlspecialchars($service['services_name']) ?></h3>
                  <p class="service-therapist">with <?= htmlspecialchars($service['trainer_name']) ?></p>
                </div>
                <div class="service-body">
                  <?php if (!empty($service['description'])): ?>
                    <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                  <?php endif; ?>
                  
                  <div class="service-details">
                    <div class="detail-item">
                      <i>📅</i>
                      <span><?= htmlspecialchars($service['day_of_week']) ?></span>
                    </div>
                    <div class="detail-item">
                      <i>🕐</i>
                      <span><?= date('g:i A', strtotime($service['start_time'])) ?> - <?= date('g:i A', strtotime($service['end_time'])) ?></span>
                    </div>
                    <div class="detail-item">
                      <i>📍</i>
                      <span><?= htmlspecialchars($service['location']) ?></span>
                    </div>
                    <div class="detail-item">
                      <i>👥</i>
                      <span>Max <?= (int)$service['max_capacity'] ?> participants</span>
                    </div>
                  </div>
                  
                  <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="appointments.php?service_id=<?= (int)$service['id'] ?><?php if(!empty($service['trainer_id'])) echo '&therapist_id=' . (int)$service['trainer_id']; ?>" class="book-btn">Book This Service</a>
                  <?php else: ?>
                    <a href="user-login.php" class="book-btn">Book This Service</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
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