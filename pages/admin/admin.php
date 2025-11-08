<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Get quick statistics with error handling
$stats = [];

try {
    // User counts
    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'passenger'");
    $stats['passengers'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'driver'");
    $stats['drivers'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Booking counts
    $result = $conn->query("SELECT COUNT(*) as total FROM tricycle_bookings");
    $stats['total_bookings'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM tricycle_bookings WHERE status = 'pending'");
    $stats['pending_bookings'] = $result ? $result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT COUNT(*) as total FROM tricycle_bookings WHERE status = 'completed'");
    $stats['completed_bookings'] = $result ? $result->fetch_assoc()['total'] : 0;

    // Verification pending - check if verification_status column exists in users table
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_status'");
    if ($result && $result->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'driver' AND (verification_status = 'pending' OR verification_status IS NULL)");
        $stats['pending_verifications'] = $result ? $result->fetch_assoc()['total'] : 0;
    } else {
        // Check drivers table instead
        $result = $conn->query("SELECT COUNT(*) as total FROM drivers WHERE verification_status = 'pending'");
        $stats['pending_verifications'] = $result ? $result->fetch_assoc()['total'] : 0;
    }

    // Recent activity
    $recentBookings = [];
    $result = $conn->query("SELECT b.*, p.name as passenger_name 
                           FROM tricycle_bookings b 
                           LEFT JOIN users p ON b.user_id = p.user_id 
                           ORDER BY b.booking_time DESC LIMIT 5");
    if ($result) {
        $recentBookings = $result->fetch_all(MYSQLI_ASSOC);
    }

    $recentDrivers = [];
    $result = $conn->query("SELECT name, email, created_at 
                          FROM users 
                          WHERE user_type = 'driver' 
                          ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        $recentDrivers = $result->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    // Set default values if there are database errors
    $stats = [
        'passengers' => 0,
        'drivers' => 0,
        'total_bookings' => 0,
        'pending_bookings' => 0,
        'completed_bookings' => 0,
        'pending_verifications' => 0
    ];
    $recentBookings = [];
    $recentDrivers = [];
}

renderAdminHeader("Dashboard", "admin");
?>

<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2><i class="bi bi-speedometer2"></i> Welcome to Admin Dashboard</h2>
            <p class="mb-0">Manage your TrycKaSaken platform efficiently</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="welcome-date">
                <i class="bi bi-calendar-check"></i>
                <?= date('F d, Y') ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="bi bi-people"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['passengers']) ?></h3>
                <p>Total Passengers</p>
                <a href="users.php?filter=passenger" class="stat-link">
                    <i class="bi bi-arrow-right"></i> View All
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="bi bi-car-front"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['drivers']) ?></h3>
                <p>Total Drivers</p>
                <a href="driver_management.php" class="stat-link">
                    <i class="bi bi-arrow-right"></i> Manage
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-info">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['total_bookings']) ?></h3>
                <p>Total Bookings</p>
                <a href="bookings.php" class="stat-link">
                    <i class="bi bi-arrow-right"></i> View All
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($stats['pending_bookings']) ?></h3>
                <p>Pending Bookings</p>
                <a href="bookings.php?status=pending" class="stat-link">
                    <i class="bi bi-arrow-right"></i> Review
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Action Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="action-card">
            <div class="action-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <h5>Driver Verifications</h5>
            <p><?= $stats['pending_verifications'] ?> pending verification<?= $stats['pending_verifications'] != 1 ? 's' : '' ?></p>
            <a href="driver_verification.php" class="btn btn-custom">
                <i class="bi bi-check-circle"></i> Review Applications
            </a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="action-card">
            <div class="action-icon">
                <i class="bi bi-graph-up"></i>
            </div>
            <h5>Analytics & Reports</h5>
            <p>View platform performance and statistics</p>
            <a href="analytics.php" class="btn btn-custom">
                <i class="bi bi-bar-chart"></i> View Analytics
            </a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="action-card">
            <div class="action-icon">
                <i class="bi bi-gear"></i>
            </div>
            <h5>System Management</h5>
            <p>Manage users, settings, and system operations</p>
            <a href="users.php" class="btn btn-custom">
                <i class="bi bi-people"></i> Manage Users
            </a>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-clock-history"></i> Recent Bookings</h5>
            <?php if (count($recentBookings) > 0): ?>
                <div class="recent-list">
                    <?php foreach ($recentBookings as $booking): ?>
                        <div class="recent-item">
                            <div class="recent-info">
                                <strong>#<?= $booking['id'] ?></strong> - <?= htmlspecialchars($booking['passenger_name']) ?><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($booking['location']) ?> → <?= htmlspecialchars($booking['destination']) ?>
                                </small>
                            </div>
                            <div class="recent-meta">
                                <span class="status-badge status-<?= $booking['status'] ?>">
                                    <?= ucfirst($booking['status']) ?>
                                </span>
                                <small class="text-muted d-block">
                                    <?= date('M d, H:i', strtotime($booking['booking_time'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="bookings.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye"></i> View All Bookings
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state small">
                    <i class="bi bi-calendar-x"></i>
                    <p>No recent bookings</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="content-card">
            <h5><i class="bi bi-person-plus"></i> New Driver Applications</h5>
            <?php if (count($recentDrivers) > 0): ?>
                <div class="recent-list">
                    <?php foreach ($recentDrivers as $driver): ?>
                        <div class="recent-item">
                            <div class="recent-info">
                                <strong><?= htmlspecialchars($driver['name']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($driver['email']) ?></small>
                            </div>
                            <div class="recent-meta">
                                <span class="status-badge status-pending">New</span>
                                <small class="text-muted d-block">
                                    <?= date('M d, H:i', strtotime($driver['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="driver_verification.php" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-shield-check"></i> Review Applications
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state small">
                    <i class="bi bi-person-x"></i>
                    <p>No new applications</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.welcome-banner {
    background: linear-gradient(135deg, var(--primary-color), #20a045);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
}

.welcome-date {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    display: inline-block;
}

.action-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(40, 167, 69, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
}

.action-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.1);
    transform: translateY(-2px);
}

.action-icon {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, var(--primary-color), #20a045);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: white;
}

.recent-list {
    max-height: 300px;
    overflow-y: auto;
}

.recent-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(40, 167, 69, 0.1);
}

.recent-item:last-child {
    border-bottom: none;
}

.recent-info {
    flex: 1;
}

.recent-meta {
    text-align: right;
    min-width: 120px;
}

.empty-state.small {
    padding: 2rem 1rem;
    text-align: center;
}

.empty-state.small i {
    font-size: 2rem;
    opacity: 0.5;
}
</style>

<?php renderAdminFooter(); ?>