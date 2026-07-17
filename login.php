<?php
// 1. Initialize sessions explicitly at the absolute top of the file
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Redirect the student automatically if they are already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Pull in the shared database connection
require_once 'db_connect.php';

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['btnlogin'])) {
    $matric_no = trim($_POST['txtmatric_no']);
    $password  = trim($_POST['txtpassword']);

    if (!empty($matric_no) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, matric_no, password, status FROM students WHERE matric_no = :matric_no LIMIT 1");
        $stmt->execute(['matric_no' => $matric_no]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['status'] === 1) {
                // 3. Prevent Session Fixation attacks by regenerating the ID on login
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['matric_no'] = $user['matric_no'];
                
                header("Location: dashboard.php");
                exit;
            } else {
                $error_message = "Your account has been deactivated. Please contact administration.";
            }
        } else {
            $error_message = "Invalid Matric Number or Password.";
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
    <title>Login | Online Student Clearance System</title>
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">
    <link rel="stylesheet" href="popup_style.css">

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
        .middle-box { background: #ffffff; padding: 35px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); max-width: 400px; width: 100%; }
        .system-title { color: #d9534f; font-weight: 700; font-size: 22px; margin-bottom: 20px; letter-spacing: -0.5px; }
        .logo-container { margin-bottom: 25px; }
        .logo-container img { max-width: 100%; height: auto; object-fit: contain; }
        .form-group { margin-bottom: 18px; position: relative; }
        .form-control { height: 46px; border-radius: 4px; border: 1px solid #e5e6e7; padding: 6px 12px; font-size: 14px; transition: border-color 0.15s ease-in-out; }
        .form-control:focus { border-color: #1ab394; box-shadow: none; }
        .btn-custom-login { background-color: #1ab394; border-color: #1ab394; color: #ffffff !important; font-weight: 600; font-size: 15px; height: 46px; border-radius: 4px; width: 100%; margin-top: 10px; margin-bottom: 15px; transition: all 0.2s ease; }
        .btn-custom-login:hover, .btn-custom-login:focus { background-color: #18a689; border-color: #18a689; transform: translateY(-1px); }
        .forgot-password-link { color: #337ab7; font-size: 13px; text-decoration: none; transition: color 0.2s ease; }
        .forgot-password-link:hover { color: #23527c; text-decoration: underline; }
        .footer { background: #ffffff; border-top: 1px solid #e7eaec; padding: 15px 20px; text-align: center; font-size: 13px; color: #676a6c; width: 100%; }
        .footer p { margin: 0; font-weight: 500; }
        .alert-danger-custom { background-color: #f2dede; border-color: #ebccd1; color: #a94442; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 13px; font-weight: 500; text-align: left; }
    </style>
</head>
<body class="gray-bg">
    <div class="login-wrapper">
        <div class="middle-box text-center loginscreen animated fadeInDown">
            <h5 class="system-title">Online Student Clearance System</h5>
            <div class="logo-container">
                <a href="index.php"><img src="utas.png" alt="UTAS Online Clearance Logo"></a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger-custom">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="m-t" role="form" method="POST" action="">
                <div class="form-group">
                    <input type="text" name="txtmatric_no" class="form-control" placeholder="Matric No" value="<?php echo isset($_POST['txtmatric_no']) ? htmlspecialchars($_POST['txtmatric_no']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <input type="password" name="txtpassword" class="form-control" placeholder="Password" required>
                </div>
                <button type="submit" name="btnlogin" class="btn btn-custom-login">Login</button>
                <div>
                    <span style="font-size: 13px; color: #676a6c;">New user?</span> 
                    <a href="register.php" class="forgot-password-link">Create an account</a>
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