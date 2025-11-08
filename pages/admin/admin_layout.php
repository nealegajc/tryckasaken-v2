<?php
// Admin Layout Header - Include this at the top of admin pages
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

function renderAdminHeader($pageTitle = "Admin Dashboard", $currentPage = "dashboard") {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | TrycKaSaken</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Custom Styles -->
  <link rel="stylesheet" href="../../public/css/style.css">
  
  <style>
    :root {
      --color-primary: #16a34a;
      --color-primary-dark: #15803d;
      --color-gray-400: #9ca3af;
      --color-gray-500: #6b7280;
      --color-gray-600: #4b5563;
      --gradient-green: linear-gradient(135deg, #16a34a, #15803d);
      --glass-bg: rgba(255, 255, 255, 0.1);
      --glass-border: rgba(22, 163, 74, 0.2);
      --blur-lg: blur(16px);
      --radius-xl: 1rem;
      --radius-lg: 0.75rem;
      --radius-md: 0.5rem;
      --radius-full: 9999px;
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      --transition-base: all 0.3s ease;
    }

    body {
      background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #ffffff 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
    }

    .admin-header {
      background: var(--gradient-green);
      color: white;
      padding: 20px 0;
      box-shadow: var(--shadow-lg);
      margin-bottom: 30px;
    }

    .admin-nav {
      background: var(--glass-bg);
      backdrop-filter: var(--blur-lg);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-xl);
      padding: 20px;
      margin-bottom: 30px;
      box-shadow: var(--shadow-md);
    }

    .nav-section {
      margin-bottom: 25px;
    }

    .nav-section h5 {
      color: var(--color-primary);
      font-weight: 700;
      margin-bottom: 15px;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .nav-links {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .nav-link-btn {
      background: rgba(22, 163, 74, 0.1);
      border: 1px solid rgba(22, 163, 74, 0.2);
      color: var(--color-primary);
      padding: 8px 16px;
      border-radius: var(--radius-lg);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.85rem;
      transition: all var(--transition-base);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .nav-link-btn:hover {
      background: var(--color-primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .nav-link-btn.active {
      background: var(--color-primary);
      color: white;
    }

    .content-card {
      background: var(--glass-bg);
      backdrop-filter: var(--blur-lg);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-xl);
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: var(--shadow-md);
    }

    .content-card h3 {
      color: var(--color-primary);
      font-weight: 700;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .table-responsive {
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    .table {
      margin: 0;
    }

    .table th {
      background: var(--color-primary);
      color: white;
      font-weight: 600;
      border: none;
      padding: 15px;
    }

    .table td {
      padding: 15px;
      vertical-align: middle;
      border-color: rgba(22, 163, 74, 0.1);
    }

    .table tbody tr:hover {
      background: rgba(22, 163, 74, 0.05);
    }

    .status-badge {
      padding: 6px 12px;
      border-radius: var(--radius-full);
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .status-active { background: rgba(22, 163, 74, 0.1); color: var(--color-primary); }
    .status-inactive { background: rgba(107, 114, 128, 0.1); color: var(--color-gray-600); }
    .status-suspended { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .status-pending { background: rgba(255, 193, 7, 0.1); color: #f59e0b; }
    .status-verified { background: rgba(22, 163, 74, 0.1); color: var(--color-primary); }
    .status-rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .status-completed { background: rgba(22, 163, 74, 0.1); color: var(--color-primary); }
    .status-cancelled { background: rgba(107, 114, 128, 0.1); color: var(--color-gray-600); }
    .status-accepted { background: rgba(59, 130, 246, 0.1); color: #2563eb; }

    .action-btn {
      background: var(--gradient-green);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: var(--radius-md);
      font-weight: 600;
      font-size: 0.8rem;
      transition: all var(--transition-base);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin: 2px;
    }

    .action-btn:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow-green);
    }

    .action-btn.btn-warning {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .action-btn.btn-danger {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    .action-btn.btn-info {
      background: linear-gradient(135deg, #0891b2, #0e7490);
    }

    .alert-custom {
      border: none;
      border-radius: var(--radius-lg);
      padding: 15px 20px;
      margin-bottom: 20px;
      backdrop-filter: var(--blur-lg);
    }

    .alert-success {
      background: rgba(22, 163, 74, 0.1);
      border-left: 4px solid var(--color-primary);
      color: var(--color-primary);
    }

    .alert-danger {
      background: rgba(239, 68, 68, 0.1);
      border-left: 4px solid #dc2626;
      color: #dc2626;
    }

    .alert-warning {
      background: rgba(245, 158, 11, 0.1);
      border-left: 4px solid #f59e0b;
      color: #d97706;
    }

    .empty-state {
      text-align: center;
      padding: 40px;
      color: var(--color-gray-500);
    }

    .empty-state i {
      font-size: 3rem;
      margin-bottom: 15px;
      color: var(--color-gray-400);
    }

    .stat-card {
      background: var(--glass-bg);
      backdrop-filter: var(--blur-lg);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-xl);
      padding: 25px;
      transition: all var(--transition-base);
      box-shadow: var(--shadow-md);
    }

    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
      border-color: var(--color-primary);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .stat-info h3 {
      font-size: 2rem;
      font-weight: 700;
      margin: 0;
      color: var(--color-primary);
    }

    .stat-info p {
      margin: 5px 0 0 0;
      color: var(--color-gray-600);
      font-weight: 500;
    }

    .stat-link {
      color: var(--color-primary);
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      margin-top: 10px;
    }

    .stat-link:hover {
      color: var(--color-primary-dark);
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      font-size: 0.8rem;
      border-radius: var(--radius-md);
      text-decoration: none;
      font-weight: 500;
      transition: all var(--transition-base);
      margin-right: 5px;
      margin-bottom: 5px;
    }

    .action-btn {
      background: var(--color-primary);
      color: white;
    }

    .action-btn:hover {
      background: var(--color-primary-dark);
      color: white;
      transform: translateY(-1px);
    }

    .action-btn.btn-info {
      background: #0ea5e9;
    }

    .action-btn.btn-danger {
      background: #dc2626;
    }

    .action-btn.btn-warning {
      background: #f59e0b;
      color: black;
    }

    .filter-controls {
      background: rgba(22, 163, 74, 0.05);
      border: 1px solid rgba(22, 163, 74, 0.1);
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-bottom: 20px;
    }

    .btn-custom {
      background: var(--gradient-green);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: var(--radius-md);
      font-weight: 600;
      transition: all var(--transition-base);
    }

    .btn-custom:hover {
      color: white;
      transform: translateY(-2px);
      box-shadow: var(--shadow-green);
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 0.2rem rgba(22, 163, 74, 0.25);
    }

    @media (max-width: 768px) {
      .nav-links {
        flex-direction: column;
      }
      
      .admin-nav {
        padding: 15px;
      }
      
      .content-card {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

<!-- Admin Header -->
<div class="admin-header">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h2 class="mb-1">
          <i class="bi bi-speedometer2"></i> <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <p class="mb-0 opacity-75">Welcome back, System Administrator</p>
      </div>
      <div>
        <a href="../../pages/auth/logout.php" class="btn btn-light btn-sm">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <!-- Navigation Menu -->
  <div class="admin-nav">
    <div class="row">
      <div class="col-md-4">
        <div class="nav-section">
          <h5><i class="bi bi-house"></i> Main</h5>
          <div class="nav-links">
            <a href="admin.php" class="nav-link-btn <?= $currentPage === 'admin' || $currentPage === 'dashboard' ? 'active' : '' ?>">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="bookings.php" class="nav-link-btn <?= $currentPage === 'bookings' ? 'active' : '' ?>">
              <i class="bi bi-calendar-check"></i> Bookings
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="nav-section">
          <h5><i class="bi bi-people"></i> Management</h5>
          <div class="nav-links">
            <a href="users.php" class="nav-link-btn <?= $currentPage === 'users' ? 'active' : '' ?>">
              <i class="bi bi-person"></i> Users
            </a>
            <a href="driver_management.php" class="nav-link-btn <?= $currentPage === 'drivers' || $currentPage === 'driver_management' ? 'active' : '' ?>">
              <i class="bi bi-car-front"></i> Drivers
            </a>
            <a href="driver_verification.php" class="nav-link-btn <?= $currentPage === 'verification' || $currentPage === 'driver_verification' ? 'active' : '' ?>">
              <i class="bi bi-shield-check"></i> Verification
            </a>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="nav-section">
          <h5><i class="bi bi-graph-up"></i> Analytics</h5>
          <div class="nav-links">
            <a href="analytics.php" class="nav-link-btn <?= $currentPage === 'analytics' ? 'active' : '' ?>">
              <i class="bi bi-bar-chart"></i> Analytics
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php
}

function renderAdminFooter() {
?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}

function showAlert($type, $message) {
  $icon = '';
  switch($type) {
    case 'success': $icon = 'bi-check-circle-fill'; break;
    case 'danger': $icon = 'bi-exclamation-triangle-fill'; break;
    case 'warning': $icon = 'bi-exclamation-triangle'; break;
    default: $icon = 'bi-info-circle-fill'; break;
  }
  
  echo '<div class="alert-custom alert-' . $type . '">';
  echo '<i class="bi ' . $icon . ' me-2"></i>';
  echo '<strong>' . ucfirst($type) . ':</strong> ' . htmlspecialchars($message);
  echo '</div>';
}
?>