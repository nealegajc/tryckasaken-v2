<?php
session_start();
require_once '../../config/Database.php';
require_once 'layout-header.php';

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

    // Get pending driver verifications - only count drivers with pending status
    $result = $conn->query("
        SELECT COUNT(*) as total 
        FROM drivers d
        INNER JOIN users u ON d.user_id = u.user_id 
        WHERE u.user_type = 'driver' 
        AND d.verification_status = 'pending'
    ");
    $stats['pending_verifications'] = $result ? $result->fetch_assoc()['total'] : 0;

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
    $result = $conn->query("SELECT u.name, u.email, u.created_at 
                          FROM users u
                          INNER JOIN drivers d ON u.user_id = d.user_id
                          WHERE u.user_type = 'driver' 
                          AND d.verification_status = 'pending'
                          ORDER BY u.created_at DESC LIMIT 5");
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
                <a href="passengers-list.php?filter=passenger" class="stat-link">
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
                <a href="drivers-list.php" class="stat-link">
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
                <a href="bookings-list.php" class="stat-link">
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
                <a href="bookings-list.php?status=pending" class="stat-link">
                    <i class="bi bi-arrow-right"></i> Review
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Action Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="action-card <?= $stats['pending_verifications'] > 0 ? 'pending-highlight' : '' ?>">
            <div class="action-icon">
                <i class="bi bi-shield-check"></i>
                <?php if ($stats['pending_verifications'] > 0): ?>
                    <span class="notification-badge"><?= $stats['pending_verifications'] ?></span>
                <?php endif; ?>
            </div>
            <h5>Driver Verifications</h5>
            <?php if ($stats['pending_verifications'] > 0): ?>
                <p class="pending-count">
                    <strong><?= $stats['pending_verifications'] ?></strong> pending verification<?= $stats['pending_verifications'] != 1 ? 's' : '' ?>
                    <br><small class="text-muted">Require your attention</small>
                </p>
            <?php else: ?>
                <p>All drivers verified<br><small class="text-muted">No pending applications</small></p>
            <?php endif; ?>
            <a href="drivers-verification.php" class="btn btn-custom">
                <i class="bi bi-<?= $stats['pending_verifications'] > 0 ? 'exclamation-circle' : 'check-circle' ?>"></i> 
                <?= $stats['pending_verifications'] > 0 ? 'Review Applications' : 'View Verifications' ?>
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
            <a href="analytics-dashboard.php" class="btn btn-custom">
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
            <a href="passengers-list.php" class="btn btn-custom">
                <i class="bi bi-people"></i> Manage Passengers
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
                    <a href="bookings-list.php" class="btn btn-outline-primary btn-sm">
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
                    <a href="drivers-verification.php" class="btn btn-outline-primary btn-sm">
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
.action-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(40, 167, 69, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
    position: relative;
}

.action-card.pending-highlight {
    border-color: #f59e0b;
    background: rgba(245, 158, 11, 0.05);
    animation: pulse-glow 2s infinite;
}

@keyframes pulse-glow {
    0%, 100% { 
        box-shadow: 0 4px 20px rgba(245, 158, 11, 0.2);
    }
    50% { 
        box-shadow: 0 4px 30px rgba(245, 158, 11, 0.4);
    }
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
    position: relative;
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc2626;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 0.75rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-4px);
    }
    60% {
        transform: translateY(-2px);
    }
}

.pending-count {
    color: #f59e0b;
    margin-bottom: 1rem;
}

.pending-count strong {
    font-size: 1.2rem;
}
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

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    /* Adjust stat cards for mobile */
    .row.g-4 {
        gap: 1rem !important;
    }

    .stat-card {
        padding: 1rem;
    }

    .stat-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.2rem;
    }

    .stat-info h3 {
        font-size: 1.5rem;
    }

    .stat-info p {
        font-size: 0.85rem;
    }

    /* Stack action cards on mobile */
    .action-card {
        margin-bottom: 1rem;
    }

    .action-icon {
        width: 3rem;
        height: 3rem;
        font-size: 1.2rem;
    }

    /* Make recent activity lists mobile-friendly */
    .recent-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .recent-meta {
        text-align: left;
        min-width: auto;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .recent-info {
        width: 100%;
    }

    .recent-info strong {
        font-size: 0.95rem;
    }

    .recent-info small {
        font-size: 0.8rem;
    }

    /* Adjust status badges for mobile */
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }

    /* Content cards spacing */
    .content-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .content-card h5 {
        font-size: 1rem;
        margin-bottom: 1rem;
    }

    /* Buttons full width on mobile */
    .content-card .btn {
        width: 100%;
        margin-top: 0.5rem;
    }

    /* Notification badge smaller on mobile */
    .notification-badge {
        width: 20px;
        height: 20px;
        font-size: 0.65rem;
    }
}

