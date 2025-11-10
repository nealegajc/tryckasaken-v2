<?php
session_start();
require_once '../../config/Database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $requested_user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'passenger';
    
    $sql = "SELECT user_id, user_type, name, email, password, status FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            if ($user['status'] != 'active') {
                $error = "Your account has been suspended or deactivated.";
            } else {
                // Check if the user type matches the requested login type
                if ($user['user_type'] !== $requested_user_type) {
                    $user_type_names = [
                        'passenger' => 'Passenger',
                        'driver' => 'Driver', 
                        'admin' => 'Admin'
                    ];
                    $error = "This account is registered as " . $user_type_names[$user['user_type']] . ". Please use the correct login tab.";
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_type'] = $user['user_type']; 
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    
                    if ($user['user_type'] == 'driver') {
                        $driver_sql = "SELECT verification_status FROM drivers WHERE user_id = ?";
                        $driver_stmt = $conn->prepare($driver_sql);
                        $driver_stmt->bind_param("i", $user['user_id']);
                        $driver_stmt->execute();
                        $driver_result = $driver_stmt->get_result();
                        
                        if ($driver_result->num_rows > 0) {
                            $driver = $driver_result->fetch_assoc();
                            $_SESSION['verification_status'] = $driver['verification_status'];
                        }
                        
                        $driver_stmt->close();
                        header("Location: ../../pages/driver/login-form.php");
                    } elseif ($user['user_type'] == 'admin') {
                        header("Location: ../../pages/admin/dashboard.php");
                    } else {
                        header("Location: ../../pages/passenger/login-form.php");
                    }
                    exit();
                }
            }
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
    
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TrycKaSaken</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
      .login-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
      }

      .login-card {
        background: var(--glass-bg);
        backdrop-filter: var(--blur-lg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-2xl);
        padding: 48px;
        max-width: 450px;
        width: 100%;
        box-shadow: var(--shadow-xl);
      }

      .login-header {
        text-align: center;
        margin-bottom: 36px;
      }

      .login-header .logo {
        font-size: 3rem;
        color: var(--color-primary);
        margin-bottom: 12px;
      }

      .login-header h1 {
        font-size: 1.8rem;
        color: var(--color-gray-900);
        font-weight: 800;
        margin-bottom: 8px;
      }

      .login-header p {
        color: var(--color-gray-600);
        margin: 0;
      }

      .form-group {
        margin-bottom: 20px;
      }

      .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--color-gray-700);
      }

      .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        background: rgba(255, 255, 255, 0.5);
        font-size: 1rem;
        transition: all var(--transition-base);
      }

      .form-control:focus {
        outline: none;
        border-color: var(--color-primary);
        background: rgba(255, 255, 255, 0.8);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
      }

      .submit-btn {
        width: 100%;
        padding: 12px 24px;
        background: var(--gradient-green);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all var(--transition-base);
        box-shadow: var(--shadow-green);
        margin-top: 24px;
      }

      .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 32px rgba(16, 185, 129, 0.3);
        color: white;
      }

      .login-footer {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--glass-border);
      }

      .login-footer a {
        color: var(--color-primary);
        font-weight: 600;
      }

      .login-footer a:hover {
        color: var(--color-primary-dark);
      }

      .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        color: var(--color-primary);
        text-decoration: none;
        font-weight: 600;
      }

      .back-link:hover {
        color: var(--color-primary-dark);
      }

      .error-alert {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: var(--radius-lg);
        padding: 12px 16px;
        margin-bottom: 24px;
        color: #dc2626;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .login-tabs {
        display: flex;
        margin-bottom: 32px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--radius-lg);
        padding: 4px;
      }

      .tab-button {
        flex: 1;
        padding: 12px 16px;
        border: none;
        background: transparent;
        color: var(--color-gray-600);
        font-weight: 600;
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
      }

      .tab-button:hover {
        color: var(--color-primary);
        background: rgba(255, 255, 255, 0.1);
      }

      .tab-button.active {
        background: var(--color-primary);
        color: white;
        box-shadow: var(--shadow-md);
      }

      .tab-content {
        display: none;
      }

      .tab-content.active {
        display: block;
      }

      .login-form {
        width: 100%;
      }
    </style>
