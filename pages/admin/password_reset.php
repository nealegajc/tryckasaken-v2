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

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get user details
$stmt = $conn->prepare("SELECT user_id, name, email, user_type, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: users.php?error=User not found");
    exit();
}

// Prevent resetting admin passwords
if ($user['user_type'] == 'admin') {
    header("Location: users.php?error=Cannot reset admin password");
    exit();
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            $update_stmt->close();
            
            // Log the password reset action (optional - you can create a logs table)
            // For now, redirect with success message
            $database->closeConnection();
            header("Location: users.php?success=Password reset successfully for " . urlencode($user['name']));
            exit();
        } else {
            $error = "Failed to reset password";
        }
        $update_stmt->close();
    }
}

$database->closeConnection();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password | TrycKaSaken Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/glass.css">
  <style>
    body {
      background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      padding: 20px;
    }
    
    .floating-shape {
      position: fixed;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      animation: float 20s infinite ease-in-out;
      z-index: 1;
    }
    
    .shape-1 { width: 300px; height: 300px; top: 10%; left: 5%; animation-delay: 0s; }
    .shape-2 { width: 200px; height: 200px; top: 60%; right: 10%; animation-delay: 3s; }
    .shape-3 { width: 150px; height: 150px; bottom: 15%; left: 15%; animation-delay: 6s; }
    
    @keyframes float {
      0%, 100% { transform: translateY(0px) translateX(0px); }
      33% { transform: translateY(-30px) translateX(30px); }
      66% { transform: translateY(30px) translateX(-30px); }
    }
    
    .reset-container {
      max-width: 500px;
      width: 100%;
      z-index: 10;
      position: relative;
    }
    
    .glass-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 24px;
      padding: 40px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .card-header i {
      font-size: 3rem;
      color: white;
      margin-bottom: 16px;
      display: block;
    }
    
    .card-header h3 {
      color: white;
      font-weight: 700;
      margin-bottom: 8px;
    }
    
    .card-header p {
      color: rgba(255, 255, 255, 0.8);
      margin: 0;
    }
    
    .user-info {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 30px;
    }
    
    .user-info-row {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      color: white;
    }
    
    .user-info-label {
      opacity: 0.8;
      font-weight: 500;
    }
    
    .user-info-value {
      font-weight: 600;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      color: white;
      font-weight: 600;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .glass-input {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 12px;
      color: white;
      padding: 14px;
      width: 100%;
      font-size: 1rem;
      transition: all 0.3s ease;
    }
    
    .glass-input::placeholder {
      color: rgba(255, 255, 255, 0.5);
    }
    
    .glass-input:focus {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.5);
      outline: none;
      box-shadow: 0 0 20px rgba(255, 255, 255, 0.1);
    }
    
    .password-toggle {
      position: relative;
    }
    
    .password-toggle .toggle-btn {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      cursor: pointer;
      font-size: 1.2rem;
      transition: color 0.3s ease;
    }
    
    .password-toggle .toggle-btn:hover {
      color: white;
    }
    
    .warning-box {
      background: rgba(255, 193, 7, 0.2);
      border: 1px solid rgba(255, 193, 7, 0.4);
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 24px;
      color: white;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .warning-box i {
      font-size: 1.5rem;
      color: #ffc107;
    }
    
    .btn-group-custom {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }
    
    .btn-group-custom .glass-btn {
      flex: 1;
      padding: 14px;
      font-size: 1rem;
      font-weight: 600;
    }
    
    .password-strength {
      margin-top: 8px;
      font-size: 0.85rem;
      color: white;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .password-strength.visible {
      opacity: 1;
    }
    
    .strength-weak { color: #ef5350; }
    .strength-medium { color: #ffc107; }
    .strength-strong { color: #4caf50; }
  </style>
</head>
<body>

<div class="floating-shape shape-1"></div>
<div class="floating-shape shape-2"></div>
<div class="floating-shape shape-3"></div>

<div class="reset-container">
  <div class="glass-card">
    <div class="card-header">
      <i class="bi bi-shield-lock"></i>
      <h3>Reset User Password</h3>
      <p>Set a new password for this user account</p>
    </div>

    <?php if (isset($error)): ?>
      <div class="glass-alert glass-alert-danger mb-4">
        <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- User Information -->
    <div class="user-info">
      <div class="user-info-row">
        <span class="user-info-label"><i class="bi bi-person"></i> Name:</span>
        <span class="user-info-value"><?= htmlspecialchars($user['name']) ?></span>
      </div>
      <div class="user-info-row">
        <span class="user-info-label"><i class="bi bi-envelope"></i> Email:</span>
        <span class="user-info-value"><?= htmlspecialchars($user['email']) ?></span>
      </div>
      <div class="user-info-row">
        <span class="user-info-label"><i class="bi bi-tag"></i> Type:</span>
        <span class="user-info-value"><?= ucfirst($user['user_type']) ?></span>
      </div>
    </div>

    <div class="warning-box">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>
        <strong>Security Notice:</strong><br>
        The user will be able to log in immediately with the new password.
      </div>
    </div>

    <!-- Password Reset Form -->
    <form method="POST" action="" id="resetForm">
      <div class="form-group">
        <label><i class="bi bi-lock"></i> New Password</label>
        <div class="password-toggle">
          <input 
            type="password" 
            name="new_password" 
            id="newPassword"
            class="glass-input" 
            placeholder="Enter new password (min. 6 characters)" 
            required 
            minlength="6"
            autocomplete="new-password">
          <button type="button" class="toggle-btn" onclick="togglePassword('newPassword', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="password-strength" id="passwordStrength"></div>
      </div>

      <div class="form-group">
        <label><i class="bi bi-lock-fill"></i> Confirm Password</label>
        <div class="password-toggle">
          <input 
            type="password" 
            name="confirm_password" 
            id="confirmPassword"
            class="glass-input" 
            placeholder="Re-enter new password" 
            required 
            minlength="6"
            autocomplete="new-password">
          <button type="button" class="toggle-btn" onclick="togglePassword('confirmPassword', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <div class="btn-group-custom">
        <a href="users.php" class="glass-btn glass-btn-secondary">
          <i class="bi bi-x-circle"></i> Cancel
        </a>
        <button type="submit" class="glass-btn glass-btn-success">
          <i class="bi bi-check-circle"></i> Reset Password
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
      input.type = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type = 'password';
      icon.className = 'bi bi-eye';
    }
  }

  // Password strength indicator
  const passwordInput = document.getElementById('newPassword');
  const strengthIndicator = document.getElementById('passwordStrength');

  passwordInput.addEventListener('input', function() {
    const password = this.value;
    const strength = calculateStrength(password);
    
    if (password.length === 0) {
      strengthIndicator.classList.remove('visible');
      return;
    }
    
    strengthIndicator.classList.add('visible');
    
    if (strength < 30) {
      strengthIndicator.className = 'password-strength visible strength-weak';
      strengthIndicator.innerHTML = '<i class="bi bi-exclamation-circle"></i> Weak password';
    } else if (strength < 60) {
      strengthIndicator.className = 'password-strength visible strength-medium';
      strengthIndicator.innerHTML = '<i class="bi bi-dash-circle"></i> Medium password';
    } else {
      strengthIndicator.className = 'password-strength visible strength-strong';
      strengthIndicator.innerHTML = '<i class="bi bi-check-circle"></i> Strong password';
    }
  });

  function calculateStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength += 20;
    if (password.length >= 10) strength += 10;
    if (/[a-z]/.test(password)) strength += 15;
    if (/[A-Z]/.test(password)) strength += 15;
    if (/[0-9]/.test(password)) strength += 15;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 25;
    
    return strength;
  }

  // Form validation
  document.getElementById('resetForm').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
      e.preventDefault();
      alert('Passwords do not match!');
      return false;
    }
    
    if (newPassword.length < 6) {
      e.preventDefault();
      alert('Password must be at least 6 characters long!');
      return false;
    }
    
    if (!confirm('Are you sure you want to reset this user\'s password?')) {
      e.preventDefault();
      return false;
    }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