/* Tablet responsive (768px to 991px) */
@media (min-width: 768px) and (max-width: 991px) {
    .stat-card {
        padding: 1.25rem;
    }

    .stat-info h3 {
        font-size: 1.75rem;
    }
}
</style>

<!-- Toast Notification Container -->
<div id="toastContainer" style="position: fixed; top: 80px; right: 20px; z-index: 9999;"></div>

<script>
// Toast notification function
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const iconMap = {
        success: 'check-circle-fill text-success',
        error: 'x-circle-fill text-danger',
        info: 'info-circle-fill text-info',
        warning: 'exclamation-triangle-fill text-warning'
    };
    
    const bgMap = {
        success: 'rgba(16, 185, 129, 0.95)',
        error: 'rgba(220, 38, 38, 0.95)',
        info: 'rgba(13, 202, 240, 0.95)',
        warning: 'rgba(245, 158, 11, 0.95)'
    };
    
    const toastHTML = `
        <div id="${toastId}" style="
            background: ${bgMap[type]};
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        ">
            <i class="bi bi-${iconMap[type]}" style="font-size: 1.2rem;"></i>
            <span style="flex: 1; font-weight: 600;">${message}</span>
            <button onclick="this.parentElement.remove()" style="
                background: none;
                border: none;
                color: white;
                font-size: 1.2rem;
                cursor: pointer;
                padding: 0;
                opacity: 0.8;
            ">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    
    setTimeout(() => {
        toastElement.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => toastElement.remove(), 300);
    }, 5000);
}

// AJAX: Refresh dashboard statistics
function refreshStats() {
    fetch('api-admin-actions.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.data);
            }
        })
        .catch(error => console.error('Stats refresh error:', error));
}

function updateStatsDisplay(stats) {
    // Update stat cards with animation
    const statElements = {
        passengers: document.querySelector('.stat-card:nth-child(1) h3'),
        drivers: document.querySelector('.stat-card:nth-child(2) h3'),
        total_bookings: document.querySelector('.stat-card:nth-child(3) h3'),
        pending_verifications: document.querySelector('.stat-card:nth-child(4) h3')
    };
    
    Object.keys(statElements).forEach(key => {
        if (statElements[key] && stats[key] !== undefined) {
            const currentValue = parseInt(statElements[key].textContent.replace(/,/g, ''));
            const newValue = parseInt(stats[key]);
            
            if (currentValue !== newValue) {
                statElements[key].style.transform = 'scale(1.1)';
                statElements[key].textContent = newValue.toLocaleString();
                setTimeout(() => {
                    statElements[key].style.transform = 'scale(1)';
                }, 200);
            }
        }
    });
}

// AJAX: Refresh recent activity
function refreshActivity() {
    fetch('api-admin-actions.php?action=get_recent_activity')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Check if there are new items
                checkForNewActivity(data.data);
            }
        })
        .catch(error => console.error('Activity refresh error:', error));
}

function checkForNewActivity(data) {
    const currentBookingCount = document.querySelectorAll('.recent-list .recent-item').length;
    const newBookingCount = data.bookings.length;
    
    if (newBookingCount > currentBookingCount) {
        showToast('New booking activity detected!', 'info');
        // Optional: Could reload specific sections instead of full page
        setTimeout(() => location.reload(), 2000);
    }
}

// Auto-refresh every 30 seconds
setInterval(() => {
    refreshStats();
    refreshActivity();
}, 30000);

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    .stat-card h3 {
        transition: transform 0.2s ease;
    }
`;
document.head.appendChild(style);
</script>

<?php renderAdminFooter(); ?>