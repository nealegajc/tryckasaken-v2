<?php
session_start();
require_once '../../config/dbConnection.php';

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$userId = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: admin.php?error=user_not_found');
    exit;
}

// Get driver information if user is a driver
$driverInfo = null;
if ($user['user_type'] === 'driver') {
    $stmt = $conn->prepare("SELECT * FROM drivers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $driverInfo = $result->fetch_assoc();
    $stmt->close();
}

// Get booking history
$bookingsQuery = "SELECT * FROM tricycle_bookings WHERE ";
if ($user['user_type'] === 'passenger') {
    $bookingsQuery .= "user_id = ?";
} else {
    $bookingsQuery .= "driver_id = ?";
}
$bookingsQuery .= " ORDER BY booking_time DESC LIMIT 10";

$stmt = $conn->prepare($bookingsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get booking statistics
$statsQuery = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings
FROM tricycle_bookings WHERE ";

if ($user['user_type'] === 'passenger') {
    $statsQuery .= "user_id = ?";
} else {
    $statsQuery .= "driver_id = ?";
}

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View User - <?= htmlspecialchars($user['name']) ?> | TrycKaSaken Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/admin.css">
  <style>
    .info-row {
      padding: 15px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .info-row:last-child {
      border-bottom: none;
    }
    .info-label {
      font-weight: 600;
      color: #666;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .info-value {
      color: #333;
      font-weight: 500;
    }
    .document-thumbnail {
      width: 150px;
      height: 150px;
      object-fit: cover;
      border-radius: 12px;
      cursor: pointer;
      transition: transform 0.2s;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .document-thumbnail:hover {
      transform: scale(1.05);
    }
    .action-buttons {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <a class="navbar-brand" href="admin.php">
      <i class="bi bi-person-circle"></i> User Details
    </a>
    <div class="d-flex gap-2">
      <a href="admin.php" class="btn btn-outline-light btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
      </a>
      <a href="../../pages/auth/logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="admin-container">
  <!-- Success/Error Messages -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'success') ?> alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_GET['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill"></i> Error: <?= htmlspecialchars($_GET['error']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- User Profile Card -->
  <div class="row">
    <div class="col-lg-4">
      <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
          <div style="width: 120px; height: 120px; margin: 0 auto 20px; background: linear-gradient(135deg, #16a34a, #22c55e); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: white;">
            <?php if ($user['user_type'] === 'driver'): ?>
              🚗
            <?php else: ?>
              👤
            <?php endif; ?>
          </div>
          <h4><?= htmlspecialchars($user['name']) ?></h4>
          <p class="text-muted mb-2">
            <span class="badge bg-<?= $user['user_type'] === 'driver' ? 'success' : 'primary' ?>" style="font-size: 14px;">
              <?= ucfirst($user['user_type']) ?>
            </span>
          </p>
          <p class="text-muted mb-3">
            <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : ($user['status'] === 'inactive' ? 'secondary' : 'warning') ?>">
              <?= ucfirst($user['status']) ?>
            </span>
          </p>
          
          <div class="action-buttons">
            <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn btn-warning btn-sm">
              <i class="bi bi-pencil"></i> Edit User
            </a>
            <?php if ($user['status'] === 'active'): ?>
              <a href="suspend_user.php?id=<?= $user['user_id'] ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Suspend this user?')">
                <i class="bi bi-pause-circle"></i> Suspend
              </a>
            <?php else: ?>
              <a href="suspend_user.php?id=<?= $user['user_id'] ?>&action=activate" class="btn btn-success btn-sm" onclick="return confirm('Activate this user?')">
                <i class="bi bi-play-circle"></i> Activate
              </a>
            <?php endif; ?>
            <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone!')">
              <i class="bi bi-trash"></i> Delete
            </a>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-graph-up"></i> Booking Statistics</h6>
        </div>
        <div class="card-body">
          <div class="info-row">
            <span class="info-label">📊 Total Bookings</span>
            <span class="info-value"><?= $stats['total_bookings'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">✅ Completed</span>
            <span class="info-value"><?= $stats['completed_bookings'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">⏳ Pending</span>
            <span class="info-value"><?= $stats['pending_bookings'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label">❌ Cancelled</span>
            <span class="info-value"><?= $stats['cancelled_bookings'] ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <!-- Personal Information -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h6 class="mb-0"><i class="bi bi-info-circle"></i> Personal Information</h6>
        </div>
        <div class="card-body">
          <div class="info-row">
            <span class="info-label"><i class="bi bi-hash"></i> User ID</span>
            <span class="info-value">#<?= $user['user_id'] ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-person"></i> Full Name</span>
            <span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-envelope"></i> Email</span>
            <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-phone"></i> Phone</span>
            <span class="info-value"><?= htmlspecialchars($user['phone']) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-calendar"></i> Registered Date</span>
            <span class="info-value"><?= date('F d, Y', strtotime($user['created_at'])) ?></span>
          </div>
          <div class="info-row">
            <span class="info-label"><i class="bi bi-clock"></i> Account Age</span>
            <span class="info-value">
              <?php
              $accountAge = floor((time() - strtotime($user['created_at'])) / 86400);
              echo $accountAge . ' day' . ($accountAge != 1 ? 's' : '');
              ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Driver Information (if driver) -->
      <?php if ($user['user_type'] === 'driver' && $driverInfo): ?>
        <div class="card shadow-sm mb-4">
          <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-shield-check"></i> Driver Information</h6>
          </div>
          <div class="card-body">
            <div class="info-row">
              <span class="info-label"><i class="bi bi-hash"></i> Driver ID</span>
              <span class="info-value">#<?= $driverInfo['driver_id'] ?></span>
            </div>
            <div class="info-row">
              <span class="info-label"><i class="bi bi-patch-check"></i> Verification Status</span>
              <span class="info-value">
                <span class="badge bg-<?= $driverInfo['verification_status'] === 'verified' ? 'success' : ($driverInfo['verification_status'] === 'rejected' ? 'danger' : 'warning') ?>">
                  <?= ucfirst($driverInfo['verification_status']) ?>
                </span>
              </span>
            </div>
            
            <!-- Documents -->
            <div class="mt-4">
              <h6 class="mb-3"><i class="bi bi-file-earmark-text"></i> Uploaded Documents</h6>
              <div class="row g-3">
                <?php if ($driverInfo['picture_path']): ?>
                  <div class="col-md-4">
                    <div class="text-center">
                      <img src="../../<?= htmlspecialchars($driverInfo['picture_path']) ?>" 
                           alt="Driver Photo" 
                           class="document-thumbnail"
                           onclick="viewDocument('../../<?= htmlspecialchars($driverInfo['picture_path']) ?>', 'Driver Photo')">
                      <p class="mt-2 mb-0 text-muted">Driver Photo</p>
                    </div>
                  </div>
                <?php endif; ?>
                
                <?php if ($driverInfo['license_path']): ?>
                  <div class="col-md-4">
                    <div class="text-center">
                      <img src="../../<?= htmlspecialchars($driverInfo['license_path']) ?>" 
                           alt="License" 
                           class="document-thumbnail"
                           onclick="viewDocument('../../<?= htmlspecialchars($driverInfo['license_path']) ?>', 'Driver License')">
                      <p class="mt-2 mb-0 text-muted">Driver's License</p>
                    </div>
                  </div>
                <?php endif; ?>
                
                <?php if ($driverInfo['or_cr_path']): ?>
                  <div class="col-md-4">
                    <div class="text-center">
                      <img src="../../<?= htmlspecialchars($driverInfo['or_cr_path']) ?>" 
                           alt="OR/CR" 
                           class="document-thumbnail"
                           onclick="viewDocument('../../<?= htmlspecialchars($driverInfo['or_cr_path']) ?>', 'OR/CR Document')">
                      <p class="mt-2 mb-0 text-muted">OR/CR</p>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Booking History -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h6 class="mb-0"><i class="bi bi-clock-history"></i> Recent Booking History</h6>
        </div>
        <div class="card-body">
          <?php if (count($bookings) > 0): ?>
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Booking ID</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Date</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($bookings as $booking): ?>
                    <tr>
                      <td><strong>#<?= $booking['id'] ?></strong></td>
                      <td><?= htmlspecialchars($booking['location']) ?></td>
                      <td><?= htmlspecialchars($booking['destination']) ?></td>
                      <td><?= date('M d, Y H:i', strtotime($booking['booking_time'])) ?></td>
                      <td>
                        <span class="badge bg-<?= $booking['status'] === 'completed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'primary') ?>">
                          <?= ucfirst($booking['status']) ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="text-center mt-3">
              <a href="bookings.php?user_id=<?= $user['user_id'] ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-list"></i> View All Bookings
              </a>
            </div>
          <?php else: ?>
            <p class="text-muted text-center mb-0">No booking history available.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="documentModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="documentTitle">Document Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img id="documentImage" src="" alt="Document" style="max-width: 100%; height: auto; border-radius: 8px;">
      </div>
      <div class="modal-footer">
        <a id="downloadLink" href="" download class="btn btn-primary">
          <i class="bi bi-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewDocument(path, title) {
  document.getElementById('documentTitle').textContent = title;
  document.getElementById('documentImage').src = path;
  document.getElementById('downloadLink').href = path;
  
  const modal = new bootstrap.Modal(document.getElementById('documentModal'));
  modal.show();
}
</script>

</body>
</html>
<?php
$db->closeConnection();
?>
