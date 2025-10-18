<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        die("<h3 style='color:red; text-align:center;'>❌ Passwords do not match.</h3>");
    }

    // Check if token is valid
    $stmt = $conn->prepare("SELECT id FROM members WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];

        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the password and clear the token
        $stmt = $conn->prepare("UPDATE members SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();

        echo "<h3 style='color:green; text-align:center;'>✅ Password reset successfully!</h3>";
        echo "<p style='text-align:center;'><a href='index.html'>Return to Login</a></p>";

    } else {
        echo "<h3 style='color:red; text-align:center;'>❌ Invalid or expired token.</h3>";
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: index.html");
    exit();
}
?>
