<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard – GreenLife</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      height: 100vh;
      background-color: #f4f4f4;
    }

    .sidebar {
      width: 240px;
      background-color: #000;
      color: #fff;
      padding: 30px 20px;
      height: 100vh;
    }

    .sidebar h2 {
      color: #509956;
      margin-bottom: 40px;
      font-size: 24px;
    }

    .sidebar a {
      display: block;
      color: #fff;
      text-decoration: none;
      margin-bottom: 10px;
      font-size: 16px;
      padding: 8px 10px;
      border-radius: 5px;
      transition: background 0.3s;
    }

    .sidebar a:hover {
      background-color: #509956;
    }

    .main {
      flex: 1;
      padding: 40px;
      overflow-y: auto;
    }

    .main h1 {
      font-size: 28px;
      color: #509956;
      margin-bottom: 20px;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }

    .card {
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .card h3 {
      margin-bottom: 10px;
      font-size: 18px;
    }

    .card a {
      display: inline-block;
      margin-top: 10px;
      padding: 10px 20px;
  background-color: #509956;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-weight: bold;
    }
  </style>
</head>
<body>


<div class="sidebar">
  <h2>GreenLife Admin</h2>
  <a href="admin-dashboard.php"> Dashboard</a>
  <a href="admin-clients.php"> View Clients</a>
  <a href="admin-queries.php"> View Queries</a>
  <a href="admin-therapists.php"> Manage Therapists</a>
  <a href="admin-appointments.php">View Appointments</a>
  <a href="admin-services.php"> Manage Services</a>
  <a href="logout.php"> Logout</a>
</div>

<div class="main">
  <h1>Welcome, Admin 👋</h1>

  <div class="cards">
    <div class="card">
      <h3> Clients</h3>
      <a href="admin-clients.php">Manage</a>
    </div>
    <div class="card">
      <h3> User Queries</h3>
      <a href="admin-queries.php">View</a>
    </div>
    <div class="card">
      <h3> Therapists</h3>
      <a href="admin-therapists.php">Manage</a>
    </div>
    <div class="card">
      <h3>  Appointments</h3>
      <a href="admin-appointments.php">View</a>
    </div>
    <div class="card">
      <h3> Services</h3>
      <a href="admin-services.php">Manage</a>
    </div>
    
  </div>
</div>

</body>
</html>
