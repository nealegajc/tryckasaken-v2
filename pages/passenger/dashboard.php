<?php
session_start();
require_once '../../config/Database.php';

// Check if user is logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'passenger') {
    header("Location: ../../pages/auth/login-form.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Handle cancel booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking_id'])) {
    $booking_id = intval($_POST['cancel_booking_id']);
    
    // Check if booking exists and is cancellable (pending or accepted but check driver acceptance)
    $check_stmt = $conn->prepare("SELECT status, driver_id FROM tricycle_bookings WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $booking = $check_stmt->get_result()->fetch_assoc();
    
    if ($booking && (strtolower($booking['status']) === 'pending' || (strtolower($booking['status']) === 'accepted' && !$booking['driver_id']))) {
        $cancel_stmt = $conn->prepare("UPDATE tricycle_bookings SET status = 'cancelled' WHERE id = ?");
        $cancel_stmt->bind_param("i", $booking_id);
        if ($cancel_stmt->execute()) {
            $_SESSION['success_message'] = 'Booking cancelled successfully!';
        }
        $cancel_stmt->close();
    }
    $check_stmt->close();
    header("Location: login-form.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $destination = trim($_POST['destination']);

    if (empty($name) || empty($location) || empty($destination)) {
        $_SESSION['error_message'] = 'Please fill in all fields!';
        header("Location: login-form.php");
        exit;
    }

    // Insert booking with user_id
    $stmt = $conn->prepare("INSERT INTO tricycle_bookings (user_id, name, location, destination, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $user_id, $name, $location, $destination);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Booking successful!';
        header("Location: login-form.php");
    } else {
        $_SESSION['error_message'] = 'Booking failed. Please try again.';
        header("Location: login-form.php");
    }

    $stmt->close();
    exit;
}

// Fetch current ACTIVE booking with driver information (exclude completed and cancelled)
$stmt = $conn->prepare("
    SELECT 
        tb.*,
        u.name as driver_name,
        u.phone as driver_phone,
        u.tricycle_info as vehicle_info
    FROM tricycle_bookings tb
    LEFT JOIN users u ON tb.driver_id = u.user_id
    WHERE tb.user_id = ? 
    AND LOWER(tb.status) NOT IN ('completed', 'cancelled', 'declined')
    ORDER BY tb.booking_time DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$latestBooking = $result->fetch_assoc();
$stmt->close();

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Book a Tricycle | TrycKaSaken</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      min-height: 100vh;
      padding: 120px 20px 40px 20px;
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    /* Fixed Navbar Styles */
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
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
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
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.2rem;
    }
    .menu-toggle {
      display: none;
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.2rem;
    }
    
    .book-container {
      max-width: 900px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .back-link {
      display: none; /* Hide back link since we have navbar */
    }
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.3);
      border-radius: 12px;
      color: white;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 24px;
      transition: all 0.3s ease;
    }
    
    .back-link:hover {
      transform: translateY(-2px);
      color: white;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    
    .page-header {
      text-align: center;
      margin-bottom: 36px;
    }
    
    .page-header h1 {
      font-size: 42px;
      font-weight: 700;
      color: white;
      margin-bottom: 8px;
    }
    
    .page-header p {
      color: rgba(255,255,255,0.8);
      font-size: 16px;
    }
    
    .form-section {
      background: rgba(255,255,255,0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      border: 1px solid rgba(255,255,255,0.3);
      padding: 36px;
      margin-bottom: 32px;
      box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .form-section h3 {
      font-size: 24px;
      font-weight: 700;
      color: #16a34a;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .form-section h3 i {
      color: #10b981;
    }
    
    .btn-book {
      width: 100%;
      padding: 16px 32px;
      background: linear-gradient(135deg, #10b981, #047857);
      border: none;
      border-radius: 14px;
      color: white;
      font-weight: 600;
      font-size: 16px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    
    .btn-book:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
      color: white;
    }
    
    .status-card {
      background: rgba(255,255,255,0.95);
      border-radius: 20px;
      padding: 32px;
      margin-top: 32px;
    }
    
    .cancel-btn {
      background: linear-gradient(135deg, #dc2626, #991b1b);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 16px;
    }
    
    .cancel-btn:hover {
      transform: translateY(-2px);
      color: white;
    }

    @media (max-width: 768px) {
      body {
        padding-top: 90px;
      }

      .navbar-brand {
        font-size: 1.1rem;
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
    <a href="../../pages/passenger/login-form.php" class="navbar-brand">
      <i class="bi bi-truck"></i>
      <span>TrycKaSaken</span>
    </a>
    <button class="menu-toggle" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>
    <div class="navbar-links" id="navbarLinks">
      <a href="../../pages/passenger/login-form.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="../../pages/passenger/trips-history.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-clock-history"></i> Trip History
      </a>
      <a href="../../pages/auth/logout-handler.php" class="nav-link-btn nav-link-primary">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

  <div class="book-container">
    <a href="../../pages/passenger/login-form.php" class="back-link">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="page-header">
      <h1><i class="bi bi-truck"></i> Book a Tricycle</h1>
      <p>Your reliable tricycle booking service</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div style="background: rgba(16,185,129,0.95); border: 2px solid #10b981; color: white; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(16,185,129,0.3);">
        <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div style="background: rgba(220,38,38,0.95); border: 2px solid #dc2626; color: white; padding: 16px; border-radius: 12px; margin-bottom: 24px; font-weight: 600; box-shadow: 0 4px 12px rgba(220,38,38,0.3);">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
      </div>
    <?php endif; ?>

    <?php
    // Show form only if no active booking (pending or accepted with no driver)
    $hasActiveBooking = $latestBooking && (
        (strtolower($latestBooking['status']) === 'pending') || 
        (strtolower($latestBooking['status']) === 'accepted' && $latestBooking['driver_id'])
    );
    
    if (!$hasActiveBooking):
    ?>
      <section class="form-section">
        <h3><i class="bi bi-plus-circle-fill"></i> Create New Booking</h3>
        <form method="post">
          <div class="mb-3">
            <label for="name" class="form-label" style="color: #16a34a; font-weight: 600;">
              <i class="bi bi-person-fill"></i> Full Name
            </label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user_name); ?>" required>
          </div>
          <div class="mb-3">
            <label for="location" class="form-label" style="color: #16a34a; font-weight: 600;">
              <i class="bi bi-geo-alt-fill"></i> Pickup Location
            </label>
            <input type="text" class="form-control" name="location" placeholder="Where should we pick you up?" required>
          </div>
          <div class="mb-3">
            <label for="destination" class="form-label" style="color: #16a34a; font-weight: 600;">
              <i class="bi bi-flag-fill"></i> Destination
            </label>
            <input type="text" class="form-control" name="destination" placeholder="Where are you going?" required>
          </div>
          <div class="text-center mt-4">
            <button type="submit" class="btn-book">
              <i class="bi bi-calendar-check-fill"></i> Book Now
            </button>
          </div>
        </form>
      </section>
    <?php else: ?>
      <div style="background: rgba(245,158,11,0.95); border: 2px solid #f59e0b; color: white; padding: 24px; border-radius: 12px; margin-bottom: 32px; font-weight: 600; box-shadow: 0 4px 12px rgba(245,158,11,0.3);">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; margin-right: 15px;"></i>
        <strong>Active Booking in Progress</strong>
        <p style="margin-top: 8px; margin-bottom: 0;">You already have an active booking. Please wait for it to be completed before creating a new one.</p>
      </div>
    <?php endif; ?>

    <?php if ($latestBooking): ?>
    <section class="status-card" id="currentBookingSection">
      <h4 style="color: #16a34a; font-weight: 700; margin-bottom: 20px;">
        <i class="bi bi-card-text"></i> Current Booking Status
      </h4>
      <div id="bookingContent">
      <div class="row align-items-start">
        <div class="col-md-8">
          <h5 style="color: #16a34a; font-weight: 700; margin-bottom: 12px;">
            Booking #<?= htmlspecialchars($latestBooking['id']); ?>
          </h5>
          <p class="mb-2" style="color: #16a34a;">
            <i class="bi bi-geo-alt-fill" style="color: #dc2626;"></i> 
            <strong>Pickup:</strong> <?= htmlspecialchars($latestBooking['location']); ?>
          </p>
          <p class="mb-2" style="color: #16a34a;">
            <i class="bi bi-flag-fill" style="color: #10b981;"></i> 
            <strong>Destination:</strong> <?= htmlspecialchars($latestBooking['destination']); ?>
          </p>
          <p class="mb-3" style="color: #6c757d;">
            <i class="bi bi-clock-fill"></i> <?= date('M d, Y h:i A', strtotime($latestBooking['booking_time'])); ?>
          </p>

          <?php if (!empty($latestBooking['driver_id'])): ?>
          <!-- Driver Information Section -->
          <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid rgba(102, 126, 234, 0.2);">
            <h6 style="color: #667eea; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
              <i class="bi bi-person-badge"></i>
              Driver Information
            </h6>
            <div style="display: grid; gap: 12px;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-person-circle" style="color: #667eea; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Driver Name</div>
                  <div style="color: #2c3e50; font-weight: 600;"><?= htmlspecialchars($latestBooking['driver_name'] ?? 'N/A'); ?></div>
                </div>
              </div>
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-telephone-fill" style="color: #10b981; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Phone Number</div>
                  <div style="color: #2c3e50; font-weight: 600;">
                    <?php if (!empty($latestBooking['driver_phone'])): ?>
                      <a href="tel:<?= htmlspecialchars($latestBooking['driver_phone']); ?>" style="color: #10b981; text-decoration: none;">
                        <?= htmlspecialchars($latestBooking['driver_phone']); ?>
                      </a>
                    <?php else: ?>
                      N/A
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php if (!empty($latestBooking['vehicle_info'])): ?>
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-truck" style="color: #ef4444; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Vehicle Info</div>
                  <div style="color: #2c3e50; font-weight: 600;"><?= htmlspecialchars($latestBooking['vehicle_info']); ?></div>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <div class="col-md-4 text-center">
          <?php 
          $status = strtolower($latestBooking['status']);
          $badge_style = '';
          $icon = '';
          
          if ($status == 'pending') {
              $badge_style = 'background: rgba(245,158,11,0.3); color: #f59e0b; border: 1px solid #f59e0b;';
              $icon = '<i class="bi bi-clock-fill"></i>';
          } elseif ($status == 'accepted') {
              $badge_style = 'background: rgba(16,185,129,0.3); color: #10b981; border: 1px solid #10b981;';
              $icon = '<i class="bi bi-check-circle-fill"></i>';
          } elseif ($status == 'completed') {
              $badge_style = 'background: rgba(16,185,129,0.3); color: #10b981; border: 1px solid #10b981;';
              $icon = '<i class="bi bi-check-circle-fill"></i>';
          } elseif ($status == 'cancelled') {
              $badge_style = 'background: rgba(220,38,38,0.3); color: #dc2626; border: 1px solid #dc2626;';
              $icon = '<i class="bi bi-x-circle-fill"></i>';
          }
          ?>
          <span style="display: inline-block; padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; <?= $badge_style; ?>">
            <?= $icon; ?> <?= htmlspecialchars(ucfirst($latestBooking['status'])); ?>
          </span>
          
          <!-- Added cancel booking button for pending bookings without driver -->
          <?php if (strtolower($latestBooking['status']) === 'pending' || (strtolower($latestBooking['status']) === 'accepted' && !$latestBooking['driver_id'])): ?>
            <form method="post" style="margin-top: 16px;">
              <input type="hidden" name="cancel_booking_id" value="<?= $latestBooking['id'] ?>">
              <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?');">
                <i class="bi bi-x-circle"></i> Cancel Booking
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <script>
    // AJAX: Check booking status periodically
    let previousStatus = null;
    let previousDriverId = null;

    function checkBookingStatus() {
      fetch('api-booking-actions.php?action=get_booking_status')
        .then(response => response.json())
        .then(data => {
          if (data.success && data.booking) {
            const booking = data.booking;
            const currentStatus = booking.status;
            const currentDriverId = booking.driver_id;
            
            // Check if status changed or driver was assigned
            if (previousStatus !== null) {
              if (previousStatus !== currentStatus) {
                // Status changed - show notification and reload
                showStatusNotification('Booking status updated to: ' + currentStatus.toUpperCase());
                setTimeout(() => window.location.reload(), 2000);
                return;
              }
              
              if (previousDriverId === null && currentDriverId !== null) {
                // Driver was assigned - show notification and reload
                showStatusNotification('A driver has been assigned to your booking!');
                setTimeout(() => window.location.reload(), 2000);
                return;
              }
            }
            
            previousStatus = currentStatus;
            previousDriverId = currentDriverId;
            
            // Update the booking display
            updateBookingDisplay(booking);
          } else if (!data.booking && previousStatus !== null) {
            // Booking was completed or cancelled - reload page
            showStatusNotification('Your booking has been updated!');
            setTimeout(() => window.location.reload(), 2000);
          }
        })
        .catch(error => {
          console.error('Status check error:', error);
        });
    }

    function updateBookingDisplay(booking) {
      const bookingContent = document.getElementById('bookingContent');
      if (!bookingContent) return;
      
      const status = booking.status.toLowerCase();
      let badgeStyle = '';
      let icon = '';
      
      if (status === 'pending') {
        badgeStyle = 'background: rgba(245,158,11,0.3); color: #f59e0b; border: 1px solid #f59e0b;';
        icon = '<i class="bi bi-clock-fill"></i>';
      } else if (status === 'accepted') {
        badgeStyle = 'background: rgba(16,185,129,0.3); color: #10b981; border: 1px solid #10b981;';
        icon = '<i class="bi bi-check-circle-fill"></i>';
      } else if (status === 'completed') {
        badgeStyle = 'background: rgba(16,185,129,0.3); color: #10b981; border: 1px solid #10b981;';
        icon = '<i class="bi bi-check-circle-fill"></i>';
      } else if (status === 'cancelled') {
        badgeStyle = 'background: rgba(220,38,38,0.3); color: #dc2626; border: 1px solid #dc2626;';
        icon = '<i class="bi bi-x-circle-fill"></i>';
      }
      
      // Format booking time
      const bookingDate = new Date(booking.booking_time);
      const formattedDate = bookingDate.toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      });
      
      let driverHtml = '';
      if (booking.driver_id) {
        driverHtml = `
          <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid rgba(102, 126, 234, 0.2);">
            <h6 style="color: #667eea; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
              <i class="bi bi-person-badge"></i>
              Driver Information
            </h6>
            <div style="display: grid; gap: 12px;">
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-person-circle" style="color: #667eea; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Driver Name</div>
                  <div style="color: #2c3e50; font-weight: 600;">${booking.driver_name || 'N/A'}</div>
                </div>
              </div>
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-telephone-fill" style="color: #10b981; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Phone Number</div>
                  <div style="color: #2c3e50; font-weight: 600;">
                    ${booking.driver_phone ? `<a href="tel:${booking.driver_phone}" style="color: #10b981; text-decoration: none;">${booking.driver_phone}</a>` : 'N/A'}
                  </div>
                </div>
              </div>
              ${booking.vehicle_info ? `
              <div style="display: flex; align-items: center; gap: 10px;">
                <i class="bi bi-truck" style="color: #ef4444; font-size: 1.2rem;"></i>
                <div>
                  <div style="font-size: 0.85rem; color: #6c757d; font-weight: 600;">Vehicle Info</div>
                  <div style="color: #2c3e50; font-weight: 600;">${booking.vehicle_info}</div>
                </div>
              </div>
              ` : ''}
            </div>
          </div>
        `;
      }
      
      let cancelBtn = '';
      if (status === 'pending' || (status === 'accepted' && !booking.driver_id)) {
        cancelBtn = `
          <form method="post" style="margin-top: 16px;">
            <input type="hidden" name="cancel_booking_id" value="${booking.id}">
            <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this booking?');">
              <i class="bi bi-x-circle"></i> Cancel Booking
            </button>
          </form>
        `;
      }
      
      bookingContent.innerHTML = `
        <div class="row align-items-start">
          <div class="col-md-8">
            <h5 style="color: #16a34a; font-weight: 700; margin-bottom: 12px;">
              Booking #${booking.id}
            </h5>
            <p class="mb-2" style="color: #16a34a;">
              <i class="bi bi-geo-alt-fill" style="color: #dc2626;"></i> 
              <strong>Pickup:</strong> ${booking.location}
            </p>
            <p class="mb-2" style="color: #16a34a;">
              <i class="bi bi-flag-fill" style="color: #10b981;"></i> 
              <strong>Destination:</strong> ${booking.destination}
            </p>
            <p class="mb-3" style="color: #6c757d;">
              <i class="bi bi-clock-fill"></i> ${formattedDate}
            </p>
            ${driverHtml}
          </div>
          <div class="col-md-4 text-center">
            <span style="display: inline-block; padding: 10px 20px; border-radius: 12px; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; ${badgeStyle}">
              ${icon} ${status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
            ${cancelBtn}
          </div>
        </div>
      `;
    }

    function showStatusNotification(message) {
      const notification = document.createElement('div');
      notification.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        background: rgba(16, 185, 129, 0.95);
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease-out;
      `;
      notification.innerHTML = `
        <i class="bi bi-check-circle-fill" style="font-size: 1.2rem;"></i>
        <span>${message}</span>
      `;
      document.body.appendChild(notification);
      
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
      }, 3000);
    }

    // Start checking booking status every 15 seconds if there's an active booking
    <?php if ($latestBooking): ?>
    setInterval(checkBookingStatus, 15000);
    // Set initial status
    previousStatus = '<?= strtolower($latestBooking['status']); ?>';
    previousDriverId = <?= !empty($latestBooking['driver_id']) ? $latestBooking['driver_id'] : 'null'; ?>;
    <?php endif; ?>

    // Mobile menu toggle
    function toggleMenu() {
      const menu = document.getElementById('navbarLinks');
      const menuIcon = document.querySelector('.menu-toggle i');
      
      menu.classList.toggle('active');
      
      // Toggle icon
      if (menu.classList.contains('active')) {
        menuIcon.className = 'bi bi-x-lg';
      } else {
        menuIcon.className = 'bi bi-list';
      }
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
      const menu = document.getElementById('navbarLinks');
      const menuToggle = document.querySelector('.menu-toggle');
      
      if (!menu.contains(event.target) && !menuToggle.contains(event.target)) {
        menu.classList.remove('active');
        document.querySelector('.menu-toggle i').className = 'bi bi-list';
      }
    });

    // Close menu when clicking on a link
    document.querySelectorAll('.navbar-links a').forEach(link => {
      link.addEventListener('click', function() {
        document.getElementById('navbarLinks').classList.remove('active');
        document.querySelector('.menu-toggle i').className = 'bi bi-list';
      });
    });

    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
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
      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  </script>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $database->closeConnection(); ?>
