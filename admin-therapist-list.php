<?php
session_start();
include 'db_connect.php';

// Check admin login
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

// Handle therapist addition
if (isset($_POST['add_therapist'])) {
    $full_name = trim($_POST['full_name']);
    $specialty = trim($_POST['specialty']);
    $category = $_POST['category'];
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $password = $_POST['password'] ?? '';
    $bio = trim($_POST['bio']);
    $status = $_POST['status'] ?? 'active';
    
    // Basic validation
    if (empty($full_name) || empty($specialty) || empty($category) || empty($contact) || empty($password)) {
        $message = "❌ Please fill in all required fields (including password).";
        $messageType = 'error';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email address.";
        $messageType = 'error';
    } else {
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $upload_dir = 'uploads/therapists/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('therapist_') . '.' . $file_extension;
                $image_path = $upload_dir . $file_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $image_path = null;
                }
            }
        }
        
    // Hash password before inserting
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert therapist (includes password)
    $sqlInsert = "INSERT INTO therapists (full_name, specialty, category, contact, email, bio, status, password, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sqlInsert);

        if ($stmt === false) {
            // Prepare failed - capture DB error and avoid calling bind_param on bool
            $message = "❌ Database prepare failed: " . htmlspecialchars($conn->error);
            $messageType = 'error';
        } else {
            // Ensure image_path is a string (NULL -> empty string) to match 's' type
            $image_path_bind = $image_path ?? '';
            $stmt->bind_param("sssssssss", $full_name, $specialty, $category, $contact, $email, $bio, $status, $hashed_password, $image_path_bind);

            if ($stmt->execute()) {
                $message = "✅ Therapist \"$full_name\" has been successfully added.";
                $messageType = 'success';
                
                // Clear form data by redirecting
                header("Location: admin-therapist-list.php?success=therapist_added&name=" . urlencode($full_name));
                exit();
            } else {
                $message = "❌ Failed to add therapist. DB error: " . htmlspecialchars($stmt->error);
                $messageType = 'error';
            }
        }
        
    }
}

// Handle success/error messages
if (!isset($message)) {
    $message = '';
    $messageType = '';
}

if (isset($_GET['success'])) {
    $messageType = 'success';
    switch ($_GET['success']) {
        case 'therapist_added':
            $therapistName = $_GET['name'] ?? 'Therapist';
            $message = "✅ Therapist \"$therapistName\" has been successfully added.";
            break;
        case 'therapist_deleted':
            $therapistName = $_GET['name'] ?? 'Therapist';
            $message = "✅ Therapist \"$therapistName\" has been successfully deleted.";
            break;
        case 'therapist_updated':
            $message = "✅ Therapist information updated successfully.";
            break;
        default:
            $message = "✅ Operation completed successfully.";
    }
}

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'invalid_id':
            $message = "❌ Invalid therapist ID provided.";
            break;
        case 'therapist_not_found':
            $message = "❌ Therapist not found.";
            break;
        case 'delete_failed':
            $message = "❌ Failed to delete therapist. Please try again.";
            break;
        case 'database_error':
            $message = "❌ Database error occurred. Please contact administrator.";
            break;
        default:
            $message = "❌ An error occurred. Please try again.";
    }
}

// Categories
$categories = [
    "Ayurvedic Therapy",
    "Yoga and Meditation Classes",
    "Nutrition and Diet Consultation",
    "Physiotherapy",
    "Massage Therapy"
];

// Handle search and filter
$search = $_GET['search'] ?? '';
$filterCategory = $_GET['filter_category'] ?? '';

$sql = "SELECT * FROM therapists WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND (full_name LIKE ? OR specialty LIKE ? OR category LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

if ($filterCategory) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
}

$sql .= " ORDER BY full_name ASC";

$stmt = $conn->prepare($sql);

