<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$db = new Database();
$conn = $db->getConnection();

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$bookingId) {
    header("Location: bookings.php");
    exit();
}

// Get booking details with user info
$query = "SELECT b.*, p.name as passenger_name, p.email as passenger_email, p.phone as passenger_phone,
                 d.name as driver_name, d.email as driver_email, d.phone as driver_phone,
                 d.license_number, d.tricycle_info
          FROM tricycle_bookings b 
          LEFT JOIN users p ON b.user_id = p.user_id 
          LEFT JOIN users d ON b.driver_id = d.user_id
          WHERE b.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: bookings.php");
    exit();
}

renderAdminHeader("Booking Details #" . $bookingId, "bookings");
?>

<!-- Booking Header -->
<div class="content-card">
    <div class="booking-header">
        <div class="booking-title">
            <h3><i class="bi bi-calendar-check"></i> Booking #<?= $booking['id'] ?></h3>
            <span class="status-badge status-<?= $booking['status'] ?>">
                <?= ucfirst($booking['status']) ?>
            </span>
        </div>
        <div class="booking-actions">
            <a href="bookings.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Bookings
            </a>
            <?php if ($booking['status'] === 'pending' && !$booking['driver_id']): ?>
                <a href="assign_driver.php?booking_id=<?= $booking['id'] ?>" class="btn btn-info">
                    <i class="bi bi-person-plus"></i> Assign Driver
                </a>
            <?php endif; ?>
            <?php if (in_array($booking['status'], ['pending', 'accepted'])): ?>
                <a href="cancel_booking.php?id=<?= $booking['id'] ?>" class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to cancel this booking?')">
                    <i class="bi bi-x-circle"></i> Cancel Booking
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details -->
<div class="row g-4">
    <!-- Trip Information -->
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-geo-alt"></i> Trip Information</h5>
            <div class="detail-group">
                <div class="detail-item">
                    <label>Pickup Location:</label>
                    <div class="detail-value">
                        <i class="bi bi-geo-alt text-success"></i>
                        <?= htmlspecialchars($booking['location']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <label>Destination:</label>
                    <div class="detail-value">
                        <i class="bi bi-flag text-primary"></i>
                        <?= htmlspecialchars($booking['destination']) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <label>Booking Date & Time:</label>
                    <div class="detail-value">
                        <i class="bi bi-calendar"></i>
                        <?= date('F d, Y \a\t g:i A', strtotime($booking['booking_time'])) ?>
                    </div>
                </div>
                <?php if ($booking['notes']): ?>
                <div class="detail-item">
                    <label>Special Notes:</label>
                    <div class="detail-value">
                        <i class="bi bi-chat-text"></i>
                        <?= htmlspecialchars($booking['notes']) ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($booking['fare']): ?>
                <div class="detail-item">
                    <label>Fare:</label>
                    <div class="detail-value">
                        <i class="bi bi-currency-dollar"></i>
                        ₱<?= number_format($booking['fare'], 2) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Timeline -->
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-clock-history"></i> Status Timeline</h5>
            <div class="timeline">
                <div class="timeline-item <?= in_array($booking['status'], ['pending', 'accepted', 'completed', 'cancelled']) ? 'completed' : '' ?>">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6>Booking Created</h6>
                        <small><?= date('M d, Y g:i A', strtotime($booking['booking_time'])) ?></small>
                    </div>
                </div>
                
                <div class="timeline-item <?= in_array($booking['status'], ['accepted', 'completed']) ? 'completed' : ($booking['status'] === 'pending' ? 'current' : '') ?>">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6><?= $booking['driver_id'] ? 'Driver Assigned' : 'Awaiting Driver' ?></h6>
                        <small><?= $booking['status'] === 'pending' ? 'In progress...' : 'Completed' ?></small>
                    </div>
                </div>
                
                <div class="timeline-item <?= $booking['status'] === 'completed' ? 'completed' : ($booking['status'] === 'accepted' ? 'current' : '') ?>">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6>Trip <?= $booking['status'] === 'completed' ? 'Completed' : ($booking['status'] === 'accepted' ? 'In Progress' : 'Pending') ?></h6>
                        <small><?= $booking['status'] === 'completed' ? 'Trip finished successfully' : ($booking['status'] === 'accepted' ? 'Driver en route' : 'Waiting for acceptance') ?></small>
                    </div>
                </div>
                
                <?php if ($booking['status'] === 'cancelled'): ?>
                <div class="timeline-item cancelled">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <h6>Booking Cancelled</h6>
                        <small>Trip was cancelled</small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Passenger & Driver Info -->
<div class="row g-4 mt-2">
    <!-- Passenger Information -->
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-person"></i> Passenger Information</h5>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="user-details">
                    <h6><?= htmlspecialchars($booking['passenger_name']) ?></h6>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="bi bi-envelope"></i>
                            <a href="mailto:<?= htmlspecialchars($booking['passenger_email']) ?>">
                                <?= htmlspecialchars($booking['passenger_email']) ?>
                            </a>
                        </div>
                        <div class="contact-item">
                            <i class="bi bi-phone"></i>
                            <a href="tel:<?= htmlspecialchars($booking['passenger_phone']) ?>">
                                <?= htmlspecialchars($booking['passenger_phone']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="user-actions">
                        <a href="view_user.php?id=<?= $booking['user_id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> View Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Information -->
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-car-front"></i> Driver Information</h5>
            <?php if ($booking['driver_id']): ?>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="user-details">
                        <h6><?= htmlspecialchars($booking['driver_name']) ?></h6>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="bi bi-envelope"></i>
                                <a href="mailto:<?= htmlspecialchars($booking['driver_email']) ?>">
                                    <?= htmlspecialchars($booking['driver_email']) ?>
                                </a>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-phone"></i>
                                <a href="tel:<?= htmlspecialchars($booking['driver_phone']) ?>">
                                    <?= htmlspecialchars($booking['driver_phone']) ?>
                                </a>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-card-text"></i>
                                License: <?= htmlspecialchars($booking['license_number']) ?>
                            </div>
                            <div class="contact-item">
                                <i class="bi bi-truck"></i>
                                <?= htmlspecialchars($booking['tricycle_info']) ?>
                            </div>
                        </div>
                        <div class="user-actions">
                            <a href="view_user.php?id=<?= $booking['driver_id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-person-dash"></i>
                    <h6>No Driver Assigned</h6>
                    <p>This booking is still waiting for a driver assignment.</p>
                    <?php if ($booking['status'] === 'pending'): ?>
                        <a href="assign_driver.php?booking_id=<?= $booking['id'] ?>" class="btn btn-custom">
                            <i class="bi bi-person-plus"></i> Assign Driver
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.booking-title {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.booking-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.detail-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item label {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.detail-value {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: rgba(40, 167, 69, 0.3);
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-marker {
    position: absolute;
    left: -1.5rem;
    top: 0.25rem;
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    background: rgba(40, 167, 69, 0.3);
    border: 2px solid var(--bg-color);
}

.timeline-item.completed .timeline-marker {
    background: var(--primary-color);
}

.timeline-item.current .timeline-marker {
    background: #ffc107;
}

.timeline-item.cancelled .timeline-marker {
    background: #dc3545;
}

.timeline-content h6 {
    margin: 0 0 0.25rem 0;
    font-size: 0.9rem;
}

.timeline-content small {
    color: var(--text-muted);
}

.user-info {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.user-avatar {
    font-size: 3rem;
    color: var(--primary-color);
    opacity: 0.7;
}

.user-details {
    flex: 1;
}

.user-details h6 {
    margin: 0 0 0.5rem 0;
    color: var(--primary-color);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.contact-item i {
    color: var(--primary-color);
    width: 1rem;
}

.contact-item a {
    color: inherit;
    text-decoration: none;
}

.contact-item a:hover {
    color: var(--primary-color);
}

.user-actions {
    margin-top: 1rem;
}
</style>

<?php renderAdminFooter(); ?>