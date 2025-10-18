<!DOCTYPE html>
<html>
<head>
    <title>Therapist Login - GreenLife Wellness Center</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #509956, #6fbf6e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #509956;
            margin: 0;
            font-size: 24px;
        }
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #509956;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #509956;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-btn:hover {
            background: #417243;
        }
        .error {
            background: #ffe6e6;
            color: #d00;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #509956;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🌿 GreenLife Wellness</h1>
            <p>Therapist Portal</p>
        </div>

        <?php
        session_start();
        include 'db_connect.php';

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            if (empty($email) || empty($password)) {
                $error = 'Please fill in all fields';
            } else {
                // Check therapist credentials
                $stmt = $conn->prepare("SELECT id, full_name, password FROM therapists WHERE email = ? AND status = 'active'");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['therapist_id'] = $row['id'];
                        $_SESSION['therapist_name'] = $row['full_name'];
                        $_SESSION['therapist_email'] = $email;
                        
                        header("Location: therapist-dashboard.php");
                        exit();
                    } else {
                        $error = 'Invalid email or password';
                    }
                } else {
                    $error = 'Invalid email or password';
                }
            }
        }
        ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="login-btn">🔐 Login to Portal</button>
        </form>

        <div class="links">
            <a href="index.html">← Back to Main Site</a>
        </div>
    </div>
</body>
</html>