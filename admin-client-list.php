<?php
session_start();
include 'db_connect.php';

// Check admin login
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
        case 'member_deleted':
            $memberName = $_GET['name'] ?? 'Member';
            $message = "✅ Member \"$memberName\" has been successfully deleted.";
            break;
        default:
            $message = "✅ Operation completed successfully.";
    }
}

if (isset($_GET['error'])) {
    $messageType = 'error';
    switch ($_GET['error']) {
        case 'invalid_id':
            $message = "❌ Invalid member ID provided.";
            break;
        case 'member_not_found':
            $message = "❌ Member not found.";
            break;
        case 'delete_failed':
            $message = "❌ Failed to delete member. Please try again.";
            break;
        case 'database_error':
            $message = "❌ Database error occurred. Please contact administrator.";
            break;
        default:
            $message = "❌ An error occurred. Please try again.";
    }
}

// Optional filters
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

// Fetch members from the members table (not clients)
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
$pending_members = $conn->query("SELECT COUNT(*) as count FROM members WHERE membership_status = 'pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Manage Members - GreenLife Wellness Center</title>
<style>
    * { box-sizing: border-box; }
    body { 
        font-family: 'Segoe UI', sans-serif; 
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        margin: 0;
        padding: 20px;
    }
    
    .header {
    background: linear-gradient(135deg, #509956, #7fbf8b);
        color: white;
        padding: 25px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 5px 20px rgba(198, 40, 40, 0.3);
    }
    
    .header h1 { 
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 600;
    }
    
    .header p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .stats-bar {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .stat-item {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        text-align: center;
        min-width: 150px;
    border-left: 4px solid #509956;
    }

    .stat-item h3 {
        margin: 0;
    color: #509956;
        font-size: 24px;
        font-weight: bold;
    }
    
    .stat-item p {
        margin: 5px 0 0 0;
        color: #666;
        font-size: 14px;
    }
    
    .controls {
        background: white;
        padding: 25px;
        border-radius: 10px;
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
        border: 2px solid #ddd;
        border-radius: 8px; 
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .filter-form input:focus, .filter-form select:focus {
        outline: none;
    border-color: #509956;
    }
    
    .filter-form button { 
        padding: 12px 20px; 
        border-radius: 8px; 
        font-size: 14px; 
    background: linear-gradient(135deg, #509956, #7fbf8b);
        color: #fff; 
        border: none; 
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .filter-form button:hover {
    background: linear-gradient(135deg, #417243, #509956);
        transform: translateY(-2px);
    }
    
    .members-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
        gap: 25px; 
    }
    
    .member-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
        padding: 25px;
        transition: transform 0.3s, box-shadow 0.3s;
    border-left: 4px solid #509956;
    }
    
    .member-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .member-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .member-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
    background: linear-gradient(135deg, #509956, #7fbf8b);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
        font-weight: bold;
        margin-right: 15px;
    }
    
    .member-info h3 { 
        margin: 0 0 5px 0; 
        color: #2c3e50; 
        font-size: 18px; 
        font-weight: 600;
    }
    
    .status-badge { 
        padding: 4px 12px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: bold; 
        text-transform: uppercase;
    }
    
    .status-active { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
    
    .member-details {
        margin-bottom: 20px;
    }
    
    .member-details p { 
        margin: 8px 0; 
        color: #555; 
        font-size: 14px;
        display: flex;
        align-items: center;
    }
    
    .member-details .icon {
        margin-right: 8px;
        font-size: 16px;
        color: #509956;
        width: 20px;
    }
    
    .plan-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .plan-info strong {
        color: #509956;
        font-size: 14px;
    }
    
    .member-actions { 
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    
    .member-actions a, .member-actions button { 
        padding: 8px 16px;
        text-decoration: none; 
        font-size: 12px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-edit { 
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }
    
    .btn-edit:hover {
        background: linear-gradient(135deg, #138496, #0f6674);
    }
    
    .btn-delete { 
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .btn-delete:hover {
        background: linear-gradient(135deg, #c82333, #a71e2a);
    }

    .no-results {
        text-align: center;
        padding: 60px 20px;
        color: #7f8c8d;
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.08);
    }
    
    .no-results h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }

    /* Modal Styles */
    .modal-overlay { 
        position: fixed; 
        top:0; left:0; 
        width:100%; height:100%; 
        background: rgba(0,0,0,0.6); 
        display: none; 
        justify-content: center; 
        align-items: center; 
        z-index: 1000; 
    }
    
    .modal { 
        background: #fff; 
        padding: 30px; 
        border-radius: 15px; 
        width: 400px; 
        text-align: center; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.3); 
    }
    
    .modal h2 { 
        margin-bottom: 15px; 
        color: #dc3545; 
        font-size: 24px;
    }
    
    .modal p {
        margin-bottom: 25px;
        color: #666;
        font-size: 16px;
    }
    
    .modal-buttons { 
        display: flex; 
        justify-content: space-around; 
        gap: 15px;
    }
    
    .modal-buttons button { 
        padding: 12px 24px; 
        border-radius: 8px; 
        border: none; 
        cursor: pointer; 
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        flex: 1;
    }
    
    .btn-cancel { 
        background: #6c757d; 
        color: #fff; 
    }
    
    .btn-cancel:hover {
        background: #5a6268;
    }
    
    .btn-confirm-delete { 
        background: #dc3545; 
        color: #fff; 
    }
    
    .btn-confirm-delete:hover {
        background: #c82333;
    }
    
    .back-link {
        display: inline-block;
        margin-bottom: 20px;
    color: #509956;
        text-decoration: none;
        font-weight: 600;
    }
    
    .back-link:hover {
        text-decoration: underline;
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
</style>
</head>
<body>

<a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>

<div class="header">
    <h1>👥 Member Management</h1>
    <p>Manage all registered wellness center members and their information</p>
</div>

<?php if ($message): ?>
<div class="message-container">
    <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>
</div>
<?php endif; ?>

<!-- stats-bar removed as requested -->

<div class="controls">
    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="🔍 Search members by name, phone, or email..." value="<?= htmlspecialchars($search) ?>" style="min-width: 300px;">
        
        <select name="filter_status">
            <option value="">-- Filter by Status --</option>
            <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        
        <button type="submit">Search</button>
        <?php if ($search || $filterStatus): ?>
            <a href="admin-client-list.php" style="padding: 12px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="members-grid">
  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
      <div class="member-card">
        <div class="member-header">
            <div class="member-avatar">
                <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
            </div>
            <div class="member-info">
                <h3><?= htmlspecialchars($row['full_name']) ?></h3>
                <span class="status-badge status-<?= strtolower($row['membership_status']) ?>">
                    <?= ucfirst($row['membership_status']) ?>
                </span>
            </div>
        </div>
        
        <div class="member-details">
            <p><span class="icon">📧</span><?= htmlspecialchars($row['email']) ?></p>
            <p><span class="icon">📞</span><?= htmlspecialchars($row['phone']) ?></p>
            <?php if ($row['dob']): ?>
                <p><span class="icon">🎂</span><?= date('M j, Y', strtotime($row['dob'])) ?></p>
            <?php endif; ?>
            <?php if ($row['gender']): ?>
                <p><span class="icon">👤</span><?= ucfirst($row['gender']) ?></p>
            <?php endif; ?>
            <p><span class="icon">📅</span>Joined: <?= date('M j, Y', strtotime($row['reg_date'])) ?></p>
        </div>
        
        <!-- Plans removed for this project: membership plan display hidden -->

        <div class="member-actions">
          <a class="btn-edit" href="admin-edit-member.php?id=<?= $row['id'] ?>">✏️ Edit</a>
          <button class="btn-delete" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['full_name']) ?>">🗑️ Delete</button>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="no-results">
        <h3>No Members Found</h3>
        <p>
            <?php if ($search || $filterStatus): ?>
                No members match your search criteria. Try adjusting your filters.
            <?php else: ?>
                No members have been registered yet.
            <?php endif; ?>
        </p>
    </div>
  <?php endif; ?>
</div>

<!-- Delete Modal -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal">
    <h2>⚠️ Confirm Delete</h2>
    <p>Are you sure you want to delete <strong id="memberName"></strong>?</p>
    <p style="color: #dc3545; font-size: 14px;">This action cannot be undone.</p>
    <div class="modal-buttons">
      <button class="btn-cancel" id="cancelBtn">Cancel</button>
      <button class="btn-confirm-delete" id="confirmDeleteBtn">Delete Member</button>
    </div>
  </div>
</div>

<script>
  const deleteButtons = document.querySelectorAll('.btn-delete');
  const modal = document.getElementById('deleteModal');
  const cancelBtn = document.getElementById('cancelBtn');
  const confirmBtn = document.getElementById('confirmDeleteBtn');
  const memberNameSpan = document.getElementById('memberName');
  let deleteId = null;
  let memberName = null;

  deleteButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      deleteId = btn.getAttribute('data-id');
      memberName = btn.getAttribute('data-name');
      memberNameSpan.textContent = memberName;
      modal.style.display = 'flex';
    });
  });

  cancelBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    deleteId = null;
    memberName = null;
  });

  confirmBtn.addEventListener('click', async () => {
    if (deleteId) {
      // Disable button and show loading
      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Deleting...';
      
      try {
        const response = await fetch('admin-delete-member.php', {
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
          
          // Remove the member card from the DOM
          const memberCard = document.querySelector(`[data-id="${deleteId}"]`).closest('.member-card');
          if (memberCard) {
            memberCard.style.animation = 'fadeOut 0.5s ease-out forwards';
            setTimeout(() => {
              memberCard.remove();
              updateStatistics();
            }, 500);
          }
        } else {
          showMessage(result.message, 'error');
        }
      } catch (error) {
        showMessage('An error occurred while deleting the member. Please try again.', 'error');
      } finally {
        // Reset button
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Delete Member';
        deleteId = null;
        memberName = null;
      }
    }
  });

  window.addEventListener('click', e => {
    if (e.target === modal) {
      modal.style.display = 'none';
      deleteId = null;
      memberName = null;
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
</script>

</body>
</html>
