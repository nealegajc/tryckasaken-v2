<?php
session_start();
require_once '../../config/dbConnection.php';

// Check if user is logged in as passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'passenger') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get user's booking statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
    SUM(CASE WHEN LOWER(status) = 'accepted' THEN 1 ELSE 0 END) as active_bookings,
    SUM(CASE WHEN LOWER(status) = 'completed' THEN 1 ELSE 0 END) as completed_bookings
FROM tricycle_bookings 
WHERE user_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
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

    .stats-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(var(--blur-amount));
      border-radius: var(--radius-lg);
      padding: 32px 24px;
      text-align: center;
      border: 1px solid rgba(255, 255, 255, 0.3);
      transition: var(--transition-smooth);
      position: relative;
      overflow: hidden;
      animation: slideUp 0.6s ease-out;
    }

    .stat-card:nth-child(1) { animation-delay: 0.1s; }
    .stat-card:nth-child(2) { animation-delay: 0.2s; }
    .stat-card:nth-child(3) { animation-delay: 0.3s; }
    .stat-card:nth-child(4) { animation-delay: 0.4s; }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--primary-gradient);
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-medium);
    }

    .stat-number {
      font-size: 3rem;
      font-weight: 800;
      color: #16a34a;
      margin-bottom: 8px;
    }

    .stat-label {
      color: #6c757d;
      font-weight: 600;
      font-size: 1rem;
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

      .stats-section {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
      }

      .stat-card {
        padding: 24px 16px;
      }

      .stat-number {
        font-size: 2rem;
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
    <a class="navbar-brand" href="#">
      <i class="bi bi-truck me-2"></i>
      TrycKaSaken
    </a>
    <button class="navbar-toggler" onclick="toggleMenu()">
      <i class="bi bi-list"></i>
    </button>
    <ul class="navbar-nav" id="navMenu">
      <li class="nav-item">
        <a class="nav-link" href="../passenger/loginUser.php">
          <i class="bi bi-house"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a href="../passenger/book.php" class="btn-request">
          <i class="bi bi-plus-circle"></i> Book Ride
        </a>
      </li>
      <li class="nav-item">
        <a href="../passenger/trip_history.php" class="nav-link">
          <i class="bi bi-clock-history"></i> Trip History
        </a>
      </li>
      <li class="nav-item">
        <a href="../../pages/auth/logout.php" class="btn btn-danger btn-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </li>
    </ul>
  </div>
</nav>

<div class="page-content">
  <!-- Enhanced Welcome Section -->
  <div class="welcome-section">
    <h2>Welcome Back, <?= htmlspecialchars($user_name); ?>! 👋</h2>
    <p>Ready for your next ride? Book a tricycle and get to your destination safely and quickly.</p>
  </div>

  <!-- Enhanced Booking Statistics -->
  <div class="stats-section">
    <div class="stat-card">
      <div class="stat-number"><?= $stats['total_bookings']; ?></div>
      <div class="stat-label">Total Bookings</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-number"><?= $stats['pending_bookings']; ?></div>
      <div class="stat-label">Pending Rides</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-number"><?= $stats['active_bookings']; ?></div>
      <div class="stat-label">Active Rides</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-number"><?= $stats['completed_bookings']; ?></div>
      <div class="stat-label">Completed Rides</div>
    </div>
  </div>

  <!-- Enhanced Services Grid -->
  <div class="services-grid">
    <div class="service-card">
      <div class="service-icon">
        <i class="bi bi-calendar-check"></i>
      </div>
      <h3>Book a Ride</h3>
      <p>Find a tricycle near you and book your ride instantly. Safe, fast, and convenient transportation.</p>
      <a href="../passenger/book.php" class="service-btn">
        <i class="bi bi-plus-circle me-2"></i>
        Book Now
      </a>
    </div>

    <div class="service-card">
      <div class="service-icon">
        <i class="bi bi-clock-history"></i>
      </div>
      <h3>Trip History</h3>
      <p>View your complete booking history, track your rides, and manage your transportation records.</p>
      <a href="../passenger/trip_history.php" class="service-btn">
        <i class="bi bi-eye me-2"></i>
        View History
      </a>
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
      this.style.opacity = '0.8';
      this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Loading...';
    });
  });

  // Add ripple effect to buttons
  document.querySelectorAll('.service-btn, .btn-request').forEach(button => {
    button.addEventListener('click', function(e) {
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

  // Auto-refresh stats periodically
  setInterval(() => {
    // Add subtle pulse animation to stats
    document.querySelectorAll('.stat-number').forEach(stat => {
      stat.style.transform = 'scale(1.05)';
      setTimeout(() => {
        stat.style.transform = 'scale(1)';
      }, 200);
    });
  }, 30000); // Every 30 seconds
});

// Add CSS for ripple animation
const style = document.createElement('style');
style.textContent = `
  @keyframes ripple {
    to {
      transform: scale(4);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
</script>

</body>
</html>
