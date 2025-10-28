<?php
/**
 * Maintenance Mode Display
 * Shown when site is in maintenance mode
 * Includes login form for admins to bypass
 */

require_once 'config.php';

$maintenance_file = __DIR__ . '/.maintenance';

if (file_exists($maintenance_file)) {
    $maintenance_data = json_decode(file_get_contents($maintenance_file), true);
    $message = isset($maintenance_data['message']) ? $maintenance_data['message'] : 'We are currently performing scheduled maintenance. Please check back later.';
    
    // Check if user is trying to log in
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!empty($email) && !empty($password)) {
            try {
                $conn = db_connect();
                $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        session_start();
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = isset($user['role']) ? $user['role'] : 'user';
                        
                        $stmt->close();
                        $conn->close();
                        
                        // Only admin can access during maintenance
                        if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_email'] === 'nic@blacnova.net') {
                            header('Location: admin.php');
                            exit;
                        } else {
                            $error = "Only admins can access during maintenance";
                        }
                    } else {
                        $stmt->close();
                        $conn->close();
                    }
                } else {
                    $stmt->close();
                    $conn->close();
                    }
                }
            } catch (Exception $e) {
                $error = "Login failed";
            }
        }
    }
} else {
    // If not in maintenance, redirect to index
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - Yucca Club</title>
    <link rel="icon" type="image/png" href="ui/img/favicon.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Lora:wght@500;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #F5F1E9 0%, #ede9df 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .maintenance-container {
            text-align: center;
            max-width: 600px;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .icon {
            font-size: 4rem;
            color: #a8aa19;
            margin-bottom: 1.5rem;
        }
        h1 {
            font-family: 'Lora', serif;
            font-size: 2.5rem;
            color: #63666A;
            margin-bottom: 1rem;
        }
        p {
            font-size: 1.1rem;
            color: #63666A;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .logo {
            max-width: 200px;
            margin: 0 auto 2rem;
            opacity: 0.8;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        .admin-login {
            margin-top: 2rem;
            padding: 2rem;
            background: #f5f1e9;
            border-radius: 12px;
            text-align: left;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .admin-login h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: #63666A;
        }
        .admin-login input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        .admin-login button {
            width: 100%;
            padding: 0.75rem;
            background: #a8aa19;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        .admin-login button:hover {
            background: #8b8c14;
        }
        .error {
            color: #A81919;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <img src="ui/img/logo.png" alt="Yucca Club Logo" class="logo">
        <div class="icon pulse">
            <i class="fas fa-tools"></i>
        </div>
        <h1>We'll Be Back Soon!</h1>
        <p><?= htmlspecialchars($message) ?></p>
        
        <div class="admin-login">
            <h3>Admin Access</h3>
            <?php if (isset($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="admin_login" value="1">
                <input type="email" name="email" placeholder="Admin Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login as Admin</button>
            </form>
        </div>
        
        <p style="font-size: 0.9rem; color: #63666A; opacity: 0.7; margin-top: 2rem;">
            &copy; <?= date('Y') ?> Yucca Club. All Rights Reserved.
        </p>
    </div>
</body>
</html>

