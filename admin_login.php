<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['txtusername']);
    $password = trim($_POST['txtpassword']);
    
    if (!empty($username) && !empty($password)) {
        try {
            // Locate administrative target record match
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username LIMIT 1");
            $stmt->execute(['username' => $username]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                // FIXED: Direct plain-text comparison override
                if ($password === $admin['password']) {
                    
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    
                    $u_stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = :id");
                    $u_stmt->execute(['id' => $admin['id']]);
                    
                    session_write_close();
                    header("Location: admin_dashboard.php");
                    exit;
                } else {
                    $error_message = "Invalid administrative access credentials.";
                }
            } else {
                $error_message = "Administrative account record profile not located.";
            }
        } catch (\PDOException $e) {
            $error_message = "System Processing Error: " . $e->getMessage();
        }
    } else {
        $error_message = "Please complete all mandatory login inputs.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Online Student Clearance System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; font-family: "Segoe UI", sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .login-card { background: #ffffff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); width: 100%; max-width: 420px; border: 1px solid #1e293b; }
        .brand-header { text-align: center; margin-bottom: 30px; }
        .brand-header i { font-size: 42px; color: #0f766e; margin-bottom: 10px; }
        .brand-header h2 { font-size: 22px; font-weight: 600; color: #1e293b; margin: 0; }
        .brand-header p { color: #64748b; font-size: 13px; margin-top: 5px; }
        .form-control { border-radius: 6px; border: 1px solid #cbd5e1; padding: 12px; height: auto; box-shadow: none; transition: all 0.2s; }
        .form-control:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,0.1); }
        .btn-admin { background-color: #0f766e; border-color: #0f766e; color: #fff; font-weight: 500; border-radius: 6px; padding: 12px; width: 100%; font-size: 14px; transition: all 0.2s; border-style: solid; }
        .btn-admin:hover { background-color: #0d635c; border-color: #0d635c; color: #fff; }
        .alert { border-radius: 8px; font-size: 13px; padding: 12px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-header">
            <i class="fa fa-sliders"></i>
            <h2>Administrative Console</h2>
            <p>Online Student Clearance Central Portal</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label style="font-size: 12px; font-weight: 500; color: #475569;">Admin Username</label>
                <input type="text" name="txtusername" class="form-control" placeholder="admin" required autofocus autocomplete="off">
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="font-size: 12px; font-weight: 500; color: #475569;">Security Passkey</label>
                <input type="password" name="txtpassword" class="form-control" placeholder="••••••••••••" required>
            </div>
            <button type="submit" class="btn btn-admin">Authenticate & Secure Log In</button>
        </form>
    </div>

</body>
</html>