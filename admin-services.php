<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

// Handle success/error messages
$message = '';
$messageType = '';

if (isset($_GET['success'])) {
    $messageType = 'success';
    switch ($_GET['success']) {
        case 'service_added':
            $message = "✅ Service has been successfully added.";
            break;
        case 'service_updated':
            $message = "✅ Service information updated successfully.";
            break;
        case 'service_deleted':
            $message = "✅ Service has been successfully deleted.";
            break;
        default:
            $message = "✅ Operation completed successfully.";
    }
}

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'time_conflict':
            $message = "❌ This service time conflicts with an existing session for the selected therapist.";
            break;
        case 'invalid_data':
            $message = "❌ Please fill in all required fields correctly.";
            break;
        case 'database_error':
            $message = "❌ Database error occurred. Please try again.";
            break;
        default:
            $message = "❌ An error occurred. Please try again.";
    }
}

$error = $success = "";
$serviceCategories = [
    "Ayurvedic Therapy", 
    "Yoga and Meditation Classes", 
    "Nutrition and Diet Consultation", 
    "Physiotherapy", 
    "Massage Therapy"
];

// Add service logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service'])) {
    $services_name = trim($_POST['services_name']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $trainer_name = $_POST['trainer_name'];
    $day = $_POST['day'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $location = trim($_POST['location']);
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price'];

    if (empty($services_name) || empty($category) || empty($trainer_name) || empty($day) || empty($start) || empty($end) || empty($location) || $capacity <= 0 || $price <= 0) {
        header("Location: admin-services.php?error=invalid_data");
        exit();
    }

    // Check for time conflicts with the same therapist
    $check = $conn->prepare("
        SELECT * FROM services 
        WHERE trainer_name = ? AND day_of_week = ? 
        AND (
            (? < end_time AND ? > start_time)
        )
    ");
    $check->bind_param("ssss", $trainer_name, $day, $start, $end);
    $check->execute();
    $conflict = $check->get_result();

    if ($conflict->num_rows > 0) {
        header("Location: admin-services.php?error=time_conflict");
        exit();
    } else {
        $stmt = $conn->prepare("INSERT INTO services 
            (services_name, category, description, trainer_name, day_of_week, start_time, end_time, location, max_capacity, price)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssid", $services_name, $category, $description, $trainer_name, $day, $start, $end, $location, $capacity, $price);

        if ($stmt->execute()) {
            header("Location: admin-services.php?success=service_added");
            exit();
        } else {
            header("Location: admin-services.php?error=database_error");
            exit();
        }
    }
}

// Delete service
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: admin-services.php?success=service_deleted");
        exit();
    } else {
        header("Location: admin-services.php?error=database_error");
        exit();
    }
}

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['filter_category'] ?? '';

// Build the query with filters
$sql = "SELECT * FROM services WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $sql .= " AND (services_name LIKE ? OR trainer_name LIKE ? OR location LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= "sss";
}

if ($filterCategory) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$total_services = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
$categories_count = $conn->query("SELECT COUNT(DISTINCT category) as count FROM services")->fetch_assoc()['count'];
$active_therapists = $conn->query("SELECT COUNT(DISTINCT trainer_name) as count FROM services WHERE trainer_name IS NOT NULL AND trainer_name != ''")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management - GreenLife Wellness Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            height: 100vh;
            background-color: #f4f4f4;
        }

        /* Sidebar to match admin-dashboard.php */
        .sidebar {
            width: 240px;
            background-color: #000;
            color: #fff;
            padding: 30px 20px;
            height: 100vh;
            position: sticky;
            top: 0;
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

        .header {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(80, 154, 87, 0.18);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><circle cx="20" cy="10" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="15" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="60" cy="5" r="2.5" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="12" r="2" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }
        
        .header h1 { 
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        .message-container {
            margin: 20px 0;
        }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            animation: slideInDown 0.5s ease-out;
        }

        .message.success {
            background: linear-gradient(135deg, #ffdede, #ffcfcf);
            color: #7a1212;
            border: 1px solid #ffcfcf;
        }

        .message.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            text-align: center;
            min-width: 150px;
            border-left: 4px solid #509956;
            flex: 1;
        }
        
        .stat-item h3 {
            margin: 0;
            color: #509956;
            font-size: 24px;
            font-weight: bold;
        }
        
        .stat-item p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 14px;
        }

        .form-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #509956;
            margin-bottom: 25px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        input, select, textarea {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #509956;
            box-shadow: 0 0 0 3px rgba(80, 154, 87, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #417243, #6aa873);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(80, 154, 87, 0.18);
        }

        .btn-submit {
            width: 100%;
            margin-top: 20px;
        }

        .controls {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .filter-form { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
            flex-wrap: wrap;
        }

        .filter-form input, .filter-form select { 
            padding: 12px 15px; 
            border: 2px solid #e1e5e9;
            border-radius: 10px; 
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-form input:focus, .filter-form select:focus {
            outline: none;
            border-color: #509956;
        }

        .filter-form button { 
            padding: 12px 20px; 
            border-radius: 10px; 
            font-size: 14px; 
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: #fff; 
            border: none; 
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-form button:hover {
            background: linear-gradient(135deg, #417243, #6ba166);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(80, 154, 87, 0.3);
        }

        .services-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .services-container h2 {
            color: #509956;
            margin: 0;
            padding: 25px 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .service-name {
            font-weight: 600;
            color: #333;
        }

        .service-category {
            font-size: 12px;
            background: rgba(80, 154, 87, 0.08);
            color: #509956;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }

        .service-time {
            font-weight: 600;
            color: #666;
        }

        .service-price {
            font-weight: 600;
            color: #509956;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 15px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-results h3 {
            color: #509956;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 700;
            padding: 10px 20px;
            background: linear-gradient(135deg, #509956, #7fbf8b);
            border-radius: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 6px 18px rgba(80,154,87,0.15);
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(80,154,87,0.18);
        }

        .required {
            color: #509956;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-bar {
                flex-direction: column;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-form input, .filter-form select {
                width: 100%;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table-responsive {
                font-size: 12px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>GreenLife Admin</h2>
    <a href="admin-dashboard.php"> Dashboard</a>
    <a href="admin-client-list.php"> View Clients</a>
    <a href="admin-queries.php"> View Queries</a>
    <a href="admin-therapist-list.php"> Manage Therapists</a>
    <a href="admin-appointments.php"> View Appointments</a>
    <!-- Manage Plans removed for this project -->
    <a href="admin-services.php"> Manage Services</a>
    <a href="logout.php"> Logout</a>
</div>

<div class="main">

    <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="header">
        <h1>🌿 Service Management</h1>
        <p>Manage all wellness center services and their schedules</p>
    </div>

    <?php if ($message): ?>
    <div class="message-container">
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="stat-item">
            <h3><?= $total_services ?></h3>
            <p>Total Services</p>
        </div>
        <div class="stat-item">
            <h3><?= $categories_count ?></h3>
            <p>Categories</p>
        </div>
        <div class="stat-item">
            <h3><?= $active_therapists ?></h3>
            <p>Active Therapists</p>
        </div>
    </div>

    <div class="form-section">
        <h2>🌱 Add New Service</h2>

        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label for="services_name">Service Name <span class="required">*</span></label>
                    <input type="text" id="services_name" name="services_name" placeholder="e.g., Morning Yoga Session" required>
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($serviceCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="trainer_name">Therapist/Trainer <span class="required">*</span></label>
                    <select id="trainer_name" name="trainer_name" required>
                        <option value="">-- Select Therapist --</option>
                        <?php
                        $therapists = $conn->query("SELECT id, full_name FROM therapists WHERE status='active' ORDER BY full_name ASC");
                        while($t = $therapists->fetch_assoc()) {
                            echo "<option value='".htmlspecialchars($t['full_name'])."'>".htmlspecialchars($t['full_name'])."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="day">Day of Week <span class="required">*</span></label>
                    <select id="day" name="day" required>
                        <option value="">-- Select Day --</option>
                        <?php 
                        $days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];
                        foreach ($days as $day): 
                        ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_time">Start Time <span class="required">*</span></label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time <span class="required">*</span></label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>

                <div class="form-group">
                    <label for="location">Location <span class="required">*</span></label>
                    <input type="text" id="location" name="location" placeholder="e.g., Yoga Studio A, Therapy Room 1" required>
                </div>

                <div class="form-group">
                    <label for="capacity">Maximum Capacity <span class="required">*</span></label>
                    <input type="number" id="capacity" name="capacity" placeholder="e.g., 15" min="1" max="100" required>
                </div>

                <div class="form-group">
                    <label for="price">Price (Rs.) <span class="required">*</span></label>
                    <input type="number" id="price" name="price" placeholder="e.g., 1500.00" min="0" step="0.01" required>
                </div>

                <div class="form-group full-width">
                    <label for="description">Service Description</label>
                    <textarea id="description" name="description" placeholder="Brief description of the service, benefits, and what clients can expect..."></textarea>
                </div>
            </div>

            <button type="submit" name="add_service" class="btn btn-primary btn-submit">
                🌿 Add Service
            </button>
        </form>
    </div>

    <div class="controls">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="🔍 Search by service name, therapist, or location..." value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            
            <select name="filter_category">
                <option value="">-- Filter by Category --</option>
                <?php foreach ($serviceCategories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Search</button>
            <?php if ($search || $filterCategory): ?>
                <a href="admin-services.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="services-container">
        <h2>🌿 Existing Services</h2>
        
        <div class="table-responsive">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Service Details</th>
                            <th>Therapist</th>
                            <th>Schedule</th>
                            <th>Location</th>
                            <th>Capacity</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="service-name"><?= htmlspecialchars($row['services_name']) ?></div>
                                    <div class="service-category"><?= htmlspecialchars($row['category']) ?></div>
                                    <?php if (!empty($row['description'])): ?>
                                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                            <?= htmlspecialchars(substr($row['description'], 0, 80)) ?><?= strlen($row['description']) > 80 ? '...' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['trainer_name'] ?? 'Not Assigned') ?></td>
                                <td>
                                    <div><strong><?= htmlspecialchars($row['day_of_week']) ?></strong></div>
                                    <div class="service-time">
                                        <?= date('g:i A', strtotime($row['start_time'])) ?> - 
                                        <?= date('g:i A', strtotime($row['end_time'])) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['location']) ?></td>
                                <td><?= (int)$row['max_capacity'] ?> people</td>
                                <td><div class="service-price">Rs. <?= number_format($row['price'] ?? 0, 2) ?></div></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-service.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <button class="btn btn-sm btn-delete" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['services_name']) ?>">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <h3>No Services Found</h3>
                    <p>
                        <?php if ($search || $filterCategory): ?>
                            No services match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No services have been added yet. Create your first service using the form above.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); display: none; justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(5px);">
        <div class="modal" style="background: white; padding: 30px; border-radius: 15px; width: 90%; max-width: 450px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.3); animation: modalSlideIn 0.3s ease-out;">
            <h2 style="margin-bottom: 20px; color: #dc3545; font-size: 1.5rem;">⚠️ Confirm Delete</h2>
            <p style="margin-bottom: 20px; color: #666; line-height: 1.5;">Are you sure you want to delete the service <strong id="serviceName"></strong>?</p>
            <p style="color: #dc3545; font-size: 14px; margin-bottom: 25px;">This action cannot be undone.</p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button class="btn" id="cancelBtn" style="background: #6c757d; color: white; padding: 12px 25px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; min-width: 100px;">Cancel</button>
                <button class="btn" id="confirmDeleteBtn" style="background: #dc3545; color: white; padding: 12px 25px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; min-width: 100px;">Delete Service</button>
            </div>
        </div>
    </div>

    <script>
        const deleteButtons = document.querySelectorAll('.btn-delete');
        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const serviceNameSpan = document.getElementById('serviceName');
        let deleteId = null;

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                deleteId = btn.getAttribute('data-id');
                const serviceName = btn.getAttribute('data-name');
                serviceNameSpan.textContent = serviceName;
                modal.style.display = 'flex';
            });
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            deleteId = null;
        });

        confirmBtn.addEventListener('click', () => {
            if (deleteId) {
                window.location.href = `?delete=${deleteId}`;
            }
        });

        window.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
                deleteId = null;
            }
        });

        // Add CSS for modal animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes modalSlideIn {
                from {
                    opacity: 0;
                    transform: scale(0.8) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: scale(1);
                }
                to {
                    opacity: 0;
                    transform: scale(0.95);
                }
            }
        `;
        document.head.appendChild(style);

        // Auto-hide existing messages
        const existingMessage = document.querySelector('.message');
        if (existingMessage) {
            setTimeout(() => {
                const messageContainer = existingMessage.closest('.message-container');
                if (messageContainer) {
                    messageContainer.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        messageContainer.remove();
                    }, 500);
                }
            }, 5000);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                alert('End time must be later than start time.');
                return;
            }
            
            const requiredFields = this.querySelectorAll('input[required], select[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Price formatting
        document.getElementById('price').addEventListener('input', function() {
            let value = this.value;
            if (value < 0) {
                this.value = 0;
            }
        });

        // Capacity validation
        document.getElementById('capacity').addEventListener('input', function() {
            let value = parseInt(this.value);
            if (value < 1) {
                this.value = 1;
            } else if (value > 100) {
                this.value = 100;
            }
        });
    </script>

</body>
</html>
