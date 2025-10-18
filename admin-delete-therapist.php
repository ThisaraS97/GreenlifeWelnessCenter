<?php
session_start();
include 'db_connect.php';

// Check admin authentication
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Content-Type: application/json");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get therapist ID
$therapist_id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$therapist_id || !is_numeric($therapist_id)) {
    if (isset($_GET['id'])) {
        header("Location: admin-therapist-list.php?error=invalid_id");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Invalid therapist ID']);
        exit();
    }
}

$therapist_id = (int)$therapist_id;

try {
    // First, check if therapist exists and get their information
    $checkStmt = $conn->prepare("SELECT full_name, image_path FROM therapists WHERE id = ?");
    $checkStmt->bind_param("i", $therapist_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        if (isset($_GET['id'])) {
            header("Location: admin-therapist-list.php?error=therapist_not_found");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Therapist not found']);
            exit();
        }
    }
    
    $therapist = $result->fetch_assoc();
    
    // Start transaction for data integrity
    $conn->begin_transaction();
    
    // Delete related records first (if any exist)
    
    // 1. Delete related appointments (if appointments reference therapists)
    $appointmentCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'therapist_id'");
    if ($appointmentCheck->num_rows > 0) {
        // Check if any appointments exist for this therapist
        $appointmentStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE therapist_id = ?");
        $appointmentStmt->bind_param("i", $therapist_id);
        $appointmentStmt->execute();
        $appointmentCount = $appointmentStmt->get_result()->fetch_assoc()['count'];
        
        if ($appointmentCount > 0) {
            // Delete appointments
            $deleteAppointments = $conn->prepare("DELETE FROM appointments WHERE therapist_id = ?");
            $deleteAppointments->bind_param("i", $therapist_id);
            $deleteAppointments->execute();
        }
    }
    
    // 2. Update services table if therapist is assigned (set trainer_name to null or empty)
    $servicesStmt = $conn->prepare("UPDATE services SET trainer_name = NULL WHERE trainer_name = ?");
    $servicesStmt->bind_param("s", $therapist['full_name']);
    $servicesStmt->execute();
    
    // 3. Finally delete the therapist
    $deleteTherapist = $conn->prepare("DELETE FROM therapists WHERE id = ?");
    $deleteTherapist->bind_param("i", $therapist_id);
    $deleteTherapist->execute();
    
    if ($deleteTherapist->affected_rows > 0) {
        // Delete image file if it exists
        if (!empty($therapist['image_path']) && file_exists($therapist['image_path'])) {
            unlink($therapist['image_path']);
        }
        
        // Commit transaction
        $conn->commit();
        
        if (isset($_GET['id'])) {
            // Redirect with success message for direct access
            header("Location: admin-therapist-list.php?success=therapist_deleted&name=" . urlencode($therapist['full_name']));
            exit();
        } else {
            // AJAX response
            header("Content-Type: application/json");
            echo json_encode([
                'success' => true, 
                'message' => 'Therapist "' . $therapist['full_name'] . '" has been successfully deleted.',
                'therapist_name' => $therapist['full_name']
            ]);
            exit();
        }
    } else {
        // Rollback transaction
        $conn->rollback();
        
        if (isset($_GET['id'])) {
            header("Location: admin-therapist-list.php?error=delete_failed");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Failed to delete therapist']);
            exit();
        }
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    if (isset($_GET['id'])) {
        header("Location: admin-therapist-list.php?error=database_error");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}
?>