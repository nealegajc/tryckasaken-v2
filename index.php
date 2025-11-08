<?php
/**
 * Auto-setup database if not exists
 * Silent setup - creates database in background without showing setup page
 */

// Start output buffering to prevent any unexpected output
ob_start();

// Suppress all errors for clean HTML output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/database/schema.php';

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tric_db';

try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    if ($conn->connect_error) {
        // Log error but don't die - continue with page load
        error_log("MySQL Connection Failed: " . $conn->connect_error);
    } else {
        // Check if database exists
        $db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
        
        if ($db_check && $db_check->num_rows == 0) {
            // Database doesn't exist - create it silently
            $conn->query("CREATE DATABASE $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $conn->select_db($db_name);
            DatabaseSchema::createSchema($conn, true);
        } else if ($db_check) {
            // Database exists, verify schema
            $conn->select_db($db_name);
            
            if (!DatabaseSchema::schemaExists($conn)) {
                // Tables don't exist - create them silently
                DatabaseSchema::createSchema($conn, true);
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    // Log error but don't die - continue with page load
    error_log("Database setup failed: " . $e->getMessage());
}

// Clean the output buffer to ensure no unexpected output
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrycKaSaken - Tricycle Booking System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom Glassmorphic Styles -->
    <link rel="stylesheet" href="public/css/style.css">
    <style>
      .hero-section {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        position: relative;
      }

      .hero-content {
        text-align: center;
        max-width: 800px;
      }

      .hero-content h1 {
        font-size: 4rem;
        font-weight: 800;
        color: var(--color-primary);
        margin-bottom: 24px;
        line-height: 1.1;
      }

      .hero-content p {
        font-size: 1.3rem;
        color: var(--color-gray-600);
        margin-bottom: 40px;
      }

      .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 24px;
        margin: 80px 0;
      }

      .feature-card {
        background: var(--glass-bg);
        backdrop-filter: var(--blur-lg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-xl);
        padding: 32px;
        text-align: center;
        transition: all var(--transition-base);
      }

      .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        border-color: var(--color-primary);
      }

      .feature-icon {
        font-size: 3rem;
        color: var(--color-primary);
        margin-bottom: 16px;
      }

      .feature-card h3 {
        color: var(--color-gray-900);
        font-weight: 700;
        margin-bottom: 12px;
      }

      .feature-card p {
        color: var(--color-gray-600);
        margin: 0;
      }

      .cta-buttons {
        display: flex;
        gap: 16px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .cta-btn {
        padding: 14px 32px;
        border-radius: var(--radius-full);
        font-weight: 600;
        text-decoration: none;
        transition: all var(--transition-base);
        display: inline-flex;
        align-items: center;
        gap: 10px;
      }

      .cta-btn-primary {
        background: var(--gradient-green);
        color: white;
        box-shadow: var(--shadow-green);
      }

      .cta-btn-primary:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(16, 185, 129, 0.4);
        color: white;
      }

      .cta-btn-secondary {
        background: var(--glass-bg);
        border: 2px solid var(--color-primary);
        color: var(--color-primary);
      }

      .cta-btn-secondary:hover {
        background: rgba(16, 185, 129, 0.1);
        color: var(--color-primary);
      }

      .benefits-section {
        background: var(--glass-bg);
        backdrop-filter: var(--blur-lg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-2xl);
        padding: 60px 40px;
        margin: 80px 0;
        box-shadow: var(--shadow-lg);
      }

      .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 32px;
        margin-top: 40px;
      }

      .benefit-item h4 {
        color: var(--color-primary);
        font-weight: 700;
        margin-bottom: 12px;
      }

      .benefit-item p {
        color: var(--color-gray-600);
        margin: 0;
      }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
      <div class="container">
        <a class="navbar-brand" href="index.php">
          <i class="bi bi-truck"></i> TrycKaSaken
        </a>
        <div class="nav-menu">
          <a href="pages/auth/login.php" class="nav-link">
            <i class="bi bi-box-arrow-in-right"></i> Login
          </a>
          <a href="pages/auth/register.php" class="btn btn-primary" style="font-size: 0.9rem; padding: 8px 20px;">
            <i class="bi bi-person-plus"></i> Register
          </a>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-content">
        <h1><i class="bi bi-truck"></i> TrycKaSaken</h1>
        <p>Your Reliable Tricycle Booking Service - Safe, Fast, and Convenient</p>
        
        <div class="cta-buttons">
          <a href="pages/auth/register.php" class="cta-btn cta-btn-primary">
            <i class="bi bi-person-plus"></i> Register Now
          </a>
          <a href="pages/auth/login.php" class="cta-btn cta-btn-secondary">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
          </a>
        </div>
      </div>
    </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