</head>
<body>
    <div class="login-container">
      <div>
        <a href="../../index.php" class="back-link">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <div class="login-card">
          <div class="login-header">
            <div class="logo">
              <i class="bi bi-truck"></i>
            </div>
            <h1>Sign in</h1>
          </div>

          <?php if ($error): ?>
            <div class="error-alert">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>

          <!-- Login Tabs -->
          <div class="login-tabs">
            <button type="button" class="tab-button active" data-tab="passenger">
              <i class="bi bi-person"></i> Passenger
            </button>
            <button type="button" class="tab-button" data-tab="driver">
              <i class="bi bi-car-front"></i> Driver
            </button>
            <button type="button" class="tab-button" data-tab="admin">
              <i class="bi bi-gear"></i> Admin
            </button>
          </div>

          <!-- Passenger Login Form -->
          <div class="tab-content active" id="passenger-tab">
            <form action="login-form.php" method="POST" class="login-form">
              <input type="hidden" name="user_type" value="passenger">
              <div class="form-group">
                <label for="passenger-email" class="form-label">
                  <i class="bi bi-envelope"></i> Email Address
                </label>
                <input type="email" 
                       class="form-control" 
                       id="passenger-email" 
                       name="email" 
                       placeholder="Enter your email"
                       value="<?php echo isset($_POST['email']) && (!isset($_POST['user_type']) || $_POST['user_type'] == 'passenger') ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
              </div>

              <div class="form-group">
                <label for="passenger-password" class="form-label">
                  <i class="bi bi-lock"></i> Password
                </label>
                <input type="password" 
                       class="form-control" 
                       id="passenger-password" 
                       name="password" 
                       placeholder="Enter your password"
                       required>
              </div>

              <button type="submit" class="submit-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In as Passenger
              </button>
            </form>
          </div>

          <!-- Driver Login Form -->
          <div class="tab-content" id="driver-tab">
            <form action="login-form.php" method="POST" class="login-form">
              <input type="hidden" name="user_type" value="driver">
              <div class="form-group">
                <label for="driver-email" class="form-label">
                  <i class="bi bi-envelope"></i> Email Address
                </label>
                <input type="email" 
                       class="form-control" 
                       id="driver-email" 
                       name="email" 
                       placeholder="Enter your email"
                       value="<?php echo isset($_POST['email']) && isset($_POST['user_type']) && $_POST['user_type'] == 'driver' ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
              </div>

              <div class="form-group">
                <label for="driver-password" class="form-label">
                  <i class="bi bi-lock"></i> Password
                </label>
                <input type="password" 
                       class="form-control" 
                       id="driver-password" 
                       name="password" 
                       placeholder="Enter your password"
                       required>
              </div>

              <button type="submit" class="submit-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In as Driver
              </button>
            </form>
          </div>

          <!-- Admin Login Form -->
          <div class="tab-content" id="admin-tab">
            <form action="login-form.php" method="POST" class="login-form">
              <input type="hidden" name="user_type" value="admin">
              <div class="form-group">
                <label for="admin-email" class="form-label">
                  <i class="bi bi-envelope"></i> Email Address
                </label>
                <input type="email" 
                       class="form-control" 
                       id="admin-email" 
                       name="email" 
                       placeholder="Enter your email"
                       value="<?php echo isset($_POST['email']) && isset($_POST['user_type']) && $_POST['user_type'] == 'admin' ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
              </div>

              <div class="form-group">
                <label for="admin-password" class="form-label">
                  <i class="bi bi-lock"></i> Password
                </label>
                <input type="password" 
                       class="form-control" 
                       id="admin-password" 
                       name="password" 
                       placeholder="Enter your password"
                       required>
              </div>

              <button type="submit" class="submit-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In as Admin
              </button>
            </form>
          </div>

          <div class="login-footer">
            <p class="mb-0">Don't have an account? <a href="register-form.php">Create one now</a></p>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      // Tab switching functionality
      document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        // Handle tab switching
        tabButtons.forEach(button => {
          button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
          });
        });

        // Auto-select tab based on error state or URL parameter
        <?php if (isset($_POST['user_type'])): ?>
          const userType = '<?php echo $_POST['user_type']; ?>';
          const targetButton = document.querySelector(`[data-tab="${userType}"]`);
          if (targetButton) {
            targetButton.click();
          }
        <?php endif; ?>

        // Handle URL parameters for direct tab access
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if (tabParam && ['passenger', 'driver', 'admin'].includes(tabParam)) {
          const targetButton = document.querySelector(`[data-tab="${tabParam}"]`);
          if (targetButton) {
            targetButton.click();
          }
        }
      });
    </script>
</body>
</html>
