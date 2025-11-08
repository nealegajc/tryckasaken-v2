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
  <!-- Custom CSS -->
  <link rel="stylesheet" href="../../public/css/book.css">
</head>
<body>

<div class="container" style="max-width: 900px; margin: 0 auto; padding: 40px 20px;">
  <a href="../passenger/loginUser.php" class="back-link" style="display: inline-flex; align-items: center; gap: 8px; color: #37517e; text-decoration: none; font-weight: 600; margin-bottom: 24px;">
    <i class="bi bi-arrow-left"></i> Back to Dashboard
  </a>

  <div class="page-header">
    <h1>🕐 Trip History</h1>
    <p>View all your booking history and status</p>
  </div>

  <section class="booking-list">
    <?php if (count($bookings) > 0): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <tr>
                  <th class="border-0">Booking #</th>
                  <th class="border-0">Pickup Location</th>
                  <th class="border-0">Destination</th>
                  <th class="border-0">Status</th>
                  <th class="border-0">Date & Time</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookings as $booking): ?>
                  <tr>
                    <td><strong class="text-primary">#<?= htmlspecialchars($booking['id']); ?></strong></td>
                    <td>
                      <i class="bi bi-geo-alt text-danger"></i> 
                      <?= htmlspecialchars($booking['location']); ?>
                    </td>
                    <td>
                      <i class="bi bi-geo-alt-fill text-success"></i> 
                      <?= htmlspecialchars($booking['destination']); ?>
                    </td>
                    <td>
                      <?php 
                      $status = strtolower($booking['status']);
                      $badge_class = '';
                      $icon = '';
                      
                      if ($status == 'pending') {
                          $badge_class = 'bg-warning text-dark';
                          $icon = '<i class="bi bi-clock-fill"></i>';
                      } elseif ($status == 'accepted') {
                          $badge_class = 'bg-info text-white';
                          $icon = '<i class="bi bi-check-circle-fill"></i>';
                      } elseif ($status == 'completed') {
                          $badge_class = 'bg-success text-white';
                          $icon = '<i class="bi bi-check-circle-fill"></i>';
                      } elseif ($status == 'declined') {
                          $badge_class = 'bg-danger text-white';
                          $icon = '<i class="bi bi-x-circle-fill"></i>';
                      }
                      ?>
                      <span class="badge <?= $badge_class; ?>" style="padding: 6px 12px; border-radius: 20px;">
                        <?= $icon; ?> <?= htmlspecialchars(ucfirst($booking['status'])); ?>
                      </span>
                    </td>
                    <td class="text-muted">
                      <i class="bi bi-clock"></i> 
                      <?= date('M d, Y h:i A', strtotime($booking['booking_time'])); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-4">
        <a href="../passenger/book.php" class="btn btn-primary btn-lg">
          <i class="bi bi-plus-circle"></i> Create New Booking
        </a>
      </div>
    <?php else: ?>
      <div class="card border-0 shadow-sm text-center p-5">
        <div class="empty-state-icon" style="font-size: 80px;">📭</div>
        <h4 class="mt-3">No Trip History Yet</h4>
        <p class="text-muted mb-4">Start your journey by creating your first booking!</p>
        <a href="../passenger/book.php" class="btn btn-primary btn-lg">
          <i class="bi bi-plus-circle"></i> Create New Booking
        </a>
      </div>
    <?php endif; ?>
  </section>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php $database->closeConnection(); ?>
