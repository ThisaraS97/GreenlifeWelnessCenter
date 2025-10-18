# CRUD Operations Guide - GreenLife Wellness Center

## Overview
This guide explains how CRUD (Create, Read, Update, Delete) operations work in the GreenLife Wellness Center project using PHP and MySQL.

---

## Database Connection (`db_connect.php`)

```php
<?php
$host = "localhost";      
$user = "root";           
$password = "";           
$database = "Greenlife_db"; 

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
?>
```

**Usage:** Include this file in every PHP script that needs database access:
```php
include 'db_connect.php';
```

---

## 1. CREATE Operation

### Example: Member Registration (`register.php`)

```php
<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Step 1: Get and sanitize form data
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'];
    $confirm = $_POST['confirmpassword'];

    // Step 2: Validate passwords match
    if ($password !== $confirm) {
        $error = "❌ Passwords do not match!";
    } else {
        // Step 3: Check if email already exists (prevent duplicates)
        $check = $conn->prepare("SELECT id FROM members WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "❌ Email already exists!";
        } else {
            // Step 4: Hash password for security
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Step 5: Insert new member into database
            $stmt = $conn->prepare("INSERT INTO members (full_name, email, phone, dob, gender, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $fullname, $email, $phone, $dob, $gender, $hashed);

            if ($stmt->execute()) {
                // Success: Set session and redirect
                $_SESSION['user_id'] = $stmt->insert_id;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "❌ Something went wrong. Try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
```

**Key Points:**
- Use `trim()` to remove whitespace
- Use `password_hash()` for password security
- Use prepared statements (`prepare()` and `bind_param()`) to prevent SQL injection
- Always check for duplicate entries
- Use `$stmt->insert_id` to get the newly created record ID

---

## 2. READ Operation

### Example: Display Members List (`admin-client-list.php`)

```php
<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit();
}

// Optional filters and search
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

// Build SQL query with filters
$sql = "SELECT * FROM members WHERE 1";
$params = [];

if ($search) {
    $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}

if ($filterStatus) {
    $sql .= " AND membership_status = ?";
    $params[] = $filterStatus;
}

$sql .= " ORDER BY reg_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);

if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$total_members = $conn->query("SELECT COUNT(*) as count FROM members")->fetch_assoc()['count'];
$active_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE membership_status = 'active'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Members List</title>
</head>
<body>
    <h1>Total Members: <?php echo $total_members; ?></h1>
    <h2>Active: <?php echo $active_members; ?></h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['membership_status']); ?></td>
                <td>
                    <a href="admin-edit-member.php?id=<?php echo $row['id']; ?>">Edit</a>
                    <a href="admin-delete-member.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this member?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
```

**Key Points:**
- Use `fetch_assoc()` to get data as associative array
- Use `htmlspecialchars()` to prevent XSS attacks when displaying data
- Use `while` loop to iterate through all results
- Always validate user authentication before showing sensitive data

### Read Single Record

```php
<?php
// Get member by ID
$member_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Member not found
    header("Location: admin-client-list.php");
    exit();
}

$member = $result->fetch_assoc();

echo "Name: " . htmlspecialchars($member['full_name']);
echo "Email: " . htmlspecialchars($member['email']);
?>
```

---

## 3. UPDATE Operation

### Example: Edit Member (`admin-edit-member.php`)

```php
<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Get member ID
$member_id = (int)$_GET['id'];

// Fetch existing member data
$stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin-client-list.php");
    exit();
}

$member = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $membership_status = $_POST['membership_status'];
    $new_password = $_POST['password'];
    
    // Validate required fields
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = "❌ Please fill in all required fields.";
    } else {
        // Check if email is already taken by another member
        $emailCheck = $conn->prepare("SELECT id FROM members WHERE email = ? AND id != ?");
        $emailCheck->bind_param("si", $email, $member_id);
        $emailCheck->execute();
        
        if ($emailCheck->get_result()->num_rows > 0) {
            $error = "❌ Email address is already registered to another member.";
        } else {
            // Update with or without password change
            if (!empty($new_password)) {
                // Update with new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, dob = ?, gender = ?, membership_status = ?, password = ? WHERE id = ?");
                $updateStmt->bind_param("sssssssi", $full_name, $email, $phone, $dob, $gender, $membership_status, $hashed_password, $member_id);
            } else {
                // Update without password change
                $updateStmt = $conn->prepare("UPDATE members SET full_name = ?, email = ?, phone = ?, dob = ?, gender = ?, membership_status = ? WHERE id = ?");
                $updateStmt->bind_param("ssssssi", $full_name, $email, $phone, $dob, $gender, $membership_status, $member_id);
            }
            
            if ($updateStmt->execute()) {
                $success = "✅ Member information updated successfully!";
                
                // Refresh member data to show updated values
                $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
                $stmt->bind_param("i", $member_id);
                $stmt->execute();
                $member = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "❌ Failed to update member information.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Member</title>
</head>
<body>
    <h1>Edit Member</h1>
    
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <label>Full Name *</label>
        <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
        
        <label>Email *</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
        
        <label>Phone *</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone']); ?>" required>
        
        <label>Date of Birth</label>
        <input type="date" name="dob" value="<?php echo htmlspecialchars($member['dob']); ?>">
        
        <label>Gender</label>
        <select name="gender">
            <option value="male" <?php echo $member['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
            <option value="female" <?php echo $member['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
            <option value="other" <?php echo $member['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
        </select>
        
        <label>Membership Status</label>
        <select name="membership_status">
            <option value="active" <?php echo $member['membership_status'] === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="pending" <?php echo $member['membership_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="expired" <?php echo $member['membership_status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
        </select>
        
        <label>New Password (leave blank to keep current)</label>
        <input type="password" name="password" placeholder="Enter new password or leave blank">
        
        <button type="submit">Update Member</button>
    </form>
</body>
</html>
```

