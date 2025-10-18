<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

// Get therapist ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-therapist-list.php");
    exit();
}

$therapist_id = (int)$_GET['id'];
$success = "";
$error = "";

// Categories
$categories = [
    "Ayurvedic Therapy",
    "Yoga and Meditation Classes",
    "Nutrition and Diet Consultation",
    "Physiotherapy",
    "Massage Therapy"
];

// Fetch therapist details
$stmt = $conn->prepare("SELECT * FROM therapists WHERE id = ?");
$stmt->bind_param("i", $therapist_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin-therapist-list.php");
    exit();
}

$therapist = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $specialty = trim($_POST['specialty']);
    $bio = trim($_POST['bio']);
    $contact = trim($_POST['contact']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    
    // Validate input
    if (empty($full_name) || empty($specialty) || empty($contact) || empty($category)) {
        $error = "❌ Please fill in all required fields.";
    } else {
        // Handle image upload
        $imagePath = $therapist['image_path']; // Keep current image by default
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $targetDir = "uploads/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $allowedTypes = array("jpg", "jpeg", "png", "gif");
            
            if (in_array($imageFileType, $allowedTypes)) {
                $newFileName = uniqid() . '.' . $imageFileType;
                $targetFile = $targetDir . $newFileName;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                    // Delete old image if it exists
                    if (!empty($therapist['image_path']) && file_exists($therapist['image_path'])) {
                        unlink($therapist['image_path']);
                    }
                    $imagePath = $targetFile;
                } else {
                    $error = "❌ Failed to upload image.";
                }
            } else {
                $error = "❌ Only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        if (empty($error)) {
            // Update therapist
            $updateStmt = $conn->prepare("UPDATE therapists SET full_name = ?, specialty = ?, bio = ?, contact = ?, category = ?, image_path = ?, status = ? WHERE id = ?");
            $updateStmt->bind_param("sssssssi", $full_name, $specialty, $bio, $contact, $category, $imagePath, $status, $therapist_id);
            
            if ($updateStmt->execute()) {
                $success = "✅ Therapist information updated successfully!";
                // Refresh therapist data
                $stmt = $conn->prepare("SELECT * FROM therapists WHERE id = ?");
                $stmt->bind_param("i", $therapist_id);
                $stmt->execute();
                $therapist = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "❌ Failed to update therapist information. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Therapist - GreenLife Wellness Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #509956 0%, #7fbf8b 50%, #a8d2a8 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
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
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .header p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .therapist-info {
            background: #f8f9fa;
            padding: 20px 30px;
            border-left: 4px solid #509956;
            margin: 20px 30px;
            border-radius: 8px;
        }

        .therapist-info h3 {
            color: #509956;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .therapist-info p {
            color: #666;
            margin: 5px 0;
        }

        .form-container {
            padding: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            box-shadow: 0 0 0 3px rgba(80, 153, 86, 0.12);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .image-section {
            text-align: center;
            margin: 20px 0;
        }

            .current-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #509956;
            margin-bottom: 15px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            padding: 10px 20px;
            background: linear-gradient(135deg, #509956, #7fbf8b);
            color: white;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            background: linear-gradient(135deg, #448d51, #6fba76);
            transform: translateY(-2px);
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .status-select {
            padding: 12px 15px;
        }

        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }

        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 500;
            text-align: center;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 15px 30px;
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
            background: linear-gradient(135deg, #448d51, #6fba76);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(80, 153, 86, 0.18);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .form-container {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }

        .required {
            color: #dc3545;
        }

        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

            .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #509956;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Edit Therapist</h1>
            <p>Update therapist information and settings</p>
        </div>

        <div class="therapist-info">
            <h3>🧘‍♀️ Current Therapist: <?= htmlspecialchars($therapist['full_name']) ?></h3>
            <p><strong>Therapist ID:</strong> <?= $therapist['id'] ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($therapist['category']) ?></p>
            <p><strong>Current Status:</strong> 
                <span class="status-<?= strtolower($therapist['status'] ?? 'active') ?>">
                    <?= ucfirst($therapist['status'] ?? 'active') ?>
                </span>
            </p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($therapist['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact">Contact Information <span class="required">*</span></label>
                            <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($therapist['contact']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="specialty">Specialty <span class="required">*</span></label>
                            <input type="text" id="specialty" name="specialty" value="<?= htmlspecialchars($therapist['specialty']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="category">Category <span class="required">*</span></label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" 
                                            <?= $therapist['category'] === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="status-select">
                                <option value="active" class="status-active" <?= ($therapist['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>✅ Active</option>
                                <option value="inactive" class="status-inactive" <?= ($therapist['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>❌ Inactive</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="bio">Biography</label>
                            <textarea id="bio" name="bio" placeholder="Enter therapist's bio and qualifications..."><?= htmlspecialchars($therapist['bio']) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Profile Image</div>
                    <div class="image-section">
                        <?php if (!empty($therapist['image_path']) && file_exists($therapist['image_path'])): ?>
                            <img src="<?= htmlspecialchars($therapist['image_path']) ?>" alt="Current Photo" class="current-image">
                        <?php else: ?>
                            <img src="data:image/svg+xml;base64,<?= base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150"><rect width="150" height="150" fill="#f0f0f0" rx="75"/><circle cx="75" cy="60" r="25" fill="#ccc"/><ellipse cx="75" cy="130" rx="40" ry="30" fill="#ccc"/></svg>') ?>" alt="Default Avatar" class="current-image">
                        <?php endif; ?>
                        <br>
                        <div class="file-input-wrapper">
                            📷 Change Photo
                            <input type="file" name="image" accept="image/*">
                        </div>
                        <p style="margin-top: 10px; color: #666; font-size: 0.9rem;">Recommended: Square image, max 2MB</p>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Update Therapist</button>
                    <a href="admin-therapist-list.php" class="btn btn-secondary">🔙 Back to Therapists</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Auto-hide success message after 5 seconds
        const successMessage = document.querySelector('.message.success');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 300);
            }, 5000);
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
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

        // Image preview
        const fileInput = document.querySelector('input[type="file"]');
        const currentImage = document.querySelector('.current-image');

        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Phone number formatting for contact field
        document.getElementById('contact').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            this.value = value;
        });
    </script>
</body>
</html>