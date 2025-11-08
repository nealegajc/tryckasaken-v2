<?php
session_start();
require_once '../../config/dbConnection.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'driver') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];
$driver_name = $_SESSION['name'];
$show_history = isset($_GET['history']) && $_GET['history'] == 'true';

// Check driver verification status
$verification_query = "SELECT verification_status FROM drivers WHERE user_id = ?";
$stmt = $conn->prepare($verification_query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$verification_result = $stmt->get_result()->fetch_assoc();
$verification_status = $verification_result ? $verification_result['verification_status'] : 'pending';
$stmt->close();

// Prevent unverified drivers from accepting rides
if (isset($_POST['accept_ride']) && $verification_status !== 'verified') {
    $_SESSION['error_message'] = "You must be verified before accepting ride requests.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['accept_ride'])) {
    $id = intval($_POST['booking_id']);

    // Check if driver already has an active trip
    $check_active = "SELECT COUNT(*) as active_count FROM tricycle_bookings WHERE driver_id = ? AND LOWER(status) = 'accepted'";
    $stmt_check = $conn->prepare($check_active);
    $stmt_check->bind_param("i", $driver_id);
    $stmt_check->execute();
    $active_result = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($active_result['active_count'] > 0) {
        $_SESSION['error_message'] = "You already have an active trip. Please complete it before accepting a new one.";
    } else {
        // Accept only if pending
        $update_sql = "UPDATE tricycle_bookings 
                       SET driver_id = ?, status = 'accepted' 
                       WHERE id = ? AND (status = 'pending' OR status = 'Pending')";
        $stmt = $conn->prepare($update_sql);

        if (!$stmt) die("SQL Error: " . $conn->error);

        $stmt->bind_param("ii", $driver_id, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "Ride accepted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to accept ride or ride already taken.";
        }

        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['complete_ride'])) {
    $id = intval($_POST['booking_id']);

    $complete_sql = "UPDATE tricycle_bookings 
                     SET status = 'completed' 
                     WHERE id = ? AND driver_id = ? AND (status = 'accepted' OR status = 'Accepted')";
    $stmt = $conn->prepare($complete_sql);

    if (!$stmt) die("SQL Error: " . $conn->error);

    $stmt->bind_param("ii", $id, $driver_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Ride completed successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to complete ride.";
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Determine which bookings to show
if ($show_history) {
    // Show only completed trips for this driver
    $sql = "SELECT * FROM tricycle_bookings 
            WHERE driver_id = ? AND LOWER(TRIM(status)) = 'completed'
            ORDER BY booking_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $driver_id);
} else {
    // Show pending requests and driver's active trips
    $sql = "SELECT * FROM tricycle_bookings 
            WHERE (LOWER(TRIM(status)) = 'pending') 
               OR (LOWER(TRIM(status)) = 'accepted' AND driver_id = ?)
            ORDER BY booking_time DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $driver_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $show_history ? 'Trip History' : 'Driver Requests'; ?> | TrycKaSaken</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../../public/css/request.css">
</head>
<body>

<div class="container">
  <a href="../driver/loginDriver.php" class="back-link"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
  
  <div class="page-header">
    <h2><?= $show_history ? '🕐 Trip History' : '🚗 Available Ride Requests'; ?></h2>
    <p><?= $show_history ? 'View your completed trips' : 'Accept and manage your ride bookings'; ?></p>
  </div>

  <!-- Verification Status Banner -->
  <?php if ($verification_status === 'pending'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert" style="border-left: 5px solid #22c55e;">
      <h5 class="alert-heading"><i class="bi bi-clock-history"></i> Application Under Review</h5>
      <p class="mb-0">Your driver application is currently being reviewed. You cannot accept ride requests until your account is verified.</p>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php elseif ($verification_status === 'rejected'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 5px solid #16a34a;">
      <h5 class="alert-heading"><i class="bi bi-x-circle"></i> Application Rejected</h5>
      <p class="mb-0">Your driver application has been rejected. Please contact support for more information.</p>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
      <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
      <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
      <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
      <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">×</button>
    </div>
  <?php endif; ?>

  <?php if ($result->num_rows > 0): ?>
    <?php if ($show_history): ?>
      <!-- Trip History Table View -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <tr>
                  <th class="border-0">Trip #</th>
                  <th class="border-0">Passenger</th>
                  <th class="border-0">Pickup Location</th>
                  <th class="border-0">Destination</th>
                  <th class="border-0">Date & Time</th>
                  <th class="border-0">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><strong class="text-primary">#<?= htmlspecialchars($row['id']); ?></strong></td>
                    <td><i class="bi bi-person-fill text-primary"></i> <?= htmlspecialchars($row['name']); ?></td>
                    <td><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($row['location']); ?></td>
                    <td><i class="bi bi-geo-alt-fill text-success"></i> <?= htmlspecialchars($row['destination']); ?></td>
                    <td class="text-muted"><i class="bi bi-clock"></i> <?= date('M d, Y h:i A', strtotime($row['booking_time'])); ?></td>
                    <td>
                      <span class="badge bg-success text-white" style="padding: 6px 12px; border-radius: 20px;">
                        <i class="bi bi-check-circle-fill"></i> Completed
                      </span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Active Requests Card View -->
      <?php while($row = $result->fetch_assoc()): ?>
        <?php $status = strtolower(trim($row['status'])); ?>
        <div class="card border-0 shadow-sm mb-3" style="border-left: 4px solid <?= ($status == 'pending') ? '#6c757d' : (($status == 'accepted') ? '#0dcaf0' : '#198754'); ?> !important;">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h5 class="card-title mb-1">
                  <i class="bi bi-person-circle text-primary"></i> 
                  <?= htmlspecialchars($row['name']); ?>
                </h5>
                <small class="text-muted">Booking #<?= htmlspecialchars($row['id']); ?></small>
              </div>
              <span class="badge <?= ($status == 'pending') ? 'bg-warning text-dark' : (($status == 'accepted') ? 'bg-info text-white' : 'bg-success text-white'); ?>" 
                    style="padding: 8px 15px; border-radius: 20px; font-size: 14px;">
                <?php if ($status == 'pending'): ?>
                  <i class="bi bi-clock-fill"></i> Pending
                <?php elseif ($status == 'accepted'): ?>
                  <i class="bi bi-check-circle-fill"></i> Accepted
                <?php else: ?>
                  <i class="bi bi-check-circle-fill"></i> <?= ucfirst($status); ?>
                <?php endif; ?>
              </span>
            </div>

            <div class="mb-3">
              <p class="mb-2">
                <i class="bi bi-geo-alt text-danger"></i> 
                <strong>Pickup:</strong> <?= htmlspecialchars($row['location']); ?>
              </p>
              <p class="mb-2">
                <i class="bi bi-geo-alt-fill text-success"></i> 
                <strong>Destination:</strong> <?= htmlspecialchars($row['destination']); ?>
              </p>
              <p class="mb-0 text-muted">
                <i class="bi bi-clock"></i> 
                <?= date('M d, Y h:i A', strtotime($row['booking_time'])); ?>
              </p>
            </div>

            <div class="action-buttons d-flex gap-2">
              <?php if ($status == 'pending'): ?>
                <?php if ($verification_status === 'verified'): ?>
                  <form method="POST" action="" class="flex-fill">
                    <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                    <button type="submit" name="accept_ride" class="btn btn-success w-100" onclick="return confirm('Accept this ride?')">
                      <i class="bi bi-check-circle-fill"></i> Accept Ride
                    </button>
                  </form>
                <?php else: ?>
                  <button type="button" class="btn btn-secondary w-100" disabled style="opacity: 0.6;">
                    <i class="bi bi-lock-fill"></i> Verification Required
                  </button>
                <?php endif; ?>
              <?php elseif ($status == 'accepted' && $row['driver_id'] == $driver_id): ?>
                <button type="button" class="btn btn-info flex-fill" disabled>
                  <i class="bi bi-check-circle-fill"></i> You Accepted This Ride
                </button>
                <form method="POST" action="" class="flex-fill">
                  <input type="hidden" name="booking_id" value="<?= $row['id']; ?>">
                  <button type="submit" name="complete_ride" class="btn btn-primary w-100" onclick="return confirm('Mark this ride as completed?')">
                    <i class="bi bi-flag-fill"></i> Complete Ride
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  <?php else: ?>
    <div class="card border-0 shadow-sm text-center p-5">
      <div class="empty-state-icon" style="font-size: 80px;">
        <?= $show_history ? '📋' : '📭'; ?>
      </div>
      <h4 class="mt-3">
        <?= $show_history ? 'No Trip History Yet' : 'No Active Requests'; ?>
      </h4>
      <p class="text-muted mb-0">
        <?php if ($show_history): ?>
          Start accepting rides to build your trip history and track your earnings!
        <?php else: ?>
          No ride requests available at the moment.<br>
          Check back soon for new booking opportunities!
        <?php endif; ?>
      </p>
    </div>
  <?php endif; ?>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