**Key Points:**
- First fetch existing data to pre-fill the form
- Validate that email is unique (excluding current member)
- Handle optional password update (only update if new password provided)
- Refresh data after update to show changes
- Use `htmlspecialchars()` in form values to prevent XSS

---

## 4. DELETE Operation

### Example: Delete Member (`admin-delete-member.php`)

```php
<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get member ID
$member_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$member_id || !is_numeric($member_id)) {
    header("Location: admin-client-list.php?error=invalid_id");
    exit();
}

$member_id = (int)$member_id;

try {
    // Step 1: Check if member exists and get their information
    $checkStmt = $conn->prepare("SELECT full_name, email FROM members WHERE id = ?");
    $checkStmt->bind_param("i", $member_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: admin-client-list.php?error=member_not_found");
        exit();
    }
    
    $member = $result->fetch_assoc();
    $memberName = $member['full_name'];
    
    // Step 2: Start transaction for data integrity
    $conn->begin_transaction();
    
    // Step 3: Delete related records first (to avoid foreign key issues)
    // Delete appointments
    $deleteAppointments = $conn->prepare("DELETE FROM appointments WHERE member_id = ?");
    $deleteAppointments->bind_param("i", $member_id);
    $deleteAppointments->execute();
    
    // Delete queries
    $deleteQueries = $conn->prepare("DELETE FROM queries WHERE member_id = ?");
    $deleteQueries->bind_param("i", $member_id);
    $deleteQueries->execute();
    
    // Step 4: Delete the member
    $deleteMember = $conn->prepare("DELETE FROM members WHERE id = ?");
    $deleteMember->bind_param("i", $member_id);
    $deleteMember->execute();
    
    if ($deleteMember->affected_rows > 0) {
        // Step 5: Commit transaction
        $conn->commit();
        
        // Redirect with success message
        header("Location: admin-client-list.php?success=member_deleted&name=" . urlencode($memberName));
        exit();
    } else {
        // Rollback if delete failed
        $conn->rollback();
        header("Location: admin-client-list.php?error=delete_failed");
        exit();
    }
    
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    error_log("Delete member error: " . $e->getMessage());
    header("Location: admin-client-list.php?error=database_error");
    exit();
}
?>
```

**Key Points:**
- Always verify member exists before deleting
- Use transactions (`begin_transaction()`, `commit()`, `rollback()`) for data integrity
- Delete related records first (appointments, queries, etc.) to avoid foreign key constraints
- Use try-catch blocks for error handling
- Provide clear feedback messages
- Use `affected_rows` to verify deletion was successful

### Simple Delete Example

```php
<?php
// Simple delete without transactions
session_start();
include 'db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    die("Unauthorized");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Member deleted successfully!";
    header("Location: admin-client-list.php?success=deleted");
} else {
    echo "Error deleting member.";
}
?>
```

---

## Security Best Practices

### 1. **Prepared Statements** (Prevent SQL Injection)
```php
// ❌ BAD - Vulnerable to SQL injection
$sql = "SELECT * FROM members WHERE email = '$email'";
$result = $conn->query($sql);

// ✅ GOOD - Using prepared statements
$stmt = $conn->prepare("SELECT * FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
```

### 2. **Password Hashing**
```php
// ❌ BAD - Storing plain text passwords
$sql = "INSERT INTO members (email, password) VALUES (?, ?)";

// ✅ GOOD - Hash passwords
$hashed = password_hash($password, PASSWORD_DEFAULT);
$sql = "INSERT INTO members (email, password) VALUES (?, ?)";

// Verify password on login
if (password_verify($input_password, $stored_hash)) {
    // Password correct
}
```

### 3. **Session Management**
```php
// Start session
session_start();

// Set user session on login
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];

// Check authentication
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Logout
session_destroy();
```

### 4. **XSS Prevention**
```php
// ❌ BAD - Direct output
echo $user['name'];

// ✅ GOOD - Escape output
echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
```

### 5. **Input Validation**
```php
// Sanitize input
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$phone = preg_replace('/[^0-9+]/', '', $_POST['phone']);
$name = trim($_POST['name']);

// Validate
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Invalid email format";
}

if (strlen($name) < 2) {
    $error = "Name too short";
}
```

---

## Parameter Binding Types

