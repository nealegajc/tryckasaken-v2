<?php
session_start();
require_once '../../config/dbConnection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../pages/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: users.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$action = $_POST['action'] ?? '';
$user_ids = $_POST['user_ids'] ?? [];

if (empty($user_ids) || empty($action)) {
    header("Location: users.php?error=invalid_request");
    exit();
}

$success_count = 0;
$error_count = 0;

foreach ($user_ids as $user_id) {
    $user_id = (int)$user_id;
    
    // Don't allow action on admin users
    $check_stmt = $conn->prepare("SELECT user_type FROM users WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $user = $result->fetch_assoc();
    $check_stmt->close();
    
    if ($user && $user['user_type'] == 'admin') {
        $error_count++;
        continue;
    }
    
    try {
        switch ($action) {
            case 'activate':
                $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                break;
                
            case 'suspend':
                $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                break;
                
            case 'delete':
                // Delete user's bookings first
                $delete_bookings = $conn->prepare("DELETE FROM tricycle_bookings WHERE user_id = ?");
                $delete_bookings->bind_param("i", $user_id);
                $delete_bookings->execute();
                $delete_bookings->close();
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                break;
                
            default:
                $error_count++;
                continue 2;
        }
        
        if ($stmt->execute()) {
            $success_count++;
        } else {
            $error_count++;
        }
        $stmt->close();
        
    } catch (Exception $e) {
        $error_count++;
    }
}

$database->closeConnection();

$message = "$success_count user(s) processed successfully";
if ($error_count > 0) {
    $message .= " ($error_count failed or skipped)";
}

header("Location: users.php?success=" . urlencode($message));
exit();
?>
