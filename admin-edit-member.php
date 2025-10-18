<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get member ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin-client-list.php");
    exit();
}

$member_id = (int)$_GET['id'];
$success = "";
$error = "";

// Fetch member details
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin-client-list.php");
    exit();
}

$member = $result->fetch_assoc();

// Plans removed for this project: no longer fetching plan list

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    // Plans removed: ignore plan and start_date inputs
    $start_date = $_POST['start_date'] ?? '';
    $membership_status = $_POST['membership_status'];
    $new_password = $_POST['password'];
    
    // Validate input
    if (empty($full_name) || empty($email) || empty($phone) || empty($plan)) {
        $error = "❌ Please fill in all required fields.";
    } else {
        // Check if email is already taken by another member
        $emailCheck = $conn->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $emailCheck->bind_param("si", $email, $member_id);
        $emailCheck->execute();
        
        if ($emailCheck->get_result()->num_rows > 0) {
            $error = "❌ Email address is already registered to another member.";
        } else {
            // Plans removed: don't lookup duration
            $duration = '';
            
            // Update member with or without password
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, dob = ?, gender = ?, membership_status = ?, password = ? WHERE id = ?");
                $updateStmt->bind_param("sssssssi", $full_name, $email, $phone, $dob, $gender, $membership_status, $hashed_password, $member_id);
            } else {
                $updateStmt = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, dob = ?, gender = ?, membership_status = ? WHERE id = ?");
                $updateStmt->bind_param("ssssssi", $full_name, $email, $phone, $dob, $gender, $membership_status, $member_id);
            }
            
            if ($updateStmt->execute()) {
                $success = "✅ Member information updated successfully!";
                // Refresh member data
                $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
                $stmt->bind_param("i", $member_id);
                $stmt->execute();
                $member = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "❌ Failed to update member information. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
                $new_password = $_POST['password'];
    
                // Validate input
                if (empty($full_name) || empty($email) || empty($phone)) {
                    $error = "❌ Please fill in all required fields.";
                } else {
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

        .member-info {
            background: #f8f9fa;
            padding: 20px 30px;
            border-left: 4px solid #509956;
            margin: 20px 30px;
            border-radius: 8px;
        }

        .member-info h3 {
            color: #509956;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .member-info p {
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

        .status-select {
            padding: 12px 15px;
        }

        .status-active { color: #28a745; }
        .status-pending { color: #ffc107; }
        .status-cancelled { color: #dc3545; }

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
+
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

        .password-note {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
            margin-top: 5px;
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
            <h1>✏️ Edit Member</h1>
            <p>Update member information and settings</p>
        </div>

        <div class="member-info">
            <h3>👤 Current Member: <?= htmlspecialchars($member['full_name']) ?></h3>
            <p><strong>Member ID:</strong> <?= $member['id'] ?></p>
            <p><strong>Registration Date:</strong> <?= date('F j, Y', strtotime($member['reg_date'])) ?></p>
            <p><strong>Current Status:</strong> 
                <span class="status-<?= strtolower($member['membership_status']) ?>">
                    <?= ucfirst($member['membership_status']) ?>
                </span>
            </p>
        </div>

        <div class="form-container">
            <?php if ($success): ?>
                <div class="message success"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-section">
                    <div class="section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($member['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($member['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($member['phone']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" value="<?= $member['dob'] ?>">
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Select Gender</option>
                                <option value="male" <?= $member['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= $member['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= $member['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Membership Details</div>
                    <div class="form-grid">
                        <div class="form-group">
                                <label for="membership_status">Membership Status <span class="required">*</span></label>
                                <select id="membership_status" name="membership_status" class="status-select" required>
                                    <option value="active" class="status-active" <?= $member['membership_status'] === 'active' ? 'selected' : '' ?>>✅ Active</option>
                                    <option value="pending" class="status-pending" <?= $member['membership_status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                    <option value="cancelled" class="status-cancelled" <?= $member['membership_status'] === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                                </select>
                        </div>

                        <!-- Membership start date input removed for this project -->

                        <div class="form-group">
                            <label for="membership_status">Membership Status <span class="required">*</span></label>
                            <select id="membership_status" name="membership_status" class="status-select" required>
                                <option value="active" class="status-active" <?= $member['membership_status'] === 'active' ? 'selected' : '' ?>>✅ Active</option>
                                <option value="pending" class="status-pending" <?= $member['membership_status'] === 'pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                <option value="cancelled" class="status-cancelled" <?= $member['membership_status'] === 'cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-title">Security Settings</div>
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password">
                        <div class="password-note">Leave blank to keep current password unchanged</div>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary">💾 Update Member</button>
                    <a href="admin-client-list.php" class="btn btn-secondary">🔙 Back to Members</a>
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

        // Real-time email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#dc3545';
                alert('Please enter a valid email address.');
            } else {
                this.style.borderColor = '#e1e5e9';
            }
        });

        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            this.value = value;
        });
    </script>
</body>
</html>