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

// Check driver verification status
$verification_query = "SELECT verification_status FROM drivers WHERE user_id = ?";
$stmt = $conn->prepare($verification_query);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$verification_result = $stmt->get_result()->fetch_assoc();
$verification_status = $verification_result ? $verification_result['verification_status'] : 'pending';
$stmt->close();

// Block access for non-verified drivers
if ($verification_status !== 'verified') {
    $message = $verification_status === 'rejected' 
        ? 'Access denied. Your driver verification was rejected.' 
        : 'Access denied. Please wait for your verification to be approved.';
    $_SESSION['error_message'] = $message;
    header("Location: login-form.php");
    exit();
}

// Get driver's completed trips
$sql = "SELECT b.*, u.name as passenger_name, u.phone as passenger_phone 
        FROM tricycle_bookings b 
        JOIN users u ON b.user_id = u.user_id
        WHERE b.driver_id = ? AND LOWER(TRIM(b.status)) = 'completed'
        ORDER BY b.booking_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

// Get trip statistics
$stats_query = "SELECT 
    COUNT(*) as total_completed,
    COUNT(DISTINCT DATE(booking_time)) as days_active,
    MIN(booking_time) as first_trip,
    MAX(booking_time) as last_trip
FROM tricycle_bookings 
WHERE driver_id = ? AND LOWER(TRIM(status)) = 'completed'";

