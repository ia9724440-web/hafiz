<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'All fields are strictly required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match. Please verify.';
    } else {
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username OR email = :email LIMIT 1");
            $check_stmt->execute([':username' => $username, ':email' => $email]);
            
            if ($check_stmt->fetch()) {
                $error_message = 'Username or Email is already registered.';
            } else {
                $insert_stmt = $pdo->prepare("INSERT INTO admins (full_name, username, email, password) VALUES (:full_name, :username, :email, :password)");
                $insert_stmt->execute([
                    ':full_name' => $full_name,
                    ':username'  => $username,
                    ':email'     => $email,
                    ':password'  => $password
                ]);

                $success_message = 'Administrator account successfully registered! You can now log in.';
                $full_name = $username = $email = '';
            }
        } catch (\PDOException $e) {
            $error_message = 'System registration failure: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Registration | UTAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style type="text/css">
        body { 
            /* Swapped to a premium executive deep blue gradient background */
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            font-family: "Segoe UI", Arial, sans-serif; 
            min-height: 100vh; 
        }
        .register-container { margin-top: 40px; margin-bottom: 40px; }
        .register-card { background: #ffffff; padding: 35px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); border: none; }
        .register-card h3 { font-size: 22px; font-weight: 700; color: #2c3e50; letter-spacing: 0.5px; }
        .form-label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #6c757d; letter-spacing: 0.3px; }
        .form-control { padding: 11px 15px; border-radius: 6px; border: 1px solid #ced4da; font-size: 14.5px; }
        .form-control:focus { border-color: #2a5298; box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.15); }
        .input-group-text { background-color: #f8f9fa; color: #6c757d; border-right: none; }
        .input-group .form-control { border-left: none; }
        .btn-register { background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%); color: #ffffff; font-weight: 600; padding: 12px; border-radius: 6px; border: none; box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2); transition: all 0.2s ease; }
        .btn-register:hover { background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); color: #ffffff; transform: translateY(-1px); }
    </style>
</head>
<body>

    <div class="container register-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                
                <!-- Adjusted branding text to crisp white colors to pop against the blue background -->
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-white" style="letter-spacing: 1px;"><i class="fa-solid fa-sliders text-info me-2"></i>UTAS</h2>
                    <p class="text-white-50 small">Administrative Portal Management Setup</p>
                </div>

                <div class="register-card">
                    <h3 class="text-center mb-4"><i class="fa-solid fa-user-plus me-2"></i>Create Admin Account</h3>
                    <hr style="border-top: 1px solid #edf0f2; margin-bottom: 25px;">

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger py-2 px-3 border-0 small d-flex align-items-center mb-3" style="border-radius: 6px;">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i>
                            <div><?php echo htmlspecialchars($error_message); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success py-2 px-3 border-0 small d-flex align-items-center mb-3" style="border-radius: 6px;">
                            <i class="fa-solid fa-circle-check me-2"></i>
                            <div><?php echo htmlspecialchars($success_message); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="admin_register.php" method="POST" autocomplete="off">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="e.g., John Doe" value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="admin@domain.com" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Choose a unique username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter secure password" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Retype password to confirm" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register w-100 mb-3">
                            <i class="fa-solid fa-circle-check me-2"></i>Register System Administrator
                        </button>

                        <div class="text-center mt-2">
                            <a href="admin_login.php" class="text-decoration-none small text-muted">
                                <i class="fa-solid fa-arrow-left me-1"></i> Return to Administration Login
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</body>
</html>