<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$database = new Database();
$conn = $database->getConnection();

// Get all users
$query = "SELECT * FROM users ORDER BY created_at DESC";
$result = $conn->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

renderAdminHeader("User Management", "users");
?>

<!-- Main Content -->
<div class="content-card">
  <h3>
    <i class="bi bi-people"></i>
    All Users (<?= count($users) ?>)
  </h3>
  
  <?php if (count($users) > 0): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th><i class="bi bi-hash"></i> ID</th>
            <th><i class="bi bi-person-badge"></i> Type</th>
            <th><i class="bi bi-person"></i> Name</th>
            <th><i class="bi bi-envelope"></i> Email</th>
            <th><i class="bi bi-telephone"></i> Phone</th>
            <th><i class="bi bi-circle"></i> Status</th>
            <th><i class="bi bi-calendar"></i> Created</th>
            <th><i class="bi bi-gear"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><strong>#<?= $user['user_id'] ?></strong></td>
              <td>
                <span class="status-badge status-<?= $user['user_type'] ?>">
                  <i class="bi bi-<?= $user['user_type'] === 'admin' ? 'gear' : ($user['user_type'] === 'driver' ? 'car-front' : 'person') ?>"></i>
                  <?= ucfirst($user['user_type']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['phone']) ?></td>
              <td>
                <span class="status-badge status-<?= $user['status'] ?>">
                  <?= ucfirst($user['status']) ?>
                </span>
              </td>
              <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
              <td>
                <a href="view_user.php?id=<?= $user['user_id'] ?>" class="action-btn">
                  <i class="bi bi-eye"></i> View
                </a>
                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="action-btn btn-warning">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <?php if ($user['status'] === 'active'): ?>
                  <a href="suspend_user.php?id=<?= $user['user_id'] ?>" class="action-btn btn-danger" 
                     onclick="return confirm('Are you sure you want to suspend this user?')">
                    <i class="bi bi-person-x"></i> Suspend
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <h5>No Users Found</h5>
      <p>Users will appear here once they register.</p>
    </div>
  <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>