$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $driver_id);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();
$stmt_stats->close();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Trip History | TrycKaSaken</title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
      --success-gradient: linear-gradient(135deg, #10b981 0%, #047857 100%);
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
      padding: 40px 0;
      padding-top: 120px; /* Add padding to prevent content hiding under fixed navbar */
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

    .container {
      position: relative;
      z-index: 1;
      max-width: 1200px;
    }

    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: white;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 24px;
      padding: 10px 20px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: 25px;
      border: 1px solid var(--glass-border);
      transition: var(--transition-smooth);
    }

    .back-link:hover {
      background: rgba(255, 255, 255, 0.25);
      color: white;
      transform: translateX(-5px);
    }

    .page-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-xl);
      border: 1px solid var(--glass-border);
      padding: 40px;
      margin-bottom: 30px;
      text-align: center;
      animation: slideUp 0.6s ease-out;
    }

    .page-header h2 {
      color: #2c3e50;
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 12px;
    }

    .page-header p {
      color: #6c757d;
      font-size: 1.1rem;
      margin: 0;
    }

    .stats-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-box {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-lg);
      padding: 24px;
      text-align: center;
      border: 1px solid var(--glass-border);
      animation: slideUp 0.6s ease-out;
      transition: var(--transition-smooth);
    }

    .stat-box:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-medium);
    }

    .stat-box .stat-icon {
      font-size: 2.5rem;
      margin-bottom: 12px;
    }

    .stat-box .stat-value {
      font-size: 2rem;
      font-weight: 800;
      color: #2c3e50;
      margin-bottom: 8px;
    }

    .stat-box .stat-label {
      color: #6c757d;
      font-weight: 600;
      font-size: 0.95rem;
    }

    .trip-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-lg);
      border: 1px solid var(--glass-border);
      margin-bottom: 20px;
      overflow: hidden;
      transition: var(--transition-smooth);
      animation: slideUp 0.6s ease-out;
    }

    .trip-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-medium);
    }

    .trip-header {
      background: var(--success-gradient);
      color: white;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .trip-header h5 {
      margin: 0;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .trip-body {
      padding: 24px;
    }

    .trip-detail-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }

    .trip-detail {
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }

    .trip-detail i {
      font-size: 1.3rem;
      margin-top: 2px;
    }

    .trip-detail .detail-label {
      color: #6c757d;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .trip-detail .detail-value {
      color: #2c3e50;
      font-weight: 500;
    }

    .trip-footer {
      border-top: 1px solid rgba(0, 0, 0, 0.1);
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(16, 185, 129, 0.05);
    }

    .trip-date {
      color: #6c757d;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .status-badge {
      background: var(--success-gradient);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .empty-state {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-xl);
      border: 1px solid var(--glass-border);
      padding: 60px 40px;
      text-align: center;
      animation: slideUp 0.6s ease-out;
    }

    .empty-state-icon {
      font-size: 80px;
      margin-bottom: 24px;
    }

    .empty-state h4 {
      color: #2c3e50;
      font-weight: 700;
      margin-bottom: 12px;
    }

    .empty-state p {
      color: #6c757d;
      margin: 0;
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
      .page-header {
        padding: 30px 20px;
      }

      .page-header h2 {
        font-size: 2rem;
      }

      .stats-summary {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
      }

      .stat-box {
        padding: 16px;
      }

      .stat-box .stat-value {
        font-size: 1.5rem;
      }

      .trip-detail-row {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .trip-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }

      .trip-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
      }
    }

    @media (max-width: 768px) {
      body {
        padding-top: 90px;
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
      <a href="../../pages/driver/dashboard.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-card-list"></i> Requests
      </a>
      <a href="../../pages/auth/logout-handler.php" class="nav-link-btn nav-link-primary">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-header">
    <h2>🕐 Trip History</h2>
    <p>View your completed trips and track your performance</p>
  </div>

  <!-- Trip Statistics Summary -->
  <?php if ($stats['total_completed'] > 0): ?>
    <div class="stats-summary">
      <div class="stat-box">
        <div class="stat-icon">🚗</div>
        <div class="stat-value"><?= $stats['total_completed'] ?></div>
        <div class="stat-label">Total Trips</div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?= $stats['days_active'] ?></div>
        <div class="stat-label">Days Active</div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">🎯</div>
        <div class="stat-value"><?= $stats['first_trip'] ? date('M d, Y', strtotime($stats['first_trip'])) : 'N/A' ?></div>
        <div class="stat-label">First Trip</div>
      </div>
      
      <div class="stat-box">
        <div class="stat-icon">✅</div>
        <div class="stat-value"><?= $stats['last_trip'] ? date('M d, Y', strtotime($stats['last_trip'])) : 'N/A' ?></div>
        <div class="stat-label">Latest Trip</div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Trip History Cards -->
  <?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
      <div class="trip-card">
        <div class="trip-header">
          <h5>
            <i class="bi bi-receipt"></i>
            Trip #<?= htmlspecialchars($row['id']) ?>
          </h5>
          <span class="status-badge">
            <i class="bi bi-check-circle-fill"></i> Completed
          </span>
        </div>
        
        <div class="trip-body">
          <div class="trip-detail-row">
            <div class="trip-detail">
              <i class="bi bi-person-fill text-primary"></i>
              <div>
                <div class="detail-label">Passenger</div>
                <div class="detail-value">
                  <?= htmlspecialchars($row['passenger_name']) ?>
                  <br>
                  <small class="text-muted">
                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($row['passenger_phone']) ?>
                  </small>
                </div>
              </div>
            </div>
            
            <div class="trip-detail">
              <i class="bi bi-geo-alt text-danger"></i>
              <div>
                <div class="detail-label">Pickup Location</div>
                <div class="detail-value"><?= htmlspecialchars($row['location']) ?></div>
              </div>
            </div>
            
            <div class="trip-detail">
              <i class="bi bi-geo-alt-fill text-success"></i>
              <div>
                <div class="detail-label">Destination</div>
                <div class="detail-value"><?= htmlspecialchars($row['destination']) ?></div>
              </div>
            </div>
          </div>
          
          <?php if ($row['notes']): ?>
            <div class="trip-detail">
              <i class="bi bi-chat-text text-info"></i>
              <div>
                <div class="detail-label">Passenger Notes</div>
                <div class="detail-value"><?= htmlspecialchars($row['notes']) ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="trip-footer">
          <div class="trip-date">
            <i class="bi bi-clock"></i>
            <?= date('F j, Y • g:i A', strtotime($row['booking_time'])) ?>
          </div>
          <small class="text-muted">Status: <strong>Completed</strong></small>
        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty-state">
      <div class="empty-state-icon">📋</div>
      <h4>No Trip History Yet</h4>
      <p>Start accepting and completing rides to build your trip history and track your earnings!</p>
    </div>
  <?php endif; ?>

</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
