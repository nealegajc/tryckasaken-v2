<?php
session_start();
require_once '../../config/dbConnection.php';

if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$userId = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $status = $_POST['status'];
    $userType = $_POST['user_type'];
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone is required";
    }
    
    // Check if email is already taken by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Email is already taken by another user";
    }
    $stmt->close();
    
    if (empty($errors)) {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, status = ?, user_type = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $status, $userType, $userId);
        
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: view_user.php?id=$userId&success=User information updated successfully!");
            exit;
        } else {
            $errors[] = "Failed to update user: " . $conn->error;
        }
        $stmt->close();
    }
}

// Get user information
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: admin.php?error=user_not_found');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit User - <?= htmlspecialchars($user['name']) ?> | TrycKaSaken Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/glass.css">
  <style>
    body {
      min-height: 100vh;
      padding: 0;
    }
    
    /* Glass Navbar */
    .glass-navbar {
      background: var(--glass-white);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--glass-border);
      padding: 20px 0;
      margin-bottom: 40px;
      box-shadow: var(--glass-shadow);
    }
    
    .navbar-brand {
      font-size: 24px;
      font-weight: 700;
      color: var(--text-primary);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .navbar-brand i {
      color: var(--primary-color);
    }
    
    /* Form Card */
    .form-card {
      max-width: 900px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    .glass-form-card {
      background: var(--glass-white);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      border: 1px solid var(--glass-border);
      padding: 40px;
      box-shadow: var(--glass-shadow);
      animation: slideUp 0.6s ease;
    }
    
    .card-header-glass {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      padding: 20px 28px;
      border-radius: 16px 16px 0 0;
      margin: -40px -40px 32px -40px;
    }
    
    .card-header-glass h5 {
      margin: 0;
      color: white;
      font-size: 22px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    /* Form styling */
    .form-label {
      font-weight: 600;
      color: var(--text-primary);
      margin-bottom: 8px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .form-label i {
      color: var(--primary-color);
    }
    
    .text-danger {
      color: #dc3545;
    }
    
    .form-control,
    .form-select {
      background: var(--glass-white);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      padding: 14px 16px;
      color: var(--text-primary);
      transition: all 0.3s ease;
      font-size: 15px;
    }
    
    .form-control:focus,
    .form-select:focus {
      background: rgba(255, 255, 255, 0.25);
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-2px);
    }
    
    .form-text {
      font-size: 13px;
      color: var(--text-secondary);
      margin-top: 6px;
    }
    
    /* Buttons */
    .btn-save {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      padding: 14px 32px;
      transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }
    
    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
    }
    
    .btn-cancel {
      background: var(--glass-white);
      backdrop-filter: blur(10px);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      color: var(--text-primary);
      font-weight: 600;
      padding: 14px 32px;
      transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      border-color: var(--primary-color);
      color: var(--primary-color);
    }
    
    /* Responsive */
    @media (max-width: 575px) {
      .glass-form-card {
        padding: 24px 20px;
      }
      
      .card-header-glass {
        margin: -24px -20px 24px -20px;
        padding: 16px 20px;
      }
      
      .card-header-glass h5 {
        font-size: 18px;
      }
    }
  </style>
</head>
<body>

<nav class="glass-navbar">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center">
      <a class="navbar-brand" href="admin.php">
        <i class="bi bi-pencil-square"></i> Edit User
      </a>
      <div class="d-flex gap-2">
      <div class="d-flex gap-2">
        <a href="view_user.php?id=<?= $userId ?>" class="glass-btn glass-btn-sm">
          <i class="bi bi-arrow-left"></i> Back to User
        </a>
        <a href="admin.php" class="glass-btn glass-btn-sm">
          <i class="bi bi-house"></i> Dashboard
        </a>
        <a href="../../pages/auth/logout.php" class="glass-btn glass-btn-danger glass-btn-sm">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="container">
  <div class="form-card">
    <!-- Errors -->
    <?php if (isset($errors) && count($errors) > 0): ?>
      <div class="glass-alert glass-alert-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
          <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="glass-form-card">
      <div class="card-header-glass">
        <h5>
          <i class="bi bi-person-gear"></i> Edit User Information
        </h5>
      </div>
        <form method="POST" action="">
          <div class="row">
            <div class="col-md-12 mb-3">
              <label for="name" class="form-label">
                <i class="bi bi-person"></i> Full Name <span class="text-danger">*</span>
              </label>
              <input type="text" 
                     class="form-control" 
                     id="name" 
                     name="name" 
                     value="<?= htmlspecialchars($user['name']) ?>" 
                     required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="email" class="form-label">
                <i class="bi bi-envelope"></i> Email Address <span class="text-danger">*</span>
              </label>
              <input type="email" 
                     class="form-control" 
                     id="email" 
                     name="email" 
                     value="<?= htmlspecialchars($user['email']) ?>" 
                     required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="phone" class="form-label">
                <i class="bi bi-phone"></i> Phone Number <span class="text-danger">*</span>
              </label>
              <input type="text" 
                     class="form-control" 
                     id="phone" 
                     name="phone" 
                     value="<?= htmlspecialchars($user['phone']) ?>" 
                     required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="user_type" class="form-label">
                <i class="bi bi-person-badge"></i> User Type <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="user_type" name="user_type" required>
                <option value="passenger" <?= $user['user_type'] === 'passenger' ? 'selected' : '' ?>>Passenger</option>
                <option value="driver" <?= $user['user_type'] === 'driver' ? 'selected' : '' ?>>Driver</option>
              </select>
              <div class="form-text">
                <i class="bi bi-info-circle"></i> Changing user type may affect access to features
              </div>
            </div>

            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">
                <i class="bi bi-toggle-on"></i> Account Status <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="status" name="status" required>
                <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="suspended" <?= $user['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
              </select>
              <div class="form-text">
                <i class="bi bi-info-circle"></i> Inactive/Suspended users cannot log in
              </div>
            </div>

            <div class="col-md-12">
              <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i> 
                <strong>Note:</strong> User ID: #<?= $user['user_id'] ?> | 
                Registered: <?= date('F d, Y', strtotime($user['created_at'])) ?>
              </div>
            </div>
          </div>

          <div class="d-flex gap-3 justify-content-end mt-4">
            <a href="view_user.php?id=<?= $userId ?>" class="btn-cancel">
              <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn-save">
              <i class="bi bi-check-circle-fill"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Danger Zone -->
    <div class="glass-form-card mt-4" style="border-color: rgba(220, 53, 69, 0.3);">
      <div class="card-header-glass" style="background: linear-gradient(135deg, #ee0979, #ff6a00);">
        <h5>
          <i class="bi bi-exclamation-triangle-fill"></i> Danger Zone
        </h5>
      </div>
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
          <strong style="color: var(--text-primary); font-size: 16px;">Delete User Account</strong>
          <p style="color: var(--text-secondary); margin: 4px 0 0 0;">Once deleted, this user's data cannot be recovered.</p>
        </div>
        <a href="delete_user.php?id=<?= $userId ?>" 
           class="glass-btn glass-btn-danger"
           onclick="return confirm('⚠️ WARNING: This will permanently delete the user and all associated data!\n\nUser: <?= htmlspecialchars($user['name']) ?>\nEmail: <?= htmlspecialchars($user['email']) ?>\n\nAre you absolutely sure?')">
          <i class="bi bi-trash-fill"></i> Delete Account
        </a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
$db->closeConnection();
?>
