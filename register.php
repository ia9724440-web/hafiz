<?php
// 1. Initialize sessions at the absolute top of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Redirect the user automatically if they are already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Pull in the shared database connection cleanly
require_once 'db_connect.php';

$error_message = "";
$success_message = "";

// 3. Process Registration Form Submissions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnregister'])) {
    $matric_no        = trim($_POST['txtmatric_no']);
    $full_name        = trim($_POST['txtfull_name']);
    $password         = trim($_POST['txtpassword']);
    $confirm_password = trim($_POST['txtconfirm_password']);

    // Server-side fallback validation
    if (!empty($matric_no) && !empty($full_name) && !empty($password) && !empty($confirm_password)) {
        
        if ($password !== $confirm_password) {
            $error_message = "Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $error_message = "Password must be at least 6 characters long.";
        } else {
            try {
                // Check if Matric Number already exists within the system
                $check_stmt = $pdo->prepare("SELECT id FROM students WHERE matric_no = :matric_no LIMIT 1");
                $check_stmt->execute(['matric_no' => $matric_no]);
                
                if ($check_stmt->fetch()) {
                    $error_message = "This Matric Number is already registered.";
                } else {
                    // Generate a highly secure cryptographic password hash
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Write user credentials safely into the database
                    $insert_stmt = $pdo->prepare("INSERT INTO students (matric_no, full_name, password, status) VALUES (:matric_no, :full_name, :password, 1)");
                    
                    if ($insert_stmt->execute([
                        'matric_no' => $matric_no,
                        'full_name' => $full_name,
                        'password'  => $hashed_password
                    ])) {
                        $success_message = "Registration successful! You can now log in.";
                        // Flush individual variables to completely empty inputs on screen after success
                        $matric_no = $full_name = "";
                    } else {
                        $error_message = "Something went wrong. Please try again.";
                    }
                }
            } catch (\PDOException $e) {
                $error_message = "Database error encountered. Please contact system admin.";
            }
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Online Student Clearance System</title>
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">

    <style type="text/css">
        body.gray-bg {
            background-color: #f3f3f4;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }
        .login-wrapper { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .middle-box { background: #ffffff; padding: 35px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); max-width: 450px; width: 100%; }
        .system-title { color: #d9534f; font-weight: 700; font-size: 22px; margin-bottom: 5px; letter-spacing: -0.5px; }
        .system-subtitle { color: #676a6c; font-size: 14px; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; position: relative; }
        .form-control { height: 46px; border-radius: 4px; border: 1px solid #e5e6e7; padding: 6px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out; }
        .form-control:focus { border-color: #1ab394; box-shadow: none; }
        .btn-custom-register { background-color: #1ab394; border-color: #1ab394; color: #ffffff !important; font-weight: 600; font-size: 15px; height: 46px; border-radius: 4px; width: 100%; margin-top: 10px; margin-bottom: 15px; transition: all 0.2s ease; }
        .btn-custom-register:hover, .btn-custom-register:focus { background-color: #18a689; border-color: #18a689; transform: translateY(-1px); }
        .login-link { color: #337ab7; font-size: 13px; text-decoration: none; }
        .login-link:hover { color: #23527c; text-decoration: underline; }
        .footer { background: #ffffff; border-top: 1px solid #e7eaec; padding: 15px 20px; text-align: center; font-size: 13px; color: #676a6c; width: 100%; }
        .footer p { margin: 0; font-weight: 500; }
        .alert-custom { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 13px; font-weight: 500; text-align: left; }
        .alert-danger-custom { background-color: #f2dede; border-color: #ebccd1; color: #a94442; }
        .alert-success-custom { background-color: #dff0d8; border-color: #d6e9c6; color: #3c763d; }
    </style>
</head>
<body class="gray-bg">
    <div class="login-wrapper">
        <div class="middle-box text-center loginscreen animated fadeInDown">
            <h5 class="system-title">Create Account</h5>
            <p class="system-subtitle">Online Student Clearance System</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-custom alert-danger-custom">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-custom alert-success-custom">
                    <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="m-t" role="form" method="POST" action="">
                <div class="form-group">
                    <input type="text" name="txtfull_name" class="form-control" placeholder="Full Name" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="text" name="txtmatric_no" class="form-control" placeholder="Matric No" value="<?php echo isset($matric_no) ? htmlspecialchars($matric_no) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="txtpassword" class="form-control" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="txtconfirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>
                
                <button type="submit" name="btnregister" class="btn btn-custom-register">Register</button>
                
                <div>
                    <span style="font-size: 13px; color: #676a6c;">Already have an account?</span> 
                    <a href="login.php" class="login-link">Sign In</a>
                </div>
            </form>
        </div>
    </div>
    <div class="footer">
        <p>&copy; 2026. ABDUL, IDDRISU HAFIZ | Online Clearance System | UTAS. All Rights Reserved.</p>
    </div>
    <script src="js/jquery-2.1.1.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>