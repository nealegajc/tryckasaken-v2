<?php
session_start();
require_once '../../config/Database.php';

// Check if user is logged in as driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'driver') {
    header("Location: ../../pages/auth/login-form.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Check driver verification status and online status
$verification_query = "SELECT verification_status, is_online FROM drivers WHERE user_id = ?";
$stmt = $conn->prepare($verification_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$verification_result = $stmt->get_result()->fetch_assoc();
$verification_status = $verification_result ? $verification_result['verification_status'] : 'pending';
$is_online = $verification_result ? $verification_result['is_online'] : 1;
$stmt->close();

$show_verified_notification = false;
if ($verification_status === 'verified') {
    if (!isset($_SESSION['verification_notified'])) {
        $show_verified_notification = true;
        $_SESSION['verification_notified'] = true;
    }
}

// Get driver profile information
$driver_profile_query = "SELECT u.name, u.email, u.phone, d.picture_path, d.verification_status 
                         FROM users u 
                         LEFT JOIN drivers d ON u.user_id = d.user_id 
                         WHERE u.user_id = ?";
$stmt = $conn->prepare($driver_profile_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver_profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Check if driver has an active trip
$active_trip_query = "SELECT COUNT(*) as has_active FROM tricycle_bookings WHERE driver_id = ? AND LOWER(status) = 'accepted'";
$stmt = $conn->prepare($active_trip_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_result = $stmt->get_result()->fetch_assoc();
$has_active_trip = $active_result['has_active'] > 0;
$stmt->close();

$conn->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Driver Dashboard - TrycKaSaken</title>
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
      --primary-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
      --success-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
      --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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

    /* Profile Card Styles */
    .profile-card {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.15);
      padding: 8px 16px;
      border-radius: 50px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      text-decoration: none;
      transition: var(--transition-smooth);
      position: relative;
    }

    .profile-card:hover {
      background: rgba(255, 255, 255, 0.25);
      transform: translateY(-2px);
    }

    .profile-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255, 255, 255, 0.3);
      background: linear-gradient(135deg, #10b981, #047857);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      position: relative;
    }

    .profile-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
    }

    .profile-name {
      color: white;
      font-weight: 600;
      font-size: 14px;
      margin: 0;
      line-height: 1;
    }

    .profile-status {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.8);
      margin: 0;
      line-height: 1;
    }

    .verification-badge {
      position: absolute;
      top: -2px;
      right: -2px;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 2px solid white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 8px;
    }

    .verification-badge.verified {
      background: #10b981;
      color: white;
    }

    .verification-badge.pending {
      background: #f59e0b;
      color: white;
    }

    .verification-badge.rejected {
      background: #ef4444;
      color: white;
    }

    /* Offline Status Styles */
    .offline-toggle {
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-size: 14px;
      font-weight: 500;
      transition: var(--transition-smooth);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .offline-toggle:hover {
      background: rgba(255, 255, 255, 0.25);
    }

    .offline-toggle.offline {
      background: rgba(239, 68, 68, 0.2);
      border-color: rgba(239, 68, 68, 0.3);
    }

    .offline-toggle.online {
      background: rgba(16, 185, 129, 0.2);
      border-color: rgba(16, 185, 129, 0.3);
    }

    .offline-toggle.on-trip {
      background: rgba(251, 191, 36, 0.2);
      border-color: rgba(251, 191, 36, 0.3);
      opacity: 0.9;
    }

    .offline-toggle.pending-verification {
      background: rgba(251, 191, 36, 0.2);
      border-color: rgba(251, 191, 36, 0.3);
      opacity: 0.9;
    }

    .offline-toggle.account-rejected {
      background: rgba(239, 68, 68, 0.2);
      border-color: rgba(239, 68, 68, 0.3);
      opacity: 0.9;
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

    .verification-status {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-lg);
      border: 1px solid rgba(255, 255, 255, 0.3);
      padding: 24px;
      margin-bottom: 30px;
      animation: slideUp 0.6s ease-out 0.1s both;
    }

    .verification-status.pending {
      border-left: 4px solid #22c55e;
    }

    .verification-status.verified {
      border-left: 4px solid #10b981;
    }

    .verification-status.rejected {
      border-left: 4px solid #e74c3c;
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

    .service-card:nth-child(1) { animation-delay: 0.5s; }
    .service-card:nth-child(2) { animation-delay: 0.6s; }

    .service-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-medium);
    }

    .service-icon {
      width: 80px;
      height: 80px;
      background: var(--success-gradient);
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
      color: #2c3e50;
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
      background: var(--success-gradient);
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
      box-shadow: 0 8px 25px rgba(17, 153, 142, 0.4);
    }

    .service-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
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

    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }

    @media (max-width: 768px) {
      .navbar {
        padding: 12px 0;
      }
      
      .navbar .container {
        flex-wrap: wrap;
        gap: 10px;
      }
      
      .profile-card {
        flex: 1;
        min-width: 200px;
      }
      
      .offline-toggle {
        font-size: 12px;
        padding: 6px 12px;
      }
      
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
        color: #2c3e50;
        padding: 12px 16px;
        width: 100%;
        justify-content: center;
      }

      .btn-request {
        background: var(--success-gradient);
        justify-content: center;
        width: 100%;
      }

      .btn-request.disabled {
        background: #6c757d;
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
    }
  </style>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <!-- Profile Card -->
    <div class="profile-card" href="#">
      <div class="profile-avatar">
        <?php if ($driver_profile && $driver_profile['picture_path'] && file_exists('../../' . $driver_profile['picture_path'])): ?>
          <img src="../../<?= htmlspecialchars($driver_profile['picture_path']) ?>" alt="Profile" class="profile-avatar">
        <?php else: ?>
          <?= strtoupper(substr($user_name, 0, 1)) ?>
        <?php endif; ?>
        <div class="verification-badge <?= $verification_status ?>">
          <?php if ($verification_status === 'verified'): ?>
            <i class="bi bi-check"></i>
          <?php elseif ($verification_status === 'pending'): ?>
            <i class="bi bi-clock"></i>
          <?php else: ?>
            <i class="bi bi-x"></i>
          <?php endif; ?>
        </div>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
        <div class="profile-status">
          <?= ucfirst($verification_status) ?> Driver
        </div>
      </div>
    </div>
    
    <!-- Online/Offline Toggle -->
    <?php if ($verification_status === 'pending'): ?>
      <button class="offline-toggle pending-verification" id="statusToggle" disabled style="cursor: not-allowed;" title="Status toggle disabled - Account pending verification">
        <i class="bi bi-clock-fill" id="statusIcon"></i>
        <span id="statusText">Pending Verification</span>
      </button>
    <?php elseif ($verification_status === 'rejected'): ?>
      <button class="offline-toggle account-rejected" id="statusToggle" disabled style="cursor: not-allowed;" title="Status toggle disabled - Account rejected">
        <i class="bi bi-x-circle-fill" id="statusIcon"></i>
        <span id="statusText">Account Rejected</span>
      </button>
    <?php elseif ($has_active_trip): ?>
      <button class="offline-toggle on-trip" id="statusToggle" disabled style="cursor: not-allowed;" title="Cannot change status during active trip">
        <i class="bi bi-circle-fill" id="statusIcon"></i>
        <span id="statusText">On Trip</span>
      </button>
    <?php else: ?>
      <button class="offline-toggle <?= $is_online ? 'online' : 'offline' ?>" id="statusToggle" onclick="toggleDriverStatus()">
        <i class="bi bi-circle-fill" id="statusIcon"></i>
        <span id="statusText"><?= $is_online ? 'Online' : 'Offline' ?></span>
      </button>
    <?php endif; ?>
    
    <button class="navbar-toggler" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>
    <ul class="navbar-nav" id="navMenu">
      <li class="nav-item">
        <a class="nav-link" href="../../pages/driver/login-form.php">
          <i class="bi bi-house"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <?php if ($verification_status === 'verified' && $is_online): ?>
          <a href="../../pages/driver/dashboard.php" class="btn-request">
            <i class="bi bi-<?= $has_active_trip ? 'check-circle' : 'card-list' ?>"></i> 
            <?= $has_active_trip ? 'Complete Trip' : 'View Requests' ?>
          </a>
        <?php else: ?>
          <a href="#" class="btn-request" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('<?= !$is_online ? 'You must be online to view requests. Please toggle your status to Online first.' : 'View Requests is locked. Please wait for verification approval.' ?>'); return false;">
            <i class="bi bi-lock"></i> View Requests
          </a>
        <?php endif; ?>
      </li>
      <li class="nav-item">
        <?php if ($verification_status !== 'verified'): ?>
          <a href="#" class="nav-link" style="opacity: 0.5; cursor: not-allowed;" onclick="alert('Trip History is locked. Please wait for verification approval.'); return false;">
            <i class="bi bi-lock"></i> Trip History
          </a>
        <?php else: ?>
          <a href="trips-history.php" class="nav-link">
            <i class="bi bi-clock-history"></i> Trip History
          </a>
        <?php endif; ?>
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
  <!-- Success/Error Messages -->
  <?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle"></i> <?= $_SESSION['success_message']; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle"></i> <?= $_SESSION['error_message']; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <!-- Enhanced Verification Status -->
  <?php if ($show_verified_notification && $verification_status === 'verified'): ?>
    <div class="verification-status verified" id="verificationNotif">
      <div class="d-flex align-items-center">
        <i class="bi bi-check-circle" style="font-size: 2rem; color: #10b981; margin-right: 16px;"></i>
        <div>
          <h5 class="mb-1" style="color: #10b981;">Account Verified! 🎉</h5>
          <p class="mb-0">Your driver account has been verified. You can now accept ride requests and start earning!</p>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Enhanced Welcome Section -->
  <div class="welcome-section">
    <h2>Welcome <?= htmlspecialchars($user_name); ?>!</h2>
    <p>
      <?php if ($verification_status === 'verified'): ?>
        Start accepting ride requests. Your passengers are waiting!
      <?php elseif ($verification_status === 'pending'): ?>
        Your account is currently under review. Once verified, you can start accepting rides.
      <?php else: ?>
        Your application was rejected. Please contact support for further assistance.
      <?php endif; ?>
    </p>
    <?php if ($has_active_trip): ?>
      <div class="mt-3 p-3" style="background: rgba(52, 152, 219, 0.1); border-radius: 12px; border-left: 4px solid #3498db;">
        <i class="bi bi-info-circle-fill me-2" style="color: #3498db;"></i>
        <strong>Active Trip:</strong> You have an active trip. Please complete it before accepting new requests.
      </div>
    <?php endif; ?>
    
    <?php if ($verification_status === 'verified' && !$is_online && !$has_active_trip): ?>
      <div class="mt-3 p-3" style="background: rgba(156, 163, 175, 0.1); border-radius: 12px; border-left: 4px solid #6b7280;">
        <i class="bi bi-wifi-off me-2" style="color: #6b7280;"></i>
        <strong>You're Offline:</strong> Toggle your status to "Online" to start viewing and accepting ride requests.
      </div>
    <?php endif; ?>
    
    <?php if ($verification_status === 'pending'): ?>
      <div class="mt-3 p-3" style="background: rgba(251, 191, 36, 0.1); border-radius: 12px; border-left: 4px solid #f59e0b;">
        <i class="bi bi-clock-fill me-2" style="color: #f59e0b;"></i>
        <strong>Status:</strong> Online status toggle is disabled until your account is verified.
      </div>
    <?php elseif ($verification_status === 'rejected'): ?>
      <div class="mt-3 p-3" style="background: rgba(239, 68, 68, 0.1); border-radius: 12px; border-left: 4px solid #ef4444;">
        <i class="bi bi-exclamation-triangle-fill me-2" style="color: #ef4444;"></i>
        <strong>Account Rejected:</strong> Your verification was rejected. All features are restricted. Please contact support.
      </div>
    <?php endif; ?>
  </div>

  <!-- Enhanced Services Grid -->
  <div class="services-grid">
    <div class="service-card <?= ($verification_status !== 'verified' || (!$is_online && !$has_active_trip)) ? 'opacity-50' : '' ?>">
      <div class="service-icon <?= ($has_active_trip ? 'bg-info' : ((!$is_online && $verification_status === 'verified') ? 'bg-secondary' : '')) ?>">
        <i class="bi bi-<?= $has_active_trip ? 'check-circle' : 'card-list' ?>"></i>
      </div>
      <h3><?= $has_active_trip ? 'Complete Trip' : 'View Requests' ?></h3>
      <p>
        <?php if ($has_active_trip): ?>
          Complete your current active trip
        <?php elseif ($verification_status !== 'verified'): ?>
          Verification required to view requests
        <?php elseif (!$is_online): ?>
          You must be online to view and accept requests
        <?php else: ?>
          Check available booking requests from passengers
        <?php endif; ?>
      </p>
      <?php if ($verification_status === 'verified' && ($is_online || $has_active_trip)): ?>
        <a href="../../pages/driver/dashboard.php" class="service-btn">
          <?= $has_active_trip ? 'Complete Trip' : 'View Requests' ?>
        </a>
      <?php elseif ($verification_status === 'verified' && !$is_online): ?>
        <button class="service-btn" disabled style="opacity: 0.5; cursor: not-allowed;">
          <i class="bi bi-wifi-off"></i> Go Online to View Requests
        </button>
      <?php else: ?>
        <button class="service-btn" disabled style="opacity: 0.5; cursor: not-allowed;">
          <i class="bi bi-lock"></i> Locked - Verification Required
        </button>
      <?php endif; ?>
    </div>

    <div class="service-card <?= $verification_status !== 'verified' ? 'opacity-50' : '' ?>">
      <div class="service-icon <?= $verification_status !== 'verified' ? 'bg-secondary' : '' ?>">
        <i class="bi bi-clock-history"></i>
      </div>
      <h3>Trip History</h3>
      <p>
        <?php if ($verification_status === 'rejected'): ?>
          Access restricted - verification rejected
        <?php elseif ($verification_status === 'pending'): ?>
          Verification required to access trip history
        <?php else: ?>
          View your completed trips and performance summary
        <?php endif; ?>
      </p>
      <?php if ($verification_status === 'verified'): ?>
        <a href="trips-history.php" class="service-btn">View History</a>
      <?php else: ?>
        <button class="service-btn" disabled style="opacity: 0.5; cursor: not-allowed;">
          <i class="bi bi-lock"></i> Locked - Verification Required
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Enhanced JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleMenu() {
  const menu = document.getElementById('navMenu');
  menu.classList.toggle('show');
}

