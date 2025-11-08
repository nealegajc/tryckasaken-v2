<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause
$whereConditions = [];
$params = [];
$types = '';

if ($statusFilter !== 'all') {
    $whereConditions[] = "b.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

if ($searchQuery) {
    $whereConditions[] = "(b.id = ? OR p.name LIKE ? OR p.email LIKE ? OR b.location LIKE ? OR b.destination LIKE ?)";
    $searchId = is_numeric($searchQuery) ? intval($searchQuery) : 0;
    $searchLike = "%$searchQuery%";
    $params = array_merge($params, [$searchId, $searchLike, $searchLike, $searchLike, $searchLike]);
    $types .= 'issss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get bookings with passenger info
$query = "SELECT b.*, p.name as passenger_name, p.email as passenger_email, p.phone as passenger_phone,
                 d.name as driver_name
          FROM tricycle_bookings b 
          LEFT JOIN users p ON b.user_id = p.user_id 
          LEFT JOIN users d ON b.driver_id = d.user_id
          $whereClause
          ORDER BY b.booking_time DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$bookings = $result->fetch_all(MYSQLI_ASSOC);

renderAdminHeader("Booking Management", "bookings");
?>

<!-- Filters -->
<div class="content-card">
  <h5><i class="bi bi-funnel"></i> Filters</h5>
  <form method="GET" class="row g-3">
    <div class="col-md-3">
      <select name="status" class="form-select">
        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
    <div class="col-md-6">
      <input type="text" name="search" class="form-control" placeholder="Search by ID, name, email, or location..." value="<?= htmlspecialchars($searchQuery) ?>">
    </div>
    <div class="col-md-3">
      <button type="submit" class="btn btn-custom w-100">
        <i class="bi bi-search"></i> Filter
      </button>
    </div>
  </form>
</div>

<!-- Bookings Table -->
<div class="content-card">
  <h3>
    <i class="bi bi-calendar-check"></i>
    All Bookings (<?= count($bookings) ?>)
  </h3>
  
  <?php if (count($bookings) > 0): ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th><i class="bi bi-hash"></i> ID</th>
            <th><i class="bi bi-person"></i> Passenger</th>
            <th><i class="bi bi-geo-alt"></i> Route</th>
            <th><i class="bi bi-car-front"></i> Driver</th>
            <th><i class="bi bi-circle"></i> Status</th>
            <th><i class="bi bi-calendar"></i> Date</th>
            <th><i class="bi bi-gear"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bookings as $booking): ?>
            <tr>
              <td><strong>#<?= $booking['id'] ?></strong></td>
              <td>
                <div>
                  <strong><?= htmlspecialchars($booking['passenger_name']) ?></strong><br>
                  <small class="text-muted"><?= htmlspecialchars($booking['passenger_email']) ?></small>
                </div>
              </td>
              <td>
                <div>
                  <i class="bi bi-geo-alt text-success"></i> <?= htmlspecialchars($booking['location']) ?><br>
                  <i class="bi bi-flag text-primary"></i> <?= htmlspecialchars($booking['destination']) ?>
                </div>
              </td>
              <td>
                <?php if ($booking['driver_name']): ?>
                  <span class="status-badge status-active">
                    <i class="bi bi-person-check"></i> <?= htmlspecialchars($booking['driver_name']) ?>
                  </span>
                <?php else: ?>
                  <span class="status-badge status-inactive">
                    <i class="bi bi-person-dash"></i> Unassigned
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <span class="status-badge status-<?= $booking['status'] ?>">
                  <?= ucfirst($booking['status']) ?>
                </span>
              </td>
              <td><?= date('M d, Y H:i', strtotime($booking['booking_time'])) ?></td>
              <td>
                <a href="view_booking.php?id=<?= $booking['id'] ?>" class="action-btn">
                  <i class="bi bi-eye"></i> View
                </a>
                <?php if ($booking['status'] === 'pending' && !$booking['driver_id']): ?>
                  <a href="assign_driver.php?booking_id=<?= $booking['id'] ?>" class="action-btn btn-info">
                    <i class="bi bi-person-plus"></i> Assign
                  </a>
                <?php endif; ?>
                <?php if (in_array($booking['status'], ['pending', 'accepted'])): ?>
                  <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="action-btn btn-danger" 
                     onclick="return confirm('Are you sure you want to cancel this booking?')">
                    <i class="bi bi-x-circle"></i> Cancel
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-calendar-x"></i>
      <h5>No Bookings Found</h5>
      <p>Bookings will appear here once passengers make reservations.</p>
    </div>
  <?php endif; ?>
</div>

<?php renderAdminFooter(); ?>