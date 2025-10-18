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
        case 'appointment_updated':
            $message = "✅ Appointment status updated successfully.";
            break;
        case 'appointment_deleted':
            $message = "✅ Appointment has been successfully deleted.";
            break;
        default:
            $message = "✅ Operation completed successfully.";
    }
}

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'invalid_id':
            $message = "❌ Invalid appointment ID provided.";
            break;
        case 'appointment_not_found':
            $message = "❌ Appointment not found.";
            break;
        case 'update_failed':
            $message = "❌ Failed to update appointment. Please try again.";
            break;
        case 'database_error':
            $message = "❌ Database error occurred. Please contact administrator.";
            break;
        default:
            $message = "❌ An error occurred. Please try again.";
    }
}

// Handle status updates
if (isset($_GET['update']) && isset($_GET['status'])) {
    $id = intval($_GET['update']);
    $new_status = $_GET['status'];
    
    if (in_array($new_status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
        
        if ($stmt->execute()) {
            header("Location: admin-appointments.php?success=appointment_updated");
            exit();
        } else {
            header("Location: admin-appointments.php?error=update_failed");
            exit();
        }
    }
}

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$filterDate = $_GET['filter_date'] ?? '';

// Build the query with proper joins and filters
$sql = "SELECT 
    a.id, 
    m.full_name AS client_name,
    m.email AS client_email,
    m.phone AS client_phone,
    s.services_name AS service_name,
    t.full_name AS therapist_name,
    a.date, 
    a.time_slot, 
    a.status,
    a.notes,
    a.created_at
FROM appointments a
LEFT JOIN members m ON a.client_id = m.id
LEFT JOIN services s ON a.service_id = s.id  
LEFT JOIN therapists t ON a.therapist_id = t.id
-- Plans removed for this project: no JOIN to plans
WHERE 1=1";

$params = [];
$types = "";

if ($search) {
    $sql .= " AND (m.full_name LIKE ? OR s.services_name LIKE ? OR t.full_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= "sss";
}

if ($filterStatus) {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

if ($filterDate) {
    $sql .= " AND DATE(a.date) = ?";
    $params[] = $filterDate;
    $types .= "s";
}

$sql .= " ORDER BY a.date DESC, a.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];
$confirmed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management - GreenLife Wellness Center</title>
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
            box-shadow: 0 5px 20px rgba(198, 40, 40, 0.3);
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
            background: linear-gradient(135deg, #417243, #509956);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(198, 40, 40, 0.3);
        }

        .appointments-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08);
            overflow: hidden;
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

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-confirmed {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-confirm {
            background: #28a745;
            color: white;
        }

        .btn-confirm:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .btn-complete {
            background: #6f42c1;
            color: white;
        }

        .btn-complete:hover {
            background: #5a32a3;
            transform: translateY(-1px);
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #6c757d;
            color: white;
        }

        .btn-delete:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .client-info {
            font-weight: 600;
            color: #333;
        }

        .client-contact {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .service-info {
            font-weight: 600;
            color: #509956;
        }

        .appointment-date {
            font-weight: 600;
            color: #333;
        }

        .appointment-time {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
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
            color: #dc3545;
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

        .btn-modal-cancel { 
            background: #6c757d; 
            color: white; 
        }

        .btn-modal-cancel:hover {
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

        @media (max-width: 768px) {
            
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

    <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>

    <div class="header">
        <h1>� Appointment Management</h1>
        <p>Manage all wellness center appointments and bookings</p>
    </div>

    <?php if ($message): ?>
    <div class="message-container">
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- stats-bar removed per request -->

    <div class="controls">
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="🔍 Search by client, service, or therapist..." value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
            
            <select name="filter_status">
                <option value="">-- Filter by Status --</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <input type="date" name="filter_date" value="<?= htmlspecialchars($filterDate) ?>" title="Filter by date">
            
            <button type="submit">Search</button>
            <?php if ($search || $filterStatus || $filterDate): ?>
                <a href="admin-appointments.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="appointments-container">
        <div class="table-responsive">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client Details</th>
                            <th>Service</th>
                            <th>Therapist</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td>
                                    <div class="client-info"><?= htmlspecialchars($row['client_name'] ?? 'N/A') ?></div>
                                    <div class="client-contact">
                                        <?php if ($row['client_email']): ?>📧 <?= htmlspecialchars($row['client_email']) ?><br><?php endif; ?>
                                        <?php if ($row['client_phone']): ?>📞 <?= htmlspecialchars($row['client_phone']) ?><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="service-info"><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['therapist_name'] ?? 'Not Assigned') ?></td>
                                <td>
                                    <div class="appointment-date"><?= date('M j, Y', strtotime($row['date'])) ?></div>
                                    <div class="appointment-time"><?= htmlspecialchars($row['time_slot'] ?? 'Not specified') ?></div>
                                </td>
                            <!-- Plan column removed for this project -->
                                <td>
                                    <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(substr($row['notes'] ?? '', 0, 50)) ?><?= strlen($row['notes'] ?? '') > 50 ? '...' : '' ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <a href="?update=<?= $row['id'] ?>&status=confirmed" class="btn btn-confirm" title="Confirm Appointment">✅</a>
                                        <?php endif; ?>
                                        
                                        <?php if ($row['status'] === 'confirmed'): ?>
                                            <a href="?update=<?= $row['id'] ?>&status=completed" class="btn btn-complete" title="Mark Complete">✔️</a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($row['status'], ['pending', 'confirmed'])): ?>
                                            <a href="?update=<?= $row['id'] ?>&status=cancelled" class="btn btn-cancel" title="Cancel Appointment">❌</a>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-delete" data-id="<?= $row['id'] ?>" data-client="<?= htmlspecialchars($row['client_name'] ?? 'Unknown') ?>" title="Delete Appointment">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-results">
                    <h3>No Appointments Found</h3>
                    <p>
                        <?php if ($search || $filterStatus || $filterDate): ?>
                            No appointments match your search criteria. Try adjusting your filters.
                        <?php else: ?>
                            No appointments have been scheduled yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h2>⚠️ Confirm Delete</h2>
            <p>Are you sure you want to delete the appointment for <strong id="clientName"></strong>?</p>
            <p style="color: #dc3545; font-size: 14px;">This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="btn-modal-cancel" id="cancelBtn">Cancel</button>
                <button class="btn-confirm-delete" id="confirmDeleteBtn">Delete Appointment</button>
            </div>
        </div>
    </div>

    <script>
        const deleteButtons = document.querySelectorAll('.btn-delete');
        const modal = document.getElementById('deleteModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const clientNameSpan = document.getElementById('clientName');
        let deleteId = null;

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                deleteId = btn.getAttribute('data-id');
                const clientName = btn.getAttribute('data-client');
                clientNameSpan.textContent = clientName;
                modal.style.display = 'flex';
            });
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            deleteId = null;
        });

        confirmBtn.addEventListener('click', async () => {
            if (deleteId) {
                // Disable button and show loading
                confirmBtn.disabled = true;
                confirmBtn.textContent = 'Deleting...';
                
                try {
                    const response = await fetch('admin-delete-appointment.php', {
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
                        
                        // Remove the appointment row from the DOM
                        const appointmentRow = document.querySelector(`[data-id="${deleteId}"]`).closest('tr');
                        if (appointmentRow) {
                            appointmentRow.style.animation = 'fadeOut 0.5s ease-out forwards';
                            setTimeout(() => {
                                appointmentRow.remove();
                                updateStatistics();
                            }, 500);
                        }
                    } else {
                        showMessage(result.message, 'error');
                    }
                } catch (error) {
                    showMessage('An error occurred while deleting the appointment. Please try again.', 'error');
                } finally {
                    // Reset button
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete Appointment';
                    deleteId = null;
                }
            }
        });

        window.addEventListener('click', e => {
            if (e.target === modal) {
                modal.style.display = 'none';
                deleteId = null;
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

        // Add confirmation for status changes
        document.querySelectorAll('.btn-confirm, .btn-complete, .btn-cancel').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.classList.contains('btn-confirm') ? 'confirm' : 
                              btn.classList.contains('btn-complete') ? 'complete' : 'cancel';
                
                if (!confirm(`Are you sure you want to ${action} this appointment?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>
</html>
