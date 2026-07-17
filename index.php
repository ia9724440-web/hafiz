<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Automatic Session Routing Guard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: dashboard.php");
        exit;
    }
}

require_once 'db_connect.php';

$error_message = "";

// 2. Authentication Processing Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btn_login'])) {
    $username = trim($_POST['txtusername']); 
    $password = trim($_POST['txtpassword']);

    if (!empty($username) && !empty($password)) {
        try {
            // 🔍 STEP A: Search the Student Table using ONLY matric_no
            $student_stmt = $pdo->prepare("SELECT id, password, 'student' AS account_role, status FROM students WHERE matric_no = :user LIMIT 1");
            $student_stmt->execute(['user' => $username]);
            $user = $student_stmt->fetch();

            // 🔍 STEP B: Fallback to Admin Table using ONLY username if no student matches
            if (!$user) {
                $admin_stmt = $pdo->prepare("SELECT id, password, 'admin' AS account_role, 1 AS status FROM admins WHERE username = :user LIMIT 1");
                $admin_stmt->execute(['user' => $username]);
                $user = $admin_stmt->fetch();
            }

            // 🔐 STEP C: Password Verification & Route Distribution
            if ($user && password_verify($password, $user['password'])) {
                if ((int)$user['status'] !== 1) {
                    $error_message = "This account profile has been suspended.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['account_role'];
                    
                    if ($user['account_role'] === 'admin') {
                        $_SESSION['admin_id'] = $user['id']; 
                        header("Location: admin_dashboard.php");
                    } else {
                        $_SESSION['matric_no'] = $username; 
                        header("Location: dashboard.php");
                    }
                    exit;
                }
            } else {
                $error_message = "Invalid access credentials supplied.";
            }
        } catch (\PDOException $e) {
            $error_message = "Database Processing Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all authorization inputs.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gateway Portal | Online Clearance System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <style type="text/css">
        :root {
            --primary-gradient: linear-gradient(135deg, #7A1C28 0%, #420a11 100%);
            --accent-gold: #f39c12;
            --dark-slate: #2C3E50;
            --light-bg: #f8fafc;
        }

        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            background-color: var(--light-bg);
        }

        .split-container {
            display: flex;
            height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        /* Left Side: Branding Panel */
        .brand-panel {
            flex: 1.2;
            background: var(--primary-gradient);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 60px 80px;
            position: relative;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" stroke="rgba(255,255,255,0.03)" stroke-width="1" fill="none"/></svg>');
            opacity: 0.4;
            pointer-events: none;
        }

        /* 🌟 SCALED UP INSTITUTIONAL LOGO STYLING */
        .brand-logo-image {
            max-width: 240px; /* Increased from 120px to make it prominent */
            width: 100%;
            height: auto;
            margin-bottom: 30px;
            filter: drop-shadow(0px 6px 12px rgba(0, 0, 0, 0.25));
        }

        .brand-panel h1 {
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0 0 15px 0;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .brand-panel p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.75);
            max-width: 460px;
            line-height: 1.6;
            margin: 0;
        }

        /* Right Side: Login Panel Desk */
        .login-panel {
            flex: 1;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            box-shadow: -10px 0 30px rgba(0,0,0,0.02);
            z-index: 2;
        }

        .login-wrapper {
            width: 100%;
            max-width: 360px;
        }

        .login-wrapper h3 {
            color: var(--dark-slate);
            font-weight: 700;
            font-size: 24px;
            margin: 0 0 8px 0;
        }

        .login-wrapper .subtitle {
            color: #8a99a8;
            font-size: 14px;
            margin-bottom: 30px;
        }

        /* Form Styling Elements */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #7f8c8d;
            font-weight: 700;
            margin-bottom: 8px;
            display: block;
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #bdc3c7;
            font-size: 14px;
            transition: color 0.2s;
        }

        .form-control {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 14px 14px 14px 40px;
            height: auto;
            font-size: 14px;
            color: var(--dark-slate);
            background-color: #f8fafc;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .form-control:focus {
            border-color: #7A1C28;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(122, 28, 40, 0.08);
        }

        .form-control:focus + i {
            color: #7A1C28;
        }

        .btn-portal {
            background: var(--primary-gradient);
            color: white;
            font-weight: 600;
            padding: 14px;
            border-radius: 8px;
            border: none;
            font-size: 15px;
            letter-spacing: 0.3px;
            margin-top: 15px;
            transition: transform 0.1s ease, opacity 0.2s;
            box-shadow: 0 4px 12px rgba(122, 28, 40, 0.2);
        }

        .btn-portal:hover {
            opacity: 0.95;
            color: white;
        }

        .btn-portal:active {
            transform: scale(0.98);
        }

        .alert-danger {
            background-color: #fff5f5;
            color: #c0392b;
            border: 1px solid #fde7e7;
            border-radius: 8px;
            font-size: 13px;
            padding: 12px 15px;
            margin-bottom: 25px;
        }

        /* Responsive Breakpoints */
        @media (max-width: 991px) {
            .brand-panel { display: none; }
            .login-panel { flex: 1; background-color: var(--light-bg); }
            .login-wrapper {
                background: #ffffff;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.03);
                border: 1px solid #e2e8f0;
            }
        }
    </style>
</head>
<body>

    <div class="split-container">
        
        <!-- Left Side Layout Panel -->
        <div class="brand-panel">
            <!-- Render scaled 'utas.png' image right on top -->
            <img src="utas.png" alt="UTAS Logo" class="brand-logo-image">
            
            <h1>University of Technology<br>& Applied Sciences</h1>
            <p>Welcome to the unified digital clearance portal. Authenticate your account to settle outstanding fees or manage graduate audit workflows.</p>
        </div>

        <!-- Right Side Layout Panel -->
        <div class="login-panel">
            <div class="login-wrapper">
                <h3>Account Gateway</h3>
                <div class="subtitle">Secure entry point for students and admin panels.</div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fa fa-exclamation-circle" style="margin-right: 5px;"></i> 
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>User Identifier</label>
                        <div class="input-icon-wrapper">
                            <input type="text" name="txtusername" class="form-control" placeholder="Matric No or Username" required autocomplete="off">
                            <i class="fa fa-user"></i>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label>Account Password</label>
                        <div class="input-icon-wrapper">
                            <input type="password" name="txtpassword" class="form-control" placeholder="••••••••" required>
                            <i class="fa fa-lock"></i>
                        </div>
                    </div>
                    
                    <button type="submit" name="btn_login" class="btn btn-block btn-portal">
                        Sign In securely <i class="fa fa-arrow-right" style="margin-left:5px; font-size:12px;"></i>
                    </button>
                </form>
            </div>
        </div>

    </div>

</body>
</html>