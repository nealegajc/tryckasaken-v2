<?php
session_start();
require_once '../../config/dbConnection.php';
require_once 'admin_layout.php';

$db = new Database();
$conn = $db->getConnection();

// Handle verification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $driverId = intval($_POST['driver_id']);
    $action = $_POST['action'];
    
    if ($action === 'verify') {
        $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_status = 'verified' WHERE user_id = ? AND user_type = 'driver'");
        $stmt->bind_param("i", $driverId);
        if ($stmt->execute()) {
            $success = "Driver verified successfully!";
        } else {
            $error = "Failed to verify driver.";
        }
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE users SET verification_status = 'rejected' WHERE user_id = ? AND user_type = 'driver'");
        $stmt->bind_param("i", $driverId);
        if ($stmt->execute()) {
            $success = "Driver application rejected.";
        } else {
            $error = "Failed to reject driver.";
        }
    }
}

// Get verification filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';

// Build query based on filter
$whereClause = "user_type = 'driver'";
switch($filter) {
    case 'pending':
        $whereClause .= " AND (verification_status = 'pending' OR verification_status IS NULL)";
        break;
    case 'verified':
        $whereClause .= " AND verification_status = 'verified'";
        break;
    case 'rejected':
        $whereClause .= " AND verification_status = 'rejected'";
        break;
}

$query = "SELECT user_id, name, email, phone, license_number, tricycle_info, created_at, 
                 is_verified, verification_status, is_active
          FROM users 
          WHERE $whereClause 
          ORDER BY created_at DESC";

$result = $conn->query($query);
$drivers = $result->fetch_all(MYSQLI_ASSOC);

renderAdminHeader("Driver Verification", "driver_verification");
?>

<?php if (isset($success)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle"></i> <?= $success ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="content-card">
    <div class="verification-tabs">
        <a href="?filter=pending" class="tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">
            <i class="bi bi-clock"></i> Pending Verification
        </a>
        <a href="?filter=verified" class="tab-btn <?= $filter === 'verified' ? 'active' : '' ?>">
            <i class="bi bi-check-circle"></i> Verified Drivers
        </a>
        <a href="?filter=rejected" class="tab-btn <?= $filter === 'rejected' ? 'active' : '' ?>">
            <i class="bi bi-x-circle"></i> Rejected Applications
        </a>
        <a href="?filter=all" class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">
            <i class="bi bi-list"></i> All Drivers
        </a>
    </div>
</div>

<!-- Drivers List -->
<div class="content-card">
    <h3>
        <i class="bi bi-shield-check"></i>
        <?php 
        switch($filter) {
            case 'pending': echo 'Pending Verification'; break;
            case 'verified': echo 'Verified Drivers'; break;
            case 'rejected': echo 'Rejected Applications'; break;
            default: echo 'All Drivers'; break;
        }
        ?> (<?= count($drivers) ?>)
    </h3>
    
    <?php if (count($drivers) > 0): ?>
        <div class="row g-4">
            <?php foreach ($drivers as $driver): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="driver-card">
                        <div class="driver-header">
                            <h5><?= htmlspecialchars($driver['name']) ?></h5>
                            <span class="verification-badge verification-<?= $driver['verification_status'] ?: 'pending' ?>">
                                <?= ucfirst($driver['verification_status'] ?: 'pending') ?>
                            </span>
                        </div>
                        
                        <div class="driver-info">
                            <div class="info-item">
                                <i class="bi bi-envelope"></i>
                                <span><?= htmlspecialchars($driver['email']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-phone"></i>
                                <span><?= htmlspecialchars($driver['phone']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-card-text"></i>
                                <span>License: <?= htmlspecialchars($driver['license_number']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-car-front"></i>
                                <span><?= htmlspecialchars($driver['tricycle_info']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-calendar"></i>
                                <span>Applied: <?= date('M d, Y', strtotime($driver['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <?php if (($driver['verification_status'] ?: 'pending') === 'pending'): ?>
                            <div class="driver-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="driver_id" value="<?= $driver['user_id'] ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <button type="submit" class="btn btn-success btn-sm" 
                                            onclick="return confirm('Verify this driver?')">
                                        <i class="bi bi-check-circle"></i> Verify
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="driver_id" value="<?= $driver['user_id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Reject this driver application?')">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                        
                        <div class="driver-actions">
                            <a href="view_user.php?id=<?= $driver['user_id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                            <a href="edit_user.php?id=<?= $driver['user_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-shield-x"></i>
            <h5>No Drivers Found</h5>
            <p>
                <?php 
                switch($filter) {
                    case 'pending': echo 'No drivers are currently pending verification.'; break;
                    case 'verified': echo 'No verified drivers found.'; break;
                    case 'rejected': echo 'No rejected applications found.'; break;
                    default: echo 'No drivers registered yet.'; break;
                }
                ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
.verification-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.tab-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid var(--primary-color);
    border-radius: 0.5rem;
    color: var(--primary-color);
    text-decoration: none;
    transition: all 0.3s ease;
}

.tab-btn:hover, .tab-btn.active {
    background: var(--primary-color);
    color: white;
}

.driver-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(40, 167, 69, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.driver-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.1);
}

.driver-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(40, 167, 69, 0.2);
}

.verification-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.verification-pending {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
    border: 1px solid #ffc107;
}

.verification-verified {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
    border: 1px solid #28a745;
}

.verification-rejected {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
    border: 1px solid #dc3545;
}

.driver-info {
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.info-item i {
    color: var(--primary-color);
    width: 1rem;
}

.driver-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}
</style>

<?php renderAdminFooter(); ?>