if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$total_therapists = $conn->query("SELECT COUNT(*) as count FROM therapists")->fetch_assoc()['count'];
$active_therapists = $conn->query("SELECT COUNT(*) as count FROM therapists WHERE status = 'active'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Therapist Management - GreenLife Wellness Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fff 0%, #f8f8f8 50%, #f1f1f1 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(204, 0, 0, 0.3);
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
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 1px solid #c3e6cb;
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

        /* stats-bar removed per request */

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
            background: linear-gradient(135deg, #458a4f, #6ba166);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(80, 154, 87, 0.3);
        }

        .therapist-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 25px; 
        }

        .therapist-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }

        .therapist-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .image-frame { 
            width: 120px; 
            height: 120px; 
            border: 3px solid #509956; 
            border-radius: 50%; 
            overflow: hidden; 
            margin-bottom: 20px;
            position: relative;
        }

        .image-frame img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
        }

        .therapist-card h3 { 
            margin: 10px 0 5px; 
            color: #333; 
            font-size: 20px;
            font-weight: 600;
        }

        .category { 
            font-size: 12px; 
            color: #509956; 
            background: rgba(80, 154, 87, 0.1); 
            padding: 6px 12px; 
            border-radius: 20px; 
            margin-bottom: 15px;
            font-weight: 500;
        }

        .therapist-info {
            flex-grow: 1;
            width: 100%;
        }

        .therapist-info p { 
            font-size: 14px; 
            color: #666; 
            margin: 8px 0; 
            line-height: 1.5;
        }

        .therapist-info .specialty {
            color: #509956;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .therapist-actions { 
            margin-top: 20px;
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .btn-edit, .btn-delete { 
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #458a4f, #6ba166);
            transform: translateY(-2px);
        }

        .btn-delete { 
            background: linear-gradient(135deg, #dc3545, #e85569);
            color: white;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
            transform: translateY(-2px);
        }

        .no-results {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        }

        .no-results h3 {
            color: #509956;
            margin-bottom: 10px;
        }

        .no-results p {
            color: #666;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            background: rgba(0,0,0,0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

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

        .modal h2 { 
            margin-bottom: 20px; 
            color: #509956;
            font-size: 1.5rem;
        }

        .modal p {
            margin-bottom: 20px;
            color: #666;
            line-height: 1.5;
        }

        .modal-buttons { 
            display: flex; 
            justify-content: center;
            gap: 15px;
            margin-top: 25px; 
        }

        .modal-buttons button {
            padding: 12px 25px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .btn-cancel { 
            background: #6c757d; 
            color: white; 
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-confirm-delete { 
            background: #dc3545; 
            color: white; 
        }

        .btn-confirm-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
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
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group .required {
            color: #dc3545;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #509956;
            box-shadow: 0 0 0 3px rgba(80, 154, 87, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input[type="file"] {
            padding: 8px 12px;
            background: #f8f9fa;
        }

        .btn-submit {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #458a4f, #6ba166);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(80, 154, 87, 0.3);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .toggle-form {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .toggle-form:hover {
            background: linear-gradient(135deg, #458a4f, #6ba166);
            transform: translateY(-2px);
        }

        .form-section.hidden {
            display: none;
        }

        @media (max-width: 768px) {
            .therapist-grid {
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

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="header">
        <h1>🧘‍♀️ Therapist Management</h1>
        <p>Manage all wellness center therapists and their specialties</p>
    </div>

    <?php if ($message): ?>
    <div class="message-container">
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <button class="toggle-form" onclick="toggleAddForm()">
        <span id="toggleIcon">➕</span> <span id="toggleText">Add New Therapist</span>
    </button>

    <div class="form-section hidden" id="addTherapistForm">
        <h2>🌱 Add New Therapist</h2>

        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g., Dr. Sarah Johnson" required value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="specialty">Specialty <span class="required">*</span></label>
                    <input type="text" id="specialty" name="specialty" placeholder="e.g., Ayurvedic Medicine, Yoga Instruction" required value="<?= isset($_POST['specialty']) ? htmlspecialchars($_POST['specialty']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($_POST['category']) && $_POST['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number <span class="required">*</span></label>
                    <input type="tel" id="contact" name="contact" placeholder="e.g., +94 77 123 4567" required value="<?= isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="e.g., therapist@greenlife.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password">Create Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" placeholder="Create a password for therapist login" required>
                    <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">Password will be securely hashed. Minimum 8 characters recommended.</small>
                </div>

                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option value="active" <?= (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Profile Image</label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
                    <small style="color: #666; font-size: 12px; margin-top: 5px;">Supported formats: JPG, PNG, GIF (Max 5MB)</small>
                </div>

                <div class="form-group full-width">
                    <label for="bio">Biography/Description</label>
                    <textarea id="bio" name="bio" placeholder="Brief description about the therapist's background, experience, and approach to wellness..."><?= isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : '' ?></textarea>
                </div>
            </div>

            <button type="submit" name="add_therapist" class="btn-submit">
                🌿 Add Therapist
            </button>
        </form>
    </div>

    <!-- stats-bar removed per request -->

    <div class="controls">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="🔍 Search therapists by name, specialty..." value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            
            <select name="filter_category">
                <option value="">-- Filter by Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $filterCategory === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit">Search</button>
            <?php if ($search || $filterCategory): ?>
                <a href="admin-therapist-list.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="therapist-grid">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="therapist-card">
                    <div class="image-frame">
                        <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                            <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['full_name']) ?>">
                        <?php else: ?>
                            <img src="data:image/svg+xml;base64,<?= base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" fill="#f0f0f0"/><circle cx="60" cy="45" r="20" fill="#ccc"/><ellipse cx="60" cy="100" rx="30" ry="25" fill="#ccc"/></svg>') ?>" alt="Default Avatar">
                        <?php endif; ?>
                    </div>
                    
                    <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                    <div class="category"><?= htmlspecialchars($row['category']) ?></div>
                    
                    <div class="therapist-info">
                        <p class="specialty"><strong>Specialty:</strong> <?= htmlspecialchars($row['specialty']) ?></p>
                        <p><strong>📞 Contact:</strong> <?= htmlspecialchars($row['contact']) ?></p>
                        <?php if (!empty($row['bio'])): ?>
                            <p><strong>Bio:</strong> <?= nl2br(htmlspecialchars(substr($row['bio'], 0, 100))) ?><?= strlen($row['bio']) > 100 ? '...' : '' ?></p>
                        <?php endif; ?>
                        <p><strong>Status:</strong> 
                            <span style="color: <?= $row['status'] === 'active' ? '#28a745' : '#6c757d' ?>;">
                                <?= ucfirst($row['status'] ?? 'active') ?>
                            </span>
                        </p>
                    </div>

                    <div class="therapist-actions">
                        <a class="btn-edit" href="admin-edit-therapist.php?id=<?= $row['id'] ?>">✏️ Edit</a>
                        <button class="btn-delete" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['full_name']) ?>">🗑️ Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-results">
                <h3>No Therapists Found</h3>
                <p>
                    <?php if ($search || $filterCategory): ?>
                        No therapists match your search criteria. Try adjusting your filters.
                    <?php else: ?>
                        No therapists have been registered yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h2>⚠️ Confirm Delete</h2>
            <p>Are you sure you want to delete <strong id="therapistName"></strong>?</p>
            <p style="color: #dc3545; font-size: 14px;">This action cannot be undone and will remove all associated data.</p>
            <div class="modal-buttons">
                <button class="btn-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-confirm-delete" id="confirmDeleteBtn">Delete Therapist</button>
            </div>
        </div>
    </div>

    <script>
        const deleteButtons = document.querySelectorAll('.btn-delete');
        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const therapistNameSpan = document.getElementById('therapistName');
        let deleteId = null;
        let therapistName = null;

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                deleteId = btn.getAttribute('data-id');
                therapistName = btn.getAttribute('data-name');
                therapistNameSpan.textContent = therapistName;
                modal.style.display = 'flex';
            });
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            deleteId = null;
            therapistName = null;
        });

        confirmBtn.addEventListener('click', async () => {
            if (deleteId) {
                // Disable button and show loading
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
                
                try {
                    const response = await fetch('admin-delete-therapist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${deleteId}`
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        // Hide modal
                        modal.style.display = 'none';
                        
                        // Show success message
                        showMessage(result.message, 'success');
                        
                        // Remove the therapist card from the DOM
                        const therapistCard = document.querySelector(`[data-id="${deleteId}"]`).closest('.therapist-card');
                        if (therapistCard) {
                            therapistCard.style.animation = 'fadeOut 0.5s ease-out forwards';
                            setTimeout(() => {
                                therapistCard.remove();
                                updateStatistics();
                            }, 500);
                        }
                    } else {
                        showMessage(result.message, 'error');
                    }
                } catch (error) {
                    showMessage('An error occurred while deleting the therapist. Please try again.', 'error');
                } finally {
                    // Reset button
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete Therapist';
                    deleteId = null;
                    therapistName = null;
                }
            }
        });

        window.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
                deleteId = null;
                therapistName = null;
            }
        });

        // Function to show messages dynamically
        function showMessage(message, type) {
            // Remove existing message if any
            const existingMessage = document.querySelector('.message-container');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Create new message
            const messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            messageContainer.innerHTML = `<div class="message ${type}">${message}</div>`;
            
            // Insert after header
            const header = document.querySelector('.header');
            header.insertAdjacentElement('afterend', messageContainer);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (messageContainer.parentNode) {
                    messageContainer.style.animation = 'fadeOut 0.5s ease-out forwards';
                    setTimeout(() => {
                        messageContainer.remove();
                    }, 500);
                }
            }, 5000);
        }

        // Function to update statistics after deletion
        function updateStatistics() {
            // Reload statistics from server
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const newDoc = parser.parseFromString(html, 'text/html');
                    const newStats = newDoc.querySelectorAll('.stat-item h3');
                    const currentStats = document.querySelectorAll('.stat-item h3');
                    
                    newStats.forEach((newStat, index) => {
                        if (currentStats[index]) {
                            currentStats[index].textContent = newStat.textContent;
                        }
                    });
                })
                .catch(error => {
                    console.error('Error updating statistics:', error);
                });
        }

        // CSS for fade out animation
        const style = document.createElement('style');
        style.textContent = `
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

        // Toggle Add Therapist Form
        function toggleAddForm() {
            const form = document.getElementById('addTherapistForm');
            const icon = document.getElementById('toggleIcon');
            const text = document.getElementById('toggleText');
            
            if (form.classList.contains('hidden')) {
                form.classList.remove('hidden');
                form.style.animation = 'slideInDown 0.3s ease-out';
                icon.textContent = '➖';
                text.textContent = 'Hide Form';
            } else {
                form.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    form.classList.add('hidden');
                    icon.textContent = '➕';
                    text.textContent = 'Add New Therapist';
                }, 300);
            }
        }

        // Form validation
        document.querySelector('form[method="post"]').addEventListener('submit', function(e) {
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

            // Validate email if provided
            const email = document.getElementById('email');
            if (email.value && !isValidEmail(email.value)) {
                email.style.borderColor = '#dc3545';
                isValid = false;
            }

            // Validate file size
            const fileInput = document.getElementById('image');
            if (fileInput.files[0] && fileInput.files[0].size > 5 * 1024 * 1024) {
                alert('Profile image must be less than 5MB.');
                fileInput.style.borderColor = '#dc3545';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please fix the highlighted fields before submitting.');
            }
        });

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Real-time email validation
        document.getElementById('email').addEventListener('input', function() {
            if (this.value && !isValidEmail(this.value)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        // File input validation
        document.getElementById('image').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    this.value = '';
                    this.style.borderColor = '#dc3545';
                } else {
                    this.style.borderColor = '#e1e5e9';
                }
            }
        });

        // Show form if there are validation errors (form was submitted)
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($messageType) && $messageType === 'error'): ?>
            toggleAddForm();
        <?php endif; ?>
    </script>

</body>
</html>
