<?php
session_start();
require_once '../../config/dbConnection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        $user_type = $_POST['user_type'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } else {
            // Check if email exists
            $check_sql = "SELECT email FROM users WHERE email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (user_type, name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $user_type, $name, $email, $phone, $hashed_password);

                if ($stmt->execute()) {
                    $success = "Registration successful! You can now login.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
                $stmt->close();
            }
            $check_stmt->close();
        }
        $conn->close();
    } else {
        $error = "Database connection failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TrycKaSaken</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../../public/css/style.css">
    <style>
      .register-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
      }

      .register-card {
        background: var(--glass-bg);
        backdrop-filter: var(--blur-lg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-2xl);
        padding: 48px;
        max-width: 500px;
        width: 100%;
        box-shadow: var(--shadow-xl);
      }

      .register-header {
        text-align: center;
        margin-bottom: 36px;
      }

      .register-header .logo {
        font-size: 3rem;
        color: var(--color-primary);
        margin-bottom: 12px;
      }

      .register-header h1 {
        font-size: 1.8rem;
        color: var(--color-gray-900);
        font-weight: 800;
        margin-bottom: 8px;
      }

      .register-header p {
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

      .form-control,
      .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        background: rgba(255, 255, 255, 0.5);
        font-size: 1rem;
        transition: all var(--transition-base);
      }

      .form-control:focus,
      .form-select:focus {
        outline: none;
        border-color: var(--color-primary);
        background: rgba(255, 255, 255, 0.8);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
      }

      .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
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

      .register-footer {
        text-align: center;
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--glass-border);
      }

      .register-footer a {
        color: var(--color-primary);
        font-weight: 600;
      }

      .register-footer a:hover {
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

      .alert-message {
        border-radius: var(--radius-lg);
        padding: 12px 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #dc2626;
      }

      .alert-success {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.3);
        color: var(--color-primary);
      }

      .register-tabs {
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

      .register-form {
        width: 100%;
      }
    </style>
</head>
<body>
    <div class="register-container">
      <div>
        <a href="../../index.php" class="back-link">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>

        <div class="register-card">
          <div class="register-header">
            <div class="logo">
              <i class="bi bi-truck"></i>
            </div>
            <h1>Join TrycKaSaken</h1>
            <p>Create your account today</p>
          </div>

          <?php if ($error): ?>
            <div class="alert-message alert-error">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <span><?php echo htmlspecialchars($error); ?></span>
            </div>
          <?php endif; ?>
          
          <?php if ($success): ?>
            <div class="alert-message alert-success">
              <i class="bi bi-check-circle-fill"></i>
              <span><?php echo htmlspecialchars($success); ?></span>
            </div>
          <?php endif; ?>

          <!-- Registration Tabs -->
          <div class="register-tabs">
            <button type="button" class="tab-button active" data-tab="passenger">
              <i class="bi bi-person"></i> Passenger
            </button>
            <button type="button" class="tab-button" data-tab="driver">
              <i class="bi bi-car-front"></i> Driver
            </button>
          </div>

          <!-- Passenger Registration Form -->
          <div class="tab-content active" id="passenger-tab">
            <form action="register.php" method="POST" class="register-form">
              <input type="hidden" name="user_type" value="passenger">
              
              <div class="form-group">
                <label for="passenger-name" class="form-label">
                  <i class="bi bi-person"></i> Full Name
                </label>
                <input type="text" 
                       class="form-control" 
                       id="passenger-name" 
                       name="name" 
                       placeholder="Your full name"
                       value="<?php echo isset($_POST['name']) && (!isset($_POST['user_type']) || $_POST['user_type'] == 'passenger') ? htmlspecialchars($_POST['name']) : ''; ?>"
                       required>
              </div>

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
                <label for="passenger-phone" class="form-label">
                  <i class="bi bi-telephone"></i> Phone Number
                </label>
                <input type="tel" 
                       class="form-control" 
                       id="passenger-phone" 
                       name="phone" 
                       placeholder="Your phone number"
                       value="<?php echo isset($_POST['phone']) && (!isset($_POST['user_type']) || $_POST['user_type'] == 'passenger') ? htmlspecialchars($_POST['phone']) : ''; ?>"
                       required>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="passenger-password" class="form-label">
                    <i class="bi bi-lock"></i> Password
                  </label>
                  <input type="password" 
                         class="form-control" 
                         id="passenger-password" 
                         name="password" 
                         placeholder="Create a password"
                         required>
                </div>

                <div class="form-group">
                  <label for="passenger-confirm-password" class="form-label">
                    <i class="bi bi-lock-check"></i> Confirm Password
                  </label>
                  <input type="password" 
                         class="form-control" 
                         id="passenger-confirm-password" 
                         name="confirm_password" 
                         placeholder="Confirm your password"
                         required>
                </div>
              </div>

              <button type="submit" class="submit-btn">
                <i class="bi bi-person-plus me-2"></i> Register as Passenger
              </button>
            </form>
          </div>

          <!-- Driver Registration Form -->
          <div class="tab-content" id="driver-tab">
            <form action="register.php" method="POST" class="register-form">
              <input type="hidden" name="user_type" value="driver">
              
              <div class="form-group">
                <label for="driver-name" class="form-label">
                  <i class="bi bi-person"></i> Full Name
                </label>
                <input type="text" 
                       class="form-control" 
                       id="driver-name" 
                       name="name" 
                       placeholder="Your full name"
                       value="<?php echo isset($_POST['name']) && isset($_POST['user_type']) && $_POST['user_type'] == 'driver' ? htmlspecialchars($_POST['name']) : ''; ?>"
                       required>
              </div>

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
                <label for="driver-phone" class="form-label">
                  <i class="bi bi-telephone"></i> Phone Number
                </label>
                <input type="tel" 
                       class="form-control" 
                       id="driver-phone" 
                       name="phone" 
                       placeholder="Your phone number"
                       value="<?php echo isset($_POST['phone']) && isset($_POST['user_type']) && $_POST['user_type'] == 'driver' ? htmlspecialchars($_POST['phone']) : ''; ?>"
                       required>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="driver-password" class="form-label">
                    <i class="bi bi-lock"></i> Password
                  </label>
                  <input type="password" 
                         class="form-control" 
                         id="driver-password" 
                         name="password" 
                         placeholder="Create a password"
                         required>
                </div>

                <div class="form-group">
                  <label for="driver-confirm-password" class="form-label">
                    <i class="bi bi-lock-check"></i> Confirm Password
                  </label>
                  <input type="password" 
                         class="form-control" 
                         id="driver-confirm-password" 
                         name="confirm_password" 
                         placeholder="Confirm your password"
                         required>
                </div>
              </div>

              <div class="alert-message" style="background: rgba(22, 163, 74, 0.1); border: 1px solid rgba(22, 163, 74, 0.3); color: #16a34a;">
                <i class="bi bi-info-circle-fill"></i>
                <span><strong>Note:</strong> Driver accounts require verification. You'll need to upload your documents after registration.</span>
              </div>

              <button type="submit" class="submit-btn">
                <i class="bi bi-person-plus me-2"></i> Register as Driver
              </button>
            </form>
          </div>

          <div class="register-footer">
            <p class="mb-0">Already have an account? <a href="login.php">Sign in here</a></p>
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
        if (tabParam && ['passenger', 'driver'].includes(tabParam)) {
          const targetButton = document.querySelector(`[data-tab="${tabParam}"]`);
          if (targetButton) {
            targetButton.click();
          }
        }
      });
    </script>
</body>
</html>
