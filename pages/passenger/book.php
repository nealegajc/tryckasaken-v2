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
    header("Location: book.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $destination = trim($_POST['destination']);

    if (empty($name) || empty($location) || empty($destination)) {
        $_SESSION['error_message'] = 'Please fill in all fields!';
        header("Location: book.php");
        exit;
    }

    // Insert booking with user_id
    $stmt = $conn->prepare("INSERT INTO tricycle_bookings (user_id, name, location, destination, status) VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $user_id, $name, $location, $destination);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Booking successful!';
        header("Location: book.php");
    } else {
        $_SESSION['error_message'] = 'Booking failed. Please try again.';
        header("Location: book.php");
    }

    $stmt->close();
    exit;
}

// Fetch ONLY the current user's bookings
$stmt = $conn->prepare("SELECT * FROM tricycle_bookings WHERE user_id = ? ORDER BY booking_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get latest booking for status check
$latestBooking = !empty($bookings) ? $bookings[0] : null;

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
  <!-- Glass CSS -->
  <link rel="stylesheet" href="../../public/css/glass.css">
  
  <style>
    body {
      min-height: 100vh;
      padding: 40px 20px;
      background: linear-gradient(135deg, #10b981 0%, #047857 100%);
    }
    
    .book-container {
      max-width: 900px;
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
  </style>
</head>
<body>
  <div class="book-container">
    <a href="../passenger/loginUser.php" class="back-link">
      <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="page-header">
      <h1><i class="bi bi-truck"></i> Book a Tricycle</h1>
      <p>Your reliable tricycle booking service</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div style="background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.5); color: #10b981; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
        <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div style="background: rgba(220,38,38,0.2); border: 1px solid rgba(220,38,38,0.5); color: #dc2626; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
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
      <div style="background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.5); color: #f59e0b; padding: 24px; border-radius: 12px; margin-bottom: 32px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 24px; margin-right: 15px;"></i>
        <strong>Active Booking in Progress</strong>
        <p style="margin-top: 8px; margin-bottom: 0;">You already have an active booking. Please wait for it to be completed before creating a new one.</p>
      </div>
    <?php endif; ?>

    <?php if ($latestBooking): ?>
    <section class="status-card">
      <h4 style="color: #16a34a; font-weight: 700; margin-bottom: 20px;">
        <i class="bi bi-card-text"></i> Current Booking Status
      </h4>
      <div class="row align-items-center">
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
          <p class="mb-0" style="color: #6c757d;">
            <i class="bi bi-clock-fill"></i> <?= date('M d, Y h:i A', strtotime($latestBooking['booking_time'])); ?>
          </p>
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
    </section>
    <?php endif; ?>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $database->closeConnection(); ?>
