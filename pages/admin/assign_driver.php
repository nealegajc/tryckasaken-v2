<?php
session_start();
require_once '../../config/dbConnection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, u.name as passenger_name, u.email as passenger_email, u.phone as passenger_phone
    FROM tricycle_bookings b
    JOIN users u ON b.user_id = u.user_id
    WHERE b.id = ?
");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: bookings.php?error=Booking not found");
    exit();
}

// Get all verified drivers
$drivers_result = $conn->query("
    SELECT u.user_id, u.name, u.email, u.phone, u.status,
           COUNT(b.id) as total_bookings,
           SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
    FROM users u
    LEFT JOIN drivers d ON u.user_id = d.user_id
    LEFT JOIN tricycle_bookings b ON u.user_id = b.driver_id
    WHERE u.user_type = 'driver' 
    AND d.status = 'verified'
    AND u.status = 'active'
    GROUP BY u.user_id
    ORDER BY u.name ASC
");
$drivers = $drivers_result->fetch_all(MYSQLI_ASSOC);

// Handle driver assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['driver_id'])) {
    $driver_id = intval($_POST['driver_id']);
    
    // Verify driver exists and is verified
    $verify_stmt = $conn->prepare("
        SELECT u.user_id FROM users u
        JOIN drivers d ON u.user_id = d.user_id
        WHERE u.user_id = ? AND u.user_type = 'driver' AND d.status = 'verified' AND u.status = 'active'
    ");
    $verify_stmt->bind_param("i", $driver_id);
    $verify_stmt->execute();
    $driver_exists = $verify_stmt->get_result()->num_rows > 0;
    $verify_stmt->close();
    
    if ($driver_exists) {
        // Update booking with driver assignment
        $update_stmt = $conn->prepare("UPDATE tricycle_bookings SET driver_id = ?, status = 'accepted' WHERE id = ?");
        $update_stmt->bind_param("ii", $driver_id, $booking_id);
        
        if ($update_stmt->execute()) {
            $update_stmt->close();
            $database->closeConnection();
            header("Location: bookings.php?success=Driver assigned successfully");
            exit();
        } else {
            $error = "Failed to assign driver";
        }
        $update_stmt->close();
    } else {
        $error = "Invalid driver selected";
    }
}

$database->closeConnection();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Assign Driver | TrycKaSaken Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/glass.css">
  <style>
    body {
      background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
      min-height: 100vh;
      position: relative;
    }
    
    .glass-navbar {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
      padding: 1rem 0;
      margin-bottom: 2rem;
    }
    
    .admin-container {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 20px 40px;
    }
    
    .glass-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 20px;
      padding: 30px;
      margin-bottom: 24px;
    }
    
    .glass-card h4 {
      color: white;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: white;
    }
    
    .info-row:last-child {
      border-bottom: none;
    }
    
    .info-label {
      opacity: 0.8;
      font-weight: 500;
    }
    
    .info-value {
      font-weight: 600;
    }
    
    .driver-card {
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      color: white;
    }
    
    .driver-card:hover {
      background: rgba(255, 255, 255, 0.15);
      border-color: rgba(255, 255, 255, 0.4);
      transform: translateY(-3px);
    }
    
    .driver-card.selected {
      background: rgba(255, 255, 255, 0.25);
      border-color: #16a34a;
      box-shadow: 0 0 20px rgba(76, 175, 80, 0.3);
    }
    
    .driver-card input[type="radio"] {
      display: none;
    }
    
    .driver-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }
    
    .driver-name {
      font-size: 1.2rem;
      font-weight: 600;
    }
    
    .driver-stats {
      display: flex;
      gap: 20px;
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    .driver-stat {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .status-badge {
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    
    .status-pending { background: rgba(34, 197, 94, 0.3); color: #22c55e; }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: white;
    }
    
    .empty-state i {
      font-size: 4rem;
      opacity: 0.5;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<nav class="glass-navbar">
  <div class="container">
    <a class="navbar-brand" href="bookings.php" style="color: white; text-decoration: none; font-weight: 600;">
      <i class="bi bi-person-plus"></i> Assign Driver
    </a>
    <div class="d-flex gap-2">
      <a href="bookings.php" class="glass-btn glass-btn-sm">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>
  </div>
</nav>

<div class="admin-container">
  <?php if (isset($error)): ?>
    <div class="glass-alert glass-alert-danger mb-4">
      <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
    </div>
  <?php endif; ?>

  <!-- Booking Details -->
  <div class="glass-card">
    <h4><i class="bi bi-calendar-check"></i> Booking Details</h4>
    <div class="info-row">
      <span class="info-label">Booking ID:</span>
      <span class="info-value">#<?= $booking['id'] ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Passenger:</span>
      <span class="info-value"><?= htmlspecialchars($booking['passenger_name']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Contact:</span>
      <span class="info-value"><?= htmlspecialchars($booking['passenger_phone']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Pickup Location:</span>
      <span class="info-value"><i class="bi bi-geo-alt-fill" style="color: #4caf50;"></i> <?= htmlspecialchars($booking['location']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Destination:</span>
      <span class="info-value"><i class="bi bi-geo-alt-fill" style="color: #ef5350;"></i> <?= htmlspecialchars($booking['destination']) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Booking Time:</span>
      <span class="info-value"><?= date('M d, Y h:i A', strtotime($booking['booking_time'])) ?></span>
    </div>
    <div class="info-row">
      <span class="info-label">Status:</span>
      <span class="status-badge status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
    </div>
  </div>

  <!-- Available Drivers -->
  <div class="glass-card">
    <h4><i class="bi bi-people"></i> Select Driver (<?= count($drivers) ?> Available)</h4>
    
    <?php if (count($drivers) > 0): ?>
      <form method="POST" action="">
        <div id="drivers-list">
          <?php foreach ($drivers as $driver): ?>
            <label class="driver-card" data-driver-id="<?= $driver['user_id'] ?>">
              <input type="radio" name="driver_id" value="<?= $driver['user_id'] ?>" required>
              <div class="driver-header">
                <div>
                  <div class="driver-name">
                    <i class="bi bi-person-circle"></i> <?= htmlspecialchars($driver['name']) ?>
                  </div>
                  <small style="opacity: 0.8;">
                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($driver['email']) ?> | 
                    <i class="bi bi-telephone"></i> <?= htmlspecialchars($driver['phone']) ?>
                  </small>
                </div>
                <i class="bi bi-check-circle" style="font-size: 1.5rem; opacity: 0; transition: opacity 0.3s;"></i>
              </div>
              <div class="driver-stats">
                <div class="driver-stat">
                  <i class="bi bi-calendar-check"></i>
                  <span><?= $driver['total_bookings'] ?> Total Trips</span>
                </div>
                <div class="driver-stat">
                  <i class="bi bi-check-circle"></i>
                  <span><?= $driver['completed_bookings'] ?> Completed</span>
                </div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        
        <button type="submit" class="glass-btn glass-btn-success w-100 mt-4" style="padding: 14px; font-size: 1.1rem;">
          <i class="bi bi-person-check"></i> Assign Selected Driver
        </button>
      </form>
    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-person-x"></i>
        <h5>No Available Drivers</h5>
        <p style="opacity: 0.8;">There are currently no verified and active drivers available for assignment.</p>
        <a href="driver_verification.php" class="glass-btn glass-btn-primary mt-3">
          <i class="bi bi-shield-check"></i> Go to Driver Verification
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
  // Handle driver card selection
  document.querySelectorAll('.driver-card').forEach(card => {
    card.addEventListener('click', function() {
      // Remove selected class from all cards
      document.querySelectorAll('.driver-card').forEach(c => {
        c.classList.remove('selected');
        c.querySelector('.bi-check-circle').style.opacity = '0';
      });
      
      // Add selected class to clicked card
      this.classList.add('selected');
      this.querySelector('.bi-check-circle').style.opacity = '1';
      
      // Check the radio button
      this.querySelector('input[type="radio"]').checked = true;
    });
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
