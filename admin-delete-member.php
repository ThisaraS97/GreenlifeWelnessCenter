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
    if (isset($_GET['id'])) {
        header("Location: admin-client-list.php?error=invalid_id");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Invalid member ID']);
        exit();
    }
}

$member_id = (int)$member_id;

try {
    // First, check if member exists and get their information
    $checkStmt = $conn->prepare("SELECT full_name, email FROM members WHERE id = ?");
    $checkStmt->bind_param("i", $member_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        if (isset($_GET['id'])) {
            header("Location: admin-client-list.php?error=member_not_found");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Member not found']);
            exit();
        }
    }
    
    $member = $result->fetch_assoc();
    
    // Start transaction for data integrity
    $conn->begin_transaction();
    
    // Delete related records first (to handle foreign key constraints)
    
    // 1. Delete member's queries
    $deleteQueries = $conn->prepare("DELETE FROM queries WHERE member_id = ?");
    $deleteQueries->bind_param("i", $member_id);
    $deleteQueries->execute();
    
    // 2. Delete member's appointments (if appointments table references members)
    // First check if appointments table has client_id referring to members table
    $appointmentCheck = $conn->query("SHOW COLUMNS FROM appointments LIKE 'client_id'");
    if ($appointmentCheck->num_rows > 0) {
        // Check if any appointments exist for this member
        $appointmentStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE client_id = ?");
        $appointmentStmt->bind_param("i", $member_id);
        $appointmentStmt->execute();
        $appointmentCount = $appointmentStmt->get_result()->fetch_assoc()['count'];
        
        if ($appointmentCount > 0) {
            // Delete appointments
            $deleteAppointments = $conn->prepare("DELETE FROM appointments WHERE client_id = ?");
            $deleteAppointments->bind_param("i", $member_id);
            $deleteAppointments->execute();
        }
    }
    
    // 3. Finally delete the member
    $deleteMember = $conn->prepare("DELETE FROM members WHERE id = ?");
    $deleteMember->bind_param("i", $member_id);
    $deleteMember->execute();
    
    if ($deleteMember->affected_rows > 0) {
        // Commit transaction
        $conn->commit();
        
        if (isset($_GET['id'])) {
            // Redirect with success message for direct access
            header("Location: admin-client-list.php?success=member_deleted&name=" . urlencode($member['full_name']));
            exit();
        } else {
            // AJAX response
            header("Content-Type: application/json");
            echo json_encode([
                'success' => true, 
                'message' => 'Member "' . $member['full_name'] . '" has been successfully deleted.',
                'member_name' => $member['full_name']
            ]);
            exit();
        }
    } else {
        // Rollback transaction
        $conn->rollback();
        
        if (isset($_GET['id'])) {
            header("Location: admin-client-list.php?error=delete_failed");
            exit();
        } else {
            header("Content-Type: application/json");
            echo json_encode(['success' => false, 'message' => 'Failed to delete member']);
            exit();
        }
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    if (isset($_GET['id'])) {
        header("Location: admin-client-list.php?error=database_error");
        exit();
    } else {
        header("Content-Type: application/json");
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}
?>