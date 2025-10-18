<?php
session_start();
include 'db_connect.php';

// Check if therapist is logged in
if (!isset($_SESSION['therapist_id'])) {
    header("Location: therapist-login.php");
    exit();
}

$therapist_id = $_SESSION['therapist_id'];
$therapist_name = $_SESSION['therapist_name'];

// Handle appointment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $conn->prepare("UPDATE appointments SET status = ?, notes = ? WHERE id = ? AND therapist_id = ?");
    $stmt->bind_param("ssii", $new_status, $notes, $appointment_id, $therapist_id);
    
    if ($stmt->execute()) {
        $success_message = "Appointment status updated successfully!";
    } else {
        $error_message = "Error updating appointment status.";
    }
}

// Fetch therapist's appointments
$appointments = $conn->prepare("
    SELECT a.*, 
           c.full_name AS client_name, 
           c.email AS client_email, 
           c.phone AS client_phone,
           s.services_name
    FROM appointments a
    JOIN members c ON a.client_id = c.id
    JOIN services s ON a.service_id = s.id
    -- Plans removed for this project: no JOIN to plans
    WHERE a.therapist_id = ?
    ORDER BY a.date ASC, a.time_slot ASC
");
$appointments->bind_param("i", $therapist_id);
$appointments->execute();
$result = $appointments->get_result();

// Get appointment statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM appointments 
    WHERE therapist_id = ?
");
$stats->bind_param("i", $therapist_id);
$stats->execute();
$stats_result = $stats->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Therapist Dashboard - GreenLife Wellness Center</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #509956, #6fbf6e);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .user-info {
            text-align: right;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border: 1px solid white;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .stat-pending { color: #ff9500; }
    .stat-confirmed { color: #509956; }
        .stat-completed { color: #007bff; }
        .stat-cancelled { color: #dc3545; }
        .stat-total { color: #6c757d; }
        
        .appointments-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .section-header {
            background: #509956;
            color: white;
            padding: 20px;
            margin: 0;
            font-size: 20px;
        }
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        .appointments-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #dee2e6;
        }
        .appointments-table td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-completed { background: #cce7ff; color: #004085; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .action-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .action-form select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .action-form input[type="text"] {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100px;
        }
        .update-btn {
            background: #509956;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .update-btn:hover {
            background: #3d7a43;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .no-appointments {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>🌿 Therapist Dashboard</h1>
            <p>Manage your appointments and client bookings</p>
        </div>
        <div class="user-info">
            <strong><?= htmlspecialchars($therapist_name) ?></strong><br>
            <small><?= htmlspecialchars($_SESSION['therapist_email']) ?></small>
            <br><a href="therapist-logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number stat-total"><?= $stats_result['total_appointments'] ?></div>
            <div>Total Appointments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number stat-pending"><?= $stats_result['pending'] ?></div>
            <div>Pending</div>
        </div>
        <div class="stat-card">
            <div class="stat-number stat-confirmed"><?= $stats_result['confirmed'] ?></div>
            <div>Confirmed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number stat-completed"><?= $stats_result['completed'] ?></div>
            <div>Completed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number stat-cancelled"><?= $stats_result['cancelled'] ?></div>
            <div>Cancelled</div>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="appointments-section">
        <h2 class="section-header">📅 Your Appointments</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['client_name']) ?></strong><br>
                                <small><?= htmlspecialchars($row['client_email']) ?></small><br>
                                <small><?= htmlspecialchars($row['client_phone']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($row['services_name']) ?></td>
                        <!-- Plan column removed for this project -->
                            <td>
                                <strong><?= date('M d, Y', strtotime($row['date'])) ?></strong><br>
                                <small><?= htmlspecialchars($row['time_slot']) ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                    <?= ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= htmlspecialchars($row['notes'] ?: 'No notes') ?></small>
                            </td>
                            <td>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="appointment_id" value="<?= $row['id'] ?>">
                                    <select name="status" required>
                                        <option value="pending" <?= $row['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="confirmed" <?= $row['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                        <option value="completed" <?= $row['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="cancelled" <?= $row['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <input type="text" name="notes" placeholder="Add notes..." 
                                           value="<?= htmlspecialchars($row['notes']) ?>">
                                    <button type="submit" name="update_status" class="update-btn">Update</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-appointments">
                <h3>No Appointments Yet</h3>
                <p>You don't have any appointments scheduled. Clients will be able to book appointments with you through the main booking system.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>