// Initialize animations and interactions
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
  document.querySelectorAll('.service-card, .verification-status').forEach(el => {
    observer.observe(el);
  });

  // Add loading states to buttons
  document.querySelectorAll('.service-btn:not(.disabled)').forEach(button => {
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

  // Auto-dismiss verification status after some time (only for success)
  <?php if ($show_verified_notification && $verification_status === 'verified'): ?>
  setTimeout(() => {
    const notif = document.getElementById('verificationNotif');
    if (notif) {
      notif.style.transition = 'all 0.5s ease';
      notif.style.opacity = '0';
      notif.style.transform = 'translateY(-20px)';
      setTimeout(() => {
        if (notif.parentNode) {
          notif.remove();
        }
      }, 500);
    }
  }, 8000); // Remove after 8 seconds
  <?php endif; ?>
});

// Driver status toggle functionality
function toggleDriverStatus() {
  const toggle = document.getElementById('statusToggle');
  const icon = document.getElementById('statusIcon');
  const text = document.getElementById('statusText');
  
  const isOnline = toggle.classList.contains('online');
  const confirmMessage = isOnline 
    ? 'Going offline will prevent you from receiving new trip requests. Continue?' 
    : 'Go online to start receiving trip requests?';
  
  if (!confirm(confirmMessage)) {
    return;
  }
  
  // Disable button during request
  toggle.disabled = true;
  toggle.style.opacity = '0.6';
  text.textContent = 'Updating...';
  
  // Send AJAX request
  fetch('status-update-handler.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Update UI
      if (data.is_online) {
        toggle.classList.remove('offline');
        toggle.classList.add('online');
        text.textContent = 'Online';
      } else {
        toggle.classList.remove('online');
        toggle.classList.add('offline');
        text.textContent = 'Offline';
      }
      showStatusMessage(data.message, 'success');
    } else {
      showStatusMessage(data.message, 'error');
    }
  })
  .catch(error => {
    showStatusMessage('Failed to update status. Please try again.', 'error');
    console.error('Error:', error);
  })
  .finally(() => {
    // Re-enable button
    toggle.disabled = false;
    toggle.style.opacity = '1';
  });
}

function showStatusMessage(message, type) {
  // Create toast notification
  const toast = document.createElement('div');
  toast.className = `alert alert-${type === 'success' ? 'success' : 'warning'} alert-dismissible fade show position-fixed`;
  toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
  toast.innerHTML = `
    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
    ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  `;
  
  document.body.appendChild(toast);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
    }
  }, 5000);
}
</script>

</body>
</html>
