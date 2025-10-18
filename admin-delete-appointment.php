<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get appointment ID
$appointment_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$appointment_id || !is_numeric($appointment_id)) {
    if (isset($_GET['id'])) {
        header("Location: admin-appointments.php?error=invalid_id");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Invalid appointment ID']);
        exit();
    }
}

$appointment_id = (int)$appointment_id;

try {
    // First, check if appointment exists and get client information
    $checkStmt = $conn->prepare("
        SELECT a.id, m.full_name AS client_name, s.services_name, a.date
        FROM appointments a
        LEFT JOIN members m ON a.client_id = m.id  
        LEFT JOIN services s ON a.service_id = s.id
        WHERE a.id = ?
    ");
    $checkStmt->bind_param("i", $appointment_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        if (isset($_GET['id'])) {
            header("Location: admin-appointments.php?error=appointment_not_found");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit();
        }
    }
    
    $appointment = $result->fetch_assoc();
    
    // Delete the appointment
    $deleteStmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $deleteStmt->bind_param("i", $appointment_id);
    
    if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
        if (isset($_GET['id'])) {
            // Redirect with success message for direct access
            header("Location: admin-appointments.php?success=appointment_deleted");
            exit();
        } else {
            // AJAX response
            header("Content-Type: application/json");
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment for ' . ($appointment['client_name'] ?? 'client') . ' has been successfully deleted.',
                'client_name' => $appointment['client_name'] ?? 'Unknown'
            ]);
            exit();
        }
    } else {
        if (isset($_GET['id'])) {
            header("Location: admin-appointments.php?error=delete_failed");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Failed to delete appointment']);
            exit();
        }
    }
    
} catch (Exception $e) {
    if (isset($_GET['id'])) {
        header("Location: admin-appointments.php?error=database_error");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}
?>