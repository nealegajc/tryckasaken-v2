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

// Get current active booking with driver info (pending or accepted, not completed/cancelled/declined)
$booking_query = "SELECT b.*, 
                  u.name as driver_name, 
                  u.phone as driver_phone,
                  u.tricycle_info as vehicle_info
                  FROM tricycle_bookings b
                  LEFT JOIN users u ON b.driver_id = u.user_id
                  WHERE b.user_id = ? 
                  AND LOWER(b.status) NOT IN ('completed', 'cancelled', 'declined')
                  ORDER BY b.booking_time DESC 
                  LIMIT 1";
$stmt = $conn->prepare($booking_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Don't close connection yet, we need it for rendering
// $conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Passenger Dashboard - TrycKaSaken</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Glass Design CSS -->
  <link rel="stylesheet" href="../../public/css/glass.css">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --secondary-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      --glass-white: rgba(255, 255, 255, 0.15);
      --glass-border: rgba(255, 255, 255, 0.2);
      --blur-amount: 20px;
      --radius-lg: 16px;
      --radius-xl: 24px;
      --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.1);
      --shadow-medium: 0 20px 60px rgba(0, 0, 0, 0.15);
      --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: var(--primary-gradient);
      min-height: 100vh;
      margin: 0;
      padding: 0;
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

    .navbar {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      padding: 16px 0;
      position: relative;
      z-index: 100;
    }

    .navbar-brand {
      color: white;
      font-weight: 800;
      font-size: 24px;
      text-decoration: none;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .navbar-nav {
      display: flex;
      align-items: center;
      gap: 20px;
      list-style: none;
      margin: 0;
      padding: 0;
      transition: var(--transition-smooth);
    }

    .nav-link {
      color: rgba(255, 255, 255, 0.9);
      text-decoration: none;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 8px;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.1);
      color: white;
    }

    .btn-request {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      padding: 10px 20px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-request:hover {
      background: rgba(255, 255, 255, 0.25);
      color: white;
      transform: translateY(-2px);
    }

    .btn-danger {
      background: #16a34a;
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      text-decoration: none;
      font-weight: 600;
      border: none;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-danger:hover {
      background: #15803d;
      color: white;
      transform: translateY(-2px);
    }

    .navbar-toggler {
      display: none;
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
    }

    .page-content {
      position: relative;
      z-index: 1;
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .welcome-section {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-xl);
      border: 1px solid var(--glass-border);
      padding: 40px;
      margin-bottom: 40px;
      text-align: center;
      animation: slideUp 0.6s ease-out;
    }

    .welcome-section h2 {
      color: white;
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 16px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .welcome-section p {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1.2rem;
      margin: 0;
    }

    .current-booking-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-xl);
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 32px;
      margin-bottom: 40px;
      animation: slideUp 0.6s ease-out;
      box-shadow: var(--shadow-soft);
    }

    .current-booking-section h3 {
      color: #16a34a;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .booking-info-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .booking-info-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .booking-info-item i {
      font-size: 1.3rem;
      margin-top: 2px;
    }

    .booking-info-label {
      color: #6c757d;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .booking-info-value {
      color: #2c3e50;
      font-weight: 500;
      font-size: 1rem;
    }

    .booking-status-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 25px;
      font-weight: 600;
      font-size: 0.95rem;
      margin-top: 16px;
    }

    .status-pending {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
      border: 2px solid #f59e0b;
    }

    .status-accepted {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
      border: 2px solid #10b981;
    }

    .no-booking-message {
      text-align: center;
      padding: 40px 20px;
      color: #6c757d;
    }

    .no-booking-message i {
      font-size: 3rem;
      color: #e0e0e0;
      margin-bottom: 16px;
    }

    .no-booking-message h4 {
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .booking-actions {
      display: flex;
      gap: 12px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .btn-view-details {
      background: var(--primary-gradient);
      color: white;
      padding: 10px 24px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: var(--transition-smooth);
    }

    .btn-view-details:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }

    .service-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-xl);
      padding: 40px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.3);
      transition: var(--transition-smooth);
      animation: slideUp 0.6s ease-out;
    }

    .service-card:nth-child(1) { animation-delay: 0.1s; }
    .service-card:nth-child(2) { animation-delay: 0.2s; }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-medium);
    }

    .service-icon {
      width: 80px;
      height: 80px;
      background: var(--primary-gradient);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      font-size: 2rem;
      color: white;
      transition: var(--transition-smooth);
    }

    .service-card:hover .service-icon {
      transform: scale(1.1);
    }

    .service-card h3 {
      color: #16a34a;
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 16px;
    }

    .service-card p {
      color: #6c757d;
      margin-bottom: 24px;
      line-height: 1.6;
    }

    .service-btn {
      background: var(--primary-gradient);
      color: white;
      padding: 12px 32px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition-smooth);
      display: inline-block;
    }

    .service-btn:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
    }

    .service-btn.disabled {
      background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
      cursor: not-allowed;
      opacity: 0.6;
      pointer-events: none;
    }

    .service-card.disabled {
      opacity: 0.7;
    }

    .service-card.disabled .service-icon {
      background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    }

    .service-card.disabled h3 {
      color: #6b7280;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @media (max-width: 768px) {
      .navbar-nav {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 100%;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        flex-direction: column;
        padding: 20px;
        transition: var(--transition-smooth);
      }

      .navbar-nav.show {
        left: 0;
      }

      .navbar-toggler {
        display: block;
      }

      .nav-link {
        color: #16a34a;
        padding: 12px 16px;
        width: 100%;
        justify-content: center;
      }

      .btn-request {
        background: var(--primary-gradient);
        justify-content: center;
        width: 100%;
      }

      .welcome-section {
        padding: 30px 20px;
        margin: 20px 0;
      }

      .welcome-section h2 {
        font-size: 2rem;
      }

      .page-content {
        padding: 0 15px;
      }

      .services-grid {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .service-card {
        padding: 30px 20px;
      }

      .current-booking-section {
        padding: 24px 20px;
      }

      .booking-info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .booking-actions {
        flex-direction: column;
      }

      .btn-view-details {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <a class="navbar-brand" href="#">
      <i class="bi bi-truck me-2"></i>
      TrycKaSaken
    </a>
    <button class="navbar-toggler" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>
    <ul class="navbar-nav" id="navMenu">
      <li class="nav-item">
        <a class="nav-link" href="../../pages/passenger/login-form.php">
          <i class="bi bi-house"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a href="../../pages/passenger/dashboard.php" class="btn-request">
          <i class="bi bi-plus-circle"></i> Book Ride
        </a>
      </li>
      <li class="nav-item">
        <a href="../../pages/passenger/trips-history.php" class="nav-link">
          <i class="bi bi-clock-history"></i> Trip History
        </a>
      </li>
      <li class="nav-item">
        <a href="../../pages/auth/logout-handler.php" class="btn btn-danger btn-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>
  </div>
</nav>

<div class="page-content">
  <!-- Enhanced Welcome Section -->
  <div class="welcome-section">
    <h2>Hello <?= htmlspecialchars($user_name); ?>!</h2>
    <p>Book a tricycle and get to your destination safely and quickly.</p>
  </div>

  <!-- Current Booking Status Section -->
  <div class="current-booking-section" id="currentBookingSection">
    <h3>
      <i class="bi bi-card-checklist"></i>
      Current Booking Status
    </h3>
    
    <div id="bookingContent">
    <?php if ($current_booking): ?>
      <div class="booking-info-grid">
        <div class="booking-info-item">
          <i class="bi bi-hash" style="color: #667eea;"></i>
          <div>
            <div class="booking-info-label">Booking ID</div>
            <div class="booking-info-value">#<?= htmlspecialchars($current_booking['id']); ?></div>
          </div>
        </div>

        <div class="booking-info-item">
          <i class="bi bi-geo-alt-fill" style="color: #ef4444;"></i>
          <div>
            <div class="booking-info-label">Pickup Location</div>
            <div class="booking-info-value"><?= htmlspecialchars($current_booking['location']); ?></div>
          </div>
        </div>

        <div class="booking-info-item">
          <i class="bi bi-flag-fill" style="color: #10b981;"></i>
          <div>
            <div class="booking-info-label">Destination</div>
            <div class="booking-info-value"><?= htmlspecialchars($current_booking['destination']); ?></div>
          </div>
        </div>

        <div class="booking-info-item">
          <i class="bi bi-calendar-event" style="color: #f59e0b;"></i>
          <div>
            <div class="booking-info-label">Booking Time</div>
            <div class="booking-info-value"><?= date('M d, Y h:i A', strtotime($current_booking['booking_time'])); ?></div>
          </div>
        </div>
      </div>

      <?php if (!empty($current_booking['driver_id'])): ?>
        <!-- Driver Information Section -->
        <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid rgba(102, 126, 234, 0.2);">
          <h4 style="color: #667eea; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="bi bi-person-badge"></i>
            Driver Information
          </h4>
          <div class="booking-info-grid">
            <div class="booking-info-item">
              <i class="bi bi-person-circle" style="color: #667eea;"></i>
              <div>
                <div class="booking-info-label">Driver Name</div>
                <div class="booking-info-value"><?= htmlspecialchars($current_booking['driver_name'] ?? 'N/A'); ?></div>
              </div>
            </div>

            <div class="booking-info-item">
              <i class="bi bi-telephone-fill" style="color: #10b981;"></i>
              <div>
                <div class="booking-info-label">Phone Number</div>
                <div class="booking-info-value">
                  <?php if (!empty($current_booking['driver_phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($current_booking['driver_phone']); ?>" style="color: #10b981; text-decoration: none; font-weight: 600;">
                      <?= htmlspecialchars($current_booking['driver_phone']); ?>
                    </a>
                  <?php else: ?>
                    N/A
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <?php if (!empty($current_booking['vehicle_info'])): ?>
            <div class="booking-info-item">
              <i class="bi bi-truck" style="color: #ef4444;"></i>
              <div>
                <div class="booking-info-label">Vehicle Info</div>
                <div class="booking-info-value"><?= htmlspecialchars($current_booking['vehicle_info']); ?></div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <div>
        <?php 
        $status = strtolower($current_booking['status']);
        $status_class = 'status-pending';
        $status_icon = 'bi-hourglass-split';
        
        if ($status === 'accepted') {
          $status_class = 'status-accepted';
          $status_icon = 'bi-check-circle-fill';
        }
        ?>
        <span class="booking-status-badge <?= $status_class; ?>">
          <i class="bi <?= $status_icon; ?>"></i>
          <?= htmlspecialchars(ucfirst($status)); ?>
        </span>
      </div>

      <div class="booking-actions">
        <a href="../../pages/passenger/dashboard.php" class="btn-view-details">
          <i class="bi bi-eye"></i>
          View
        </a>
      </div>

    <?php else: ?>
      <div class="no-booking-message">
        <i class="bi bi-inbox"></i>
        <h4>No Active Booking</h4>
        <p>You don't have any active bookings at the moment.</p>
      </div>
    <?php endif; ?>
    </div>
  </div>

  <!-- Enhanced Services Grid -->
  <div class="services-grid">
    <div class="service-card <?= $current_booking ? 'disabled' : ''; ?>">
      <div class="service-icon">
        <i class="bi bi-calendar-check"></i>
      </div>
      <h3>Book a Ride</h3>
      <?php if ($current_booking): ?>
        <p>You have an active booking. Please wait for it to be completed before creating a new one.</p>
        <span class="service-btn disabled">
          <i class="bi bi-x-circle me-2"></i>
          Booking In Progress
        </span>
      <?php else: ?>
        <p>Find a tricycle near you and book your ride instantly. Safe, fast, and convenient transportation.</p>
        <a href="../../pages/passenger/dashboard.php" class="service-btn">
          <i class="bi bi-plus-circle me-2"></i>
          Book Now
        </a>
      <?php endif; ?>
    </div>

    <div class="service-card">
      <div class="service-icon">
        <i class="bi bi-clock-history"></i>
      </div>
      <h3>Trip History</h3>
      <p>View your complete booking history, track your rides, and manage your transportation records.</p>
      <a href="../../pages/passenger/trips-history.php" class="service-btn">
        <i class="bi bi-eye me-2"></i>
        View History
      </a>
    </div>
  </div>
</div>

<!-- Enhanced JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
  const statusClass = status === 'accepted' ? 'status-accepted' : 'status-pending';
  const statusIcon = status === 'accepted' ? 'bi-check-circle-fill' : 'bi-hourglass-split';
  
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
        <h4 style="color: #667eea; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
          <i class="bi bi-person-badge"></i>
          Driver Information
        </h4>
        <div class="booking-info-grid">
          <div class="booking-info-item">
            <i class="bi bi-person-circle" style="color: #667eea;"></i>
            <div>
              <div class="booking-info-label">Driver Name</div>
              <div class="booking-info-value">${booking.driver_name || 'N/A'}</div>
            </div>
          </div>
          <div class="booking-info-item">
            <i class="bi bi-telephone-fill" style="color: #10b981;"></i>
            <div>
              <div class="booking-info-label">Phone Number</div>
              <div class="booking-info-value">
                ${booking.driver_phone ? `<a href="tel:${booking.driver_phone}" style="color: #10b981; text-decoration: none; font-weight: 600;">${booking.driver_phone}</a>` : 'N/A'}
              </div>
            </div>
          </div>
          ${booking.vehicle_info ? `
          <div class="booking-info-item">
            <i class="bi bi-truck" style="color: #ef4444;"></i>
            <div>
              <div class="booking-info-label">Vehicle Info</div>
              <div class="booking-info-value">${booking.vehicle_info}</div>
            </div>
          </div>
          ` : ''}
        </div>
      </div>
    `;
  }
  
  bookingContent.innerHTML = `
    <div class="booking-info-grid">
      <div class="booking-info-item">
        <i class="bi bi-hash" style="color: #667eea;"></i>
        <div>
          <div class="booking-info-label">Booking ID</div>
          <div class="booking-info-value">#${booking.id}</div>
        </div>
      </div>
      <div class="booking-info-item">
        <i class="bi bi-geo-alt-fill" style="color: #ef4444;"></i>
        <div>
          <div class="booking-info-label">Pickup Location</div>
          <div class="booking-info-value">${booking.location}</div>
        </div>
      </div>
      <div class="booking-info-item">
        <i class="bi bi-flag-fill" style="color: #10b981;"></i>
        <div>
          <div class="booking-info-label">Destination</div>
          <div class="booking-info-value">${booking.destination}</div>
        </div>
      </div>
      <div class="booking-info-item">
        <i class="bi bi-calendar-event" style="color: #f59e0b;"></i>
        <div>
          <div class="booking-info-label">Booking Time</div>
          <div class="booking-info-value">${formattedDate}</div>
        </div>
      </div>
    </div>
    ${driverHtml}
    <div>
      <span class="booking-status-badge ${statusClass}">
        <i class="bi ${statusIcon}"></i>
        ${status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    </div>
    <div class="booking-actions">
      <a href="../../pages/passenger/dashboard.php" class="btn-view-details">
        <i class="bi bi-eye"></i>
        View Full Details
      </a>
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

// Start checking booking status every 15 seconds
<?php if ($current_booking): ?>
setInterval(checkBookingStatus, 15000);
// Set initial status
previousStatus = '<?= strtolower($current_booking['status']); ?>';
previousDriverId = <?= !empty($current_booking['driver_id']) ? $current_booking['driver_id'] : 'null'; ?>;
<?php endif; ?>

function toggleMenu() {
  const menu = document.getElementById('navMenu');
  menu.classList.toggle('show');
}

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', function() {
  // Animation observer for cards
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };
  
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);
  
  // Observe all animated elements
  document.querySelectorAll('.stat-card, .service-card').forEach(el => {
    observer.observe(el);
  });

  // Add loading states to buttons
  document.querySelectorAll('.service-btn').forEach(button => {
    button.addEventListener('click', function(e) {
      if (!this.classList.contains('disabled')) {
        this.style.opacity = '0.8';
        this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Loading...';
      }
    });
  });

  // Add ripple effect to buttons
  document.querySelectorAll('.service-btn, .btn-request').forEach(button => {
    button.addEventListener('click', function(e) {
      if (this.classList.contains('disabled')) return;
      
      const ripple = document.createElement('span');
      const rect = this.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;
      
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
      `;
      
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      
      setTimeout(() => {
        if (ripple.parentNode) {
          ripple.remove();
        }
      }, 600);
    });
  });
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
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

</body>
</html>

<?php 
// Close database connection at the end
$database->closeConnection(); 
?>