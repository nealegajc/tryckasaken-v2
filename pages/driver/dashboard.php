<?php
session_start();
require_once '../../config/Database.php';

$database = new Database();
$conn = $database->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'driver') {
    header("Location: ../../pages/auth/login-form.php");
    exit();
}

$driver_id = $_SESSION['user_id'];
$driver_name = $_SESSION['name'];

// Check driver verification status and online status
$verification_query = "SELECT verification_status, is_online FROM drivers WHERE user_id = ?";
$stmt = $conn->prepare($verification_query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$verification_result = $stmt->get_result()->fetch_assoc();
$verification_status = $verification_result ? $verification_result['verification_status'] : 'pending';
$is_online = $verification_result ? $verification_result['is_online'] : 0;
$stmt->close();

// Check if driver has an active trip (allow access if they do)
$active_trip_check = "SELECT COUNT(*) as has_active FROM tricycle_bookings WHERE driver_id = ? AND LOWER(status) = 'accepted'";
$stmt = $conn->prepare($active_trip_check);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$active_result = $stmt->get_result()->fetch_assoc();
$has_active_trip = $active_result['has_active'] > 0;
$stmt->close();

// Block access if driver is offline and has no active trip
if (!$is_online && !$has_active_trip) {
    $_SESSION['error_message'] = "You must be online to view available requests. Please go to your dashboard and toggle your status to Online.";
    header("Location: login-form.php");
    exit();
}

// Prevent unverified drivers from accepting rides
if (isset($_POST['accept_ride']) && $verification_status !== 'verified') {
    $_SESSION['error_message'] = "You must be verified before accepting ride requests.";
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Prevent offline drivers from accepting NEW rides (but allow completing existing trips)
if (isset($_POST['accept_ride']) && !$is_online) {
    $_SESSION['error_message'] = "You must be online to accept new ride requests.";
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
        // Accept the ride
        $update_sql = "UPDATE tricycle_bookings 
                       SET driver_id = ?, status = 'accepted' 
                       WHERE id = ? AND (status = 'pending' OR status = 'Pending')";
        $stmt = $conn->prepare($update_sql);

        if (!$stmt) die("SQL Error: " . $conn->error);

        $stmt->bind_param("ii", $driver_id, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Update driver status to "busy" (is_online = 1 since they're still working, just unavailable for new trips)
            // The frontend will show "On Trip" status based on has_active_trip
            $_SESSION['success_message'] = "Ride accepted successfully! You are now on a trip.";
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

// Show pending requests and driver's active trips
$sql = "SELECT * FROM tricycle_bookings 
        WHERE (LOWER(TRIM(status)) = 'pending') 
           OR (LOWER(TRIM(status)) = 'accepted' AND driver_id = ?)
        ORDER BY booking_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);

$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>View Requests | TrycKaSaken</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../../public/css/request.css">
  <style>
    body {
      padding-top: 100px;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.95) !important;
      color: white !important;
      border: 2px solid #10b981 !important;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      border-radius: 12px;
      padding: 16px 20px;
    }

    .alert-danger {
      background: rgba(220, 38, 38, 0.95) !important;
      color: white !important;
      border: 2px solid #dc2626 !important;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
      border-radius: 12px;
      padding: 16px 20px;
    }

    .alert .btn-close {
      filter: brightness(0) invert(1);
      opacity: 0.8;
    }

    .navbar-fixed {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(16, 185, 129, 0.2);
      padding: 12px 0;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .navbar-brand {
      font-size: 1.3rem;
      font-weight: 700;
      color: #10b981;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .navbar-brand:hover {
      color: #047857;
    }

    .navbar-links {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .nav-link-btn {
      padding: 8px 16px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 0.9rem;
    }

    .nav-link-primary {
      background: linear-gradient(135deg, #10b981, #047857);
      color: white;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .nav-link-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
      color: white;
    }

    .nav-link-secondary {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .nav-link-secondary:hover {
      background: rgba(16, 185, 129, 0.2);
      color: #047857;
    }

    .menu-toggle {
      display: none;
      background: linear-gradient(135deg, #10b981, #047857);
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.2rem;
    }

    @media (max-width: 768px) {
      body {
        padding-top: 80px;
      }

      .navbar-brand {
        font-size: 1.1rem;
      }

      .navbar-brand i {
        font-size: 1.2rem;
      }

      .menu-toggle {
        display: block;
      }

      .navbar-links {
        position: fixed;
        top: 60px;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(20px);
        flex-direction: column;
        padding: 20px;
        gap: 12px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        transform: translateY(-100%);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
      }

      .navbar-links.active {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
      }

      .nav-link-btn {
        width: 100%;
        justify-content: center;
        padding: 12px 20px;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>

<!-- Fixed Navigation Bar -->
<nav class="navbar-fixed">
  <div class="navbar-content">
    <a href="../../pages/driver/login-form.php" class="navbar-brand">
      <i class="bi bi-truck"></i>
      <span>TrycKaSaken</span>
    </a>
    <button class="menu-toggle" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>
    <div class="navbar-links" id="navbarLinks">
      <a href="../../pages/driver/login-form.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="../../pages/driver/trips-history.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-clock-history"></i> History
      </a>
      <a href="../../pages/auth/logout-handler.php" class="nav-link-btn nav-link-primary">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <h2> Available Ride Requests</h2>
    <p>Accept and manage your ride bookings</p>
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
    <!-- Available Requests Cards -->
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
                  <button type="button" class="btn btn-success w-100" onclick="acceptRide(<?= $row['id']; ?>, this)">
                    <i class="bi bi-check-circle-fill"></i> Accept Ride
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-secondary w-100" disabled style="opacity: 0.6;">
                    <i class="bi bi-lock-fill"></i> Verification Required
                  </button>
                <?php endif; ?>
              <?php elseif ($status == 'accepted' && $row['driver_id'] == $driver_id): ?>
                <button type="button" class="btn btn-info flex-fill" disabled>
                  <i class="bi bi-check-circle-fill"></i> You Accepted This Ride
                </button>
                <button type="button" class="btn btn-primary flex-fill" onclick="completeRide(<?= $row['id']; ?>, this)">
                  <i class="bi bi-flag-fill"></i> Complete Ride
                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
  <?php else: ?>
    <div class="card border-0 shadow-sm text-center p-5">
      <div class="empty-state-icon" style="font-size: 80px;">
        
      </div>
      <h4 class="mt-3">
        No Active Requests
      </h4>
      <p class="text-muted mb-0">
        Check back later for new ride requests from passengers!
      </p>
    </div>
  <?php endif; ?>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toast Notification Styles -->
<style>
.toast-container {
  position: fixed;
  top: 80px;
  right: 20px;
  z-index: 9999;
}

.custom-toast {
  min-width: 300px;
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border: none;
  border-left: 4px solid;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  margin-bottom: 10px;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.custom-toast.toast-success {
  border-left-color: #198754;
}

.custom-toast.toast-error {
  border-left-color: #dc3545;
}

.custom-toast.toast-info {
  border-left-color: #0dcaf0;
}

.custom-toast .toast-header {
  background: transparent;
  border: none;
  font-weight: 600;
}

.custom-toast .toast-body {
  padding: 12px 16px;
}

.btn-loading {
  position: relative;
  pointer-events: none;
  opacity: 0.7;
}

.btn-loading::after {
  content: '';
  position: absolute;
  width: 16px;
  height: 16px;
  top: 50%;
  left: 50%;
  margin-left: -8px;
  margin-top: -8px;
  border: 2px solid #ffffff;
  border-radius: 50%;
  border-top-color: transparent;
  animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
  to { transform: rotate(360deg); }
}
</style>

<!-- Toast Container -->
<div class="toast-container"></div>

<script>
// Toast notification function
function showToast(message, type = 'info') {
  const toastContainer = document.querySelector('.toast-container');
  const toastId = 'toast-' + Date.now();
  
  const toastHTML = `
    <div id="${toastId}" class="toast custom-toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="bi bi-${type === 'success' ? 'check-circle-fill text-success' : type === 'error' ? 'x-circle-fill text-danger' : 'info-circle-fill text-info'} me-2"></i>
        <strong class="me-auto">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body">
        ${message}
      </div>
    </div>
  `;
  
  toastContainer.insertAdjacentHTML('beforeend', toastHTML);
  const toastElement = document.getElementById(toastId);
  const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
  toast.show();
  
  // Remove toast element after it's hidden
  toastElement.addEventListener('hidden.bs.toast', function() {
    toastElement.remove();
  });
}

// AJAX: Accept ride
function acceptRide(bookingId, button) {
  if (!confirm('Accept this ride?')) return;
  
  // Add loading state
  button.classList.add('btn-loading');
  button.disabled = true;
  
  const formData = new FormData();
  formData.append('action', 'accept_ride');
  formData.append('booking_id', bookingId);
  
  fetch('api-driver-actions.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    button.classList.remove('btn-loading');
    button.disabled = false;
    
    if (data.success) {
      showToast(data.message, 'success');
      // Reload page to update the requests list
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } else {
      showToast(data.message, 'error');
    }
  })
  .catch(error => {
    button.classList.remove('btn-loading');
    button.disabled = false;
    showToast('An error occurred. Please try again.', 'error');
    console.error('Error:', error);
  });
}

// AJAX: Complete ride
function completeRide(bookingId, button) {
  if (!confirm('Mark this ride as completed?')) return;
  
  // Add loading state
  button.classList.add('btn-loading');
  button.disabled = true;
  
  const formData = new FormData();
  formData.append('action', 'complete_ride');
  formData.append('booking_id', bookingId);
  
  fetch('api-driver-actions.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    button.classList.remove('btn-loading');
    button.disabled = false;
    
    if (data.success) {
      showToast(data.message, 'success');
      // Reload page to update the requests list
      setTimeout(() => {
        window.location.reload();
      }, 1000);
    } else {
      showToast(data.message, 'error');
    }
  })
  .catch(error => {
    button.classList.remove('btn-loading');
    button.disabled = false;
    showToast('An error occurred. Please try again.', 'error');
    console.error('Error:', error);
  });
}

// Auto-refresh requests every 30 seconds
setInterval(function() {
  fetch('api-driver-actions.php?action=check_status')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // If counts changed, show notification and offer to reload
        const currentCount = document.querySelectorAll('.card').length - 1; // Subtract empty state card
        const newCount = data.data.pending_requests + data.data.active_trips;
        
        if (newCount !== currentCount && newCount > 0) {
          // Page has updates available
          const notification = document.createElement('div');
          notification.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 10000; background: rgba(13, 202, 240, 0.95); color: white; padding: 12px 24px; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); cursor: pointer;';
          notification.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i> New requests available. Click to refresh.';
          notification.onclick = () => window.location.reload();
          document.body.appendChild(notification);
        }
      }
    })
    .catch(error => console.error('Status check error:', error));
}, 30000); // Check every 30 seconds

// Menu functions
function toggleMenu() {
  const navbarLinks = document.getElementById('navbarLinks');
  const menuToggle = document.querySelector('.menu-toggle i');
  navbarLinks.classList.toggle('active');
  
  // Toggle icon between menu and close
  if (navbarLinks.classList.contains('active')) {
    menuToggle.classList.remove('bi-list');
    menuToggle.classList.add('bi-x-lg');
  } else {
    menuToggle.classList.remove('bi-x-lg');
    menuToggle.classList.add('bi-list');
  }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
  const navbarLinks = document.getElementById('navbarLinks');
  const menuToggle = document.querySelector('.menu-toggle');
  const navbar = document.querySelector('.navbar-fixed');
  
  if (!navbar.contains(event.target) && navbarLinks.classList.contains('active')) {
    navbarLinks.classList.remove('active');
    menuToggle.querySelector('i').classList.remove('bi-x-lg');
    menuToggle.querySelector('i').classList.add('bi-list');
  }
});

// Close menu when clicking a link
document.querySelectorAll('.nav-link-btn').forEach(link => {
  link.addEventListener('click', function() {
    const navbarLinks = document.getElementById('navbarLinks');
    const menuToggle = document.querySelector('.menu-toggle i');
    navbarLinks.classList.remove('active');
    menuToggle.classList.remove('bi-x-lg');
    menuToggle.classList.add('bi-list');
  });
});
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
