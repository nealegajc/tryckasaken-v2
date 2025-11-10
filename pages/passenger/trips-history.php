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

// Fetch ALL the user's bookings
$stmt = $conn->prepare("SELECT * FROM tricycle_bookings WHERE user_id = ? ORDER BY booking_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
      --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
      padding: 120px 0 40px 0;
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

    /* Fixed Navbar Styles */
    .navbar-fixed {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(102, 126, 234, 0.2);
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
      color: #667eea;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .navbar-brand:hover {
      color: #764ba2;
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
      background: var(--primary-gradient);
      color: white;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .nav-link-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
      color: white;
    }

    .nav-link-secondary {
      background: rgba(102, 126, 234, 0.1);
      color: #667eea;
      border: 1px solid rgba(102, 126, 234, 0.3);
    }

    .nav-link-secondary:hover {
      background: rgba(102, 126, 234, 0.2);
      color: #764ba2;
    }

    .menu-toggle {
      display: none;
      background: var(--primary-gradient);
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 1.2rem;
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
      background: var(--primary-gradient);
      color: white;
      padding: 20px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
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
      background: rgba(102, 126, 234, 0.05);
    }

    .trip-date {
      color: #6c757d;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .status-badge {
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-accepted {
      background: #dbeafe;
      color: #1e40af;
    }

    .status-completed {
      background: #d1fae5;
      color: #065f46;
    }

    .status-declined, .status-cancelled {
      background: #fee2e2;
      color: #991b1b;
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
      margin-bottom: 24px;
    }

    .btn-custom {
      background: var(--primary-gradient);
      color: white;
      border: none;
      padding: 12px 32px;
      border-radius: 25px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: var(--transition-smooth);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-custom:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
      color: white;
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
      }

      .trip-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
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
      <a href="../../pages/passenger/dashboard.php" class="nav-link-btn nav-link-secondary">
        <i class="bi bi-plus-circle"></i> New Booking
      </a>
      <a href="../../pages/auth/logout-handler.php" class="nav-link-btn nav-link-primary">
        <i class="bi bi-box-arrow-right"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="container">

  <!-- Page Header -->
  <div class="page-header">
    <h2><i class="bi bi-clock-history"></i> Trip History</h2>
    <p>View all your booking history and track your trips</p>
  </div>

  <?php
  // Calculate statistics
  $total_trips = count($bookings);
  $completed_trips = 0;
  $pending_trips = 0;
  $cancelled_trips = 0;

  foreach ($bookings as $booking) {
    $status = strtolower($booking['status']);
    if ($status === 'completed') $completed_trips++;
    elseif ($status === 'pending') $pending_trips++;
    elseif ($status === 'declined' || $status === 'cancelled') $cancelled_trips++;
  }
  ?>

  <!-- Statistics Summary -->
  <div class="stats-summary">
    <div class="stat-box">
      <div class="stat-icon" style="color: #667eea;">
        <i class="bi bi-geo-alt"></i>
      </div>
      <div class="stat-value"><?php echo $total_trips; ?></div>
      <div class="stat-label">Total Trips</div>
    </div>

    <div class="stat-box">
      <div class="stat-icon" style="color: #10b981;">
        <i class="bi bi-check-circle"></i>
      </div>
      <div class="stat-value"><?php echo $completed_trips; ?></div>
      <div class="stat-label">Completed</div>
    </div>

    <div class="stat-box">
      <div class="stat-icon" style="color: #f59e0b;">
        <i class="bi bi-hourglass-split"></i>
      </div>
      <div class="stat-value"><?php echo $pending_trips; ?></div>
      <div class="stat-label">Pending</div>
    </div>

    <div class="stat-box">
      <div class="stat-icon" style="color: #ef4444;">
        <i class="bi bi-x-circle"></i>
      </div>
      <div class="stat-value"><?php echo $cancelled_trips; ?></div>
      <div class="stat-label">Cancelled</div>
    </div>
  </div>

  <!-- Trip Cards -->
  <?php if (count($bookings) > 0): ?>
    <?php foreach ($bookings as $booking): 
      $status = strtolower($booking['status']);
      $status_class = 'status-pending';
      $status_icon = 'bi-hourglass-split';
      
      if ($status === 'accepted') {
        $status_class = 'status-accepted';
        $status_icon = 'bi-arrow-right-circle';
      } elseif ($status === 'completed') {
        $status_class = 'status-completed';
        $status_icon = 'bi-check-circle-fill';
      } elseif ($status === 'declined' || $status === 'cancelled') {
        $status_class = 'status-declined';
        $status_icon = 'bi-x-circle-fill';
      }
    ?>
    <div class="trip-card">
      <div class="trip-header">
        <h5>
          <i class="bi bi-geo-alt-fill"></i>
          Trip #<?php echo htmlspecialchars($booking['id']); ?>
        </h5>
        <span class="status-badge <?php echo $status_class; ?>">
          <i class="bi <?php echo $status_icon; ?>"></i>
          <?php echo htmlspecialchars(ucfirst($status)); ?>
        </span>
      </div>
      
      <div class="trip-body">
        <div class="trip-detail-row">
          <div class="trip-detail">
            <i class="bi bi-pin-map-fill" style="color: #10b981;"></i>
            <div>
              <div class="detail-label">Pickup Location</div>
              <div class="detail-value"><?php echo htmlspecialchars($booking['location']); ?></div>
            </div>
          </div>
          
          <div class="trip-detail">
            <i class="bi bi-geo-fill" style="color: #ef4444;"></i>
            <div>
              <div class="detail-label">Dropoff Location</div>
              <div class="detail-value"><?php echo htmlspecialchars($booking['destination']); ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="trip-footer">
        <div class="trip-date">
          <i class="bi bi-calendar3"></i>
          <?php echo date('F j, Y \a\t g:i A', strtotime($booking['booking_time'])); ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

  <?php else: ?>
    <!-- Empty State -->
    <div class="empty-state">
      <div class="empty-state-icon" style="color: #e0e0e0;">
        <i class="bi bi-inbox"></i>
      </div>
      <h4>No Bookings Yet</h4>
      <p>Your booking history will appear here once you make your first trip.</p>
      <a href="../../pages/passenger/dashboard.php" class="btn-custom">
        <i class="bi bi-plus-circle"></i> Book a Ride
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
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
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $database->closeConnection(); ?>