When using `bind_param()`:
- `"i"` - integer
- `"d"` - double (float)
- `"s"` - string
- `"b"` - blob (binary)

```php
// Multiple parameters
$stmt->bind_param("ssi", $name, $email, $age);
//                 ↑↑↑
//                 string, string, integer

// Example
$stmt = $conn->prepare("INSERT INTO members (name, email, age) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $name, $email, $age);
```

---

## Common Patterns

### Search with LIKE
```php
$search = $_GET['search'];
$like = "%$search%";
$stmt = $conn->prepare("SELECT * FROM members WHERE full_name LIKE ? OR email LIKE ?");
$stmt->bind_param("ss", $like, $like);
```

### Count Records
```php
$result = $conn->query("SELECT COUNT(*) as total FROM members");
$row = $result->fetch_assoc();
echo "Total: " . $row['total'];
```

### Get Last Inserted ID
```php
$stmt->execute();
$new_id = $stmt->insert_id;
echo "New member ID: " . $new_id;
```

### Check if Record Exists
```php
$stmt = $conn->prepare("SELECT id FROM members WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "Email already exists";
}
```

---

## Complete CRUD Example - Therapist Management

### Create Therapist
```php
<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Handle file upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/therapists/";
        $photo = basename($_FILES['photo']['name']);
        $target_file = $target_dir . $photo;
        move_uploaded_file($_FILES['photo']['tmp_name'], $target_file);
    }
    
    $stmt = $conn->prepare("INSERT INTO therapists (name, specialization, email, phone, photo) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $specialization, $email, $phone, $photo);
    
    if ($stmt->execute()) {
        echo "Therapist added successfully!";
    }
}
?>
```

### Read Therapists
```php
<?php
include 'db_connect.php';

$stmt = $conn->prepare("SELECT * FROM therapists ORDER BY name ASC");
$stmt->execute();
$result = $stmt->get_result();

while ($therapist = $result->fetch_assoc()) {
    echo "<div>";
    echo "<h3>" . htmlspecialchars($therapist['name']) . "</h3>";
    echo "<p>" . htmlspecialchars($therapist['specialization']) . "</p>";
    if ($therapist['photo']) {
        echo "<img src='uploads/therapists/" . htmlspecialchars($therapist['photo']) . "' alt='Photo'>";
    }
    echo "<a href='edit-therapist.php?id=" . $therapist['id'] . "'>Edit</a>";
    echo "<a href='delete-therapist.php?id=" . $therapist['id'] . "'>Delete</a>";
    echo "</div>";
}
?>
```

### Update Therapist
```php
<?php
include 'db_connect.php';

$id = (int)$_GET['id'];

// Fetch existing data
$stmt = $conn->prepare("SELECT * FROM therapists WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$therapist = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $specialization = trim($_POST['specialization']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $updateStmt = $conn->prepare("UPDATE therapists SET name = ?, specialization = ?, email = ?, phone = ? WHERE id = ?");
    $updateStmt->bind_param("ssssi", $name, $specialization, $email, $phone, $id);
    
    if ($updateStmt->execute()) {
        echo "Therapist updated!";
    }
}
?>
```

### Delete Therapist
```php
<?php
include 'db_connect.php';

$id = (int)$_GET['id'];

$stmt = $conn->prepare("DELETE FROM therapists WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: therapist-list.php?success=deleted");
} else {
    header("Location: therapist-list.php?error=failed");
}
?>
```

---

## Error Handling

```php
<?php
// Enable error reporting in development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection with error handling
try {
    $conn = new mysqli($host, $user, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Your CRUD operations here
    
} catch (Exception $e) {
    // Log error
    error_log($e->getMessage());
    
    // Show user-friendly message
    echo "An error occurred. Please try again later.";
    
    // In production, don't show actual error
    // echo $e->getMessage(); // Only in development
}
?>
```

---

## Summary

| Operation | SQL | PHP Function |
|-----------|-----|--------------|
| **Create** | INSERT INTO | `$conn->prepare()`, `execute()` |
| **Read** | SELECT | `get_result()`, `fetch_assoc()` |
| **Update** | UPDATE | `$conn->prepare()`, `execute()` |
| **Delete** | DELETE FROM | `$conn->prepare()`, `execute()` |

**Always Remember:**
1. ✅ Use prepared statements
2. ✅ Hash passwords
3. ✅ Validate and sanitize input
4. ✅ Use transactions for related deletions
5. ✅ Check authentication
6. ✅ Escape output with `htmlspecialchars()`
7. ✅ Handle errors gracefully

---

## Testing Your CRUD Operations

1. **Create Test**: Register a new member
2. **Read Test**: View members list
3. **Update Test**: Edit member details
4. **Delete Test**: Remove a member
5. **Edge Cases**: 
   - Try duplicate email
   - Try empty required fields
   - Try invalid data types
   - Try unauthorized access

---

**Need Help?** Check your project files:
- `register.php` - CREATE example
- `admin-client-list.php` - READ example
- `admin-edit-member.php` - UPDATE example
- `admin-delete-member.php` - DELETE example
