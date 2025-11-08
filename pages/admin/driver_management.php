<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action'];
        
        if ($action === 'suspend') {
            $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ? AND user_type = 'driver'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = 'Driver suspended successfully!';
        } elseif ($action === 'activate') {
            $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ? AND user_type = 'driver'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success_message'] = 'Driver activated successfully!';
        }
        
        header("Location: driver_management.php");
        exit();
    }
}

// Get all drivers with verification status
$query = "SELECT u.*, d.verification_status FROM users u 
          LEFT JOIN drivers d ON u.user_id = d.user_id 
          WHERE u.user_type = 'driver' ORDER BY u.user_id DESC";
$result = $conn->query($query);
$drivers = $result->fetch_all(MYSQLI_ASSOC);

renderAdminHeader("Driver Management", "drivers");
?>

<!-- Success/Error Messages -->
<?php if (isset($_SESSION['success_message'])): ?>
  <?php showAlert('success', $_SESSION['success_message']); ?>
  <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
  <?php showAlert('danger', $_SESSION['error_message']); ?>
  <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<!-- Main Content -->
<div class="content-card">
  <h3>
    <i class="bi bi-car-front"></i>
    Driver Management (<?= count($drivers) ?>)
  </h3>
  
  <div class="row mb-3">
    <div class="col-md-6">
      <a href="driver_verification.php" class="btn btn-custom">
        <i class="bi bi-shield-check"></i> Driver Verification
      </a>
    </div>
  </div>
  
  <?php if (count($drivers) > 0): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th><i class="bi bi-hash"></i> ID</th>
            <th><i class="bi bi-person"></i> Name</th>
            <th><i class="bi bi-envelope"></i> Email</th>
            <th><i class="bi bi-telephone"></i> Phone</th>
            <th><i class="bi bi-shield"></i> Verification</th>
            <th><i class="bi bi-circle"></i> Status</th>
            <th><i class="bi bi-calendar"></i> Joined</th>
            <th><i class="bi bi-gear"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($drivers as $driver): ?>
            <tr>
              <td><strong>#<?= $driver['user_id'] ?></strong></td>
              <td><?= htmlspecialchars($driver['name']) ?></td>
              <td><?= htmlspecialchars($driver['email']) ?></td>
              <td><?= htmlspecialchars($driver['phone']) ?></td>
              <td>
                <?php if ($driver['verification_status']): ?>
                  <span class="status-badge status-<?= $driver['verification_status'] ?>">
                    <i class="bi bi-<?= $driver['verification_status'] === 'verified' ? 'shield-check' : ($driver['verification_status'] === 'pending' ? 'clock' : 'shield-x') ?>"></i>
                    <?= ucfirst($driver['verification_status']) ?>
                  </span>
                <?php else: ?>
                  <span class="status-badge status-inactive">
                    <i class="bi bi-shield-slash"></i> Not Applied
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge status-<?= $driver['status'] ?>">
                  <?= ucfirst($driver['status']) ?>
                </span>
              </td>
              <td><?= date('M d, Y', strtotime($driver['created_at'])) ?></td>
              <td>
                <a href="view_user.php?id=<?= $driver['user_id'] ?>" class="action-btn">
                  <i class="bi bi-eye"></i> View
                </a>
                
                <?php if ($driver['verification_status'] === 'pending'): ?>
                  <a href="driver_verification.php?id=<?= $driver['user_id'] ?>" class="action-btn btn-info">
                    <i class="bi bi-shield-check"></i> Verify
                  </a>
                <?php endif; ?>
                
                <?php if ($driver['status'] === 'active'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?= $driver['user_id'] ?>">
                    <input type="hidden" name="action" value="suspend">
                    <button type="submit" class="action-btn btn-danger" 
                            onclick="return confirm('Are you sure you want to suspend this driver?')">
                      <i class="bi bi-person-x"></i> Suspend
                    </button>
                  </form>
                <?php elseif ($driver['status'] === 'suspended'): ?>
                  <form method="POST" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?= $driver['user_id'] ?>">
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="action-btn" 
                            onclick="return confirm('Are you sure you want to activate this driver?')">
                      <i class="bi bi-person-check"></i> Activate
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-car-front"></i>
      <h5>No Drivers Found</h5>
      <p>Drivers will appear here once they register.</p>
    </div>
  <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>

