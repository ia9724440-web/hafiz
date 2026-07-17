<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$error_message = "";
$success_message = "";

// 2. Core Processing Actions (Profile Pic and Password Updates)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACTION A: Update Photograph
    if (isset($_POST['btn_upload_photo'])) {
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp  = $_FILES['student_photo']['tmp_name'];
            $file_name = $_FILES['student_photo']['name'];
            $file_size = $_FILES['student_photo']['size'];
            $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_ext, $allowed_extensions)) {
                $error_message = "Invalid format! Only JPG, JPEG, and PNG are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) { 
                $error_message = "File is too large! Maximum allowed size is 2MB.";
            } else {
                if (!is_dir('uploads/photos')) {
                    mkdir('uploads/photos', 0755, true);
                }
                
                $new_file_name = "photo_" . $_SESSION['user_id'] . "_" . time() . "." . $file_ext;
                $upload_path = "uploads/photos/" . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    $stmt = $pdo->prepare("UPDATE students SET profile_pic = :photo WHERE id = :id");
                    $stmt->execute(['photo' => $upload_path, 'id' => $_SESSION['user_id']]);
                    $success_message = "Photograph successfully updated!";
                } else {
                    $error_message = "Failed to upload image file securely.";
                }
            }
        } else {
            $error_message = "Please select a valid image file to upload.";
        }
    } 
    
    // ACTION B: Change Password
    if (isset($_POST['btn_change_password'])) {
        $old_pass = trim($_POST['txtold_password']);
        $new_pass = trim($_POST['txtnew_password']);
        $conf_pass = trim($_POST['txtconfirm_password']);
        
        if (!empty($old_pass) && !empty($new_pass) && !empty($conf_pass)) {
            $stmt = $pdo->prepare("SELECT password FROM students WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $_SESSION['user_id']]);
            $current_user = $stmt->fetch();
            
            if ($current_user && password_verify($old_pass, $current_user['password'])) {
                if ($new_pass !== $conf_pass) {
                    $error_message = "Your new password verification entry did not match.";
                } elseif (strlen($new_pass) < 6) {
                    $error_message = "Your new security string must be 6 characters or longer.";
                } else {
                    $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
                    $update_stmt = $pdo->prepare("UPDATE students SET password = :pass WHERE id = :id");
                    $update_stmt->execute(['pass' => $hashed_pass, 'id' => $_SESSION['user_id']]);
                    $success_message = "Security access credential successfully modified!";
                }
            } else {
                $error_message = "Current security password entered is incorrect.";
            }
        } else {
            $error_message = "Please enter all necessary password fields.";
        }
    }
}

// 3. Gather Student Profile Metadata & Status Log Elements
try {
    $stmt = $pdo->prepare("SELECT full_name, profile_pic, status, fee_status, fee_paid, fee_balance, clearance_bursar, clearance_library, clearance_dept, clearance_sports FROM students WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $student = $stmt->fetch();

    if (!$student || (int)$student['status'] !== 1) {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // 🌟 LINKED MODULE: Fetch recent payment transactions for this user
    $txn_stmt = $pdo->prepare("SELECT reference, amount, payment_method, transaction_date FROM fee_transactions WHERE student_id = :id ORDER BY transaction_date DESC");
    $txn_stmt->execute(['id' => $_SESSION['user_id']]);
    $transactions = $txn_stmt->fetchAll();

} catch (\PDOException $e) {
    die("An unexpected error occurred loading your layout profile components.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Console | Online Student Clearance System</title>
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon.png">

    <style type="text/css">
        body { 
            background-color: #f6f8fa; 
            font-family: "Inter", "Segoe UI", "Helvetica Neue", Arial, sans-serif; 
            color: #333F48;
        }
        .navbar-custom { 
            background: linear-gradient(135deg, #7A1C28 0%, #56101A 100%); 
            color: #ffffff; 
            padding: 18px 25px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .navbar-custom h3 { 
            margin: 0; font-weight: 600; font-size: 20px; letter-spacing: -0.3px; 
        }
        .card-panel { 
            background: #ffffff; padding: 28px; border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(140, 150, 170, 0.06); margin-bottom: 30px; 
            border: 1px solid rgba(230, 235, 242, 0.8);
        }
        .card-panel h4 {
            font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #56101A; margin-bottom: 20px;
        }
        .profile-avatar { 
            width: 130px; height: 130px; object-fit: cover; border-radius: 50%; border: 4px solid #e9ecef; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background: #fff; 
        }
        .status-badge { 
            font-weight: 700; padding: 6px 14px; border-radius: 20px; font-size: 11px; letter-spacing: 0.5px; display: inline-block;
        }
        .badge-cleared { background-color: #e6f7ed; color: #107c41; border: 1px solid #c3f2d2; }
        .badge-pending { background-color: #fff8e6; color: #a36100; border: 1px solid #ffe8b3; }
        .form-control { border-radius: 6px; border: 1px solid #ced4da; padding: 10px 12px; height: auto; }
        .form-control:focus { border-color: #7A1C28; box-shadow: 0 0 0 3px rgba(122, 28, 40, 0.1); }
        .btn-custom-print { background-color: #7A1C28; color: #fff; border: none; font-weight: 600; padding: 10px 20px; border-radius: 6px; }
        .btn-custom-print:hover { background-color: #56101A; color: #fff; }
        .ledger-box { padding: 15px; border-radius: 8px; background: #fafbfc; border: 1px solid #edf0f2; }
        .table-modern th {
            background-color: #f8f9fa; color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #edf0f2 !important;
        }
        .table-modern td { vertical-align: middle !important; padding: 14px 8px !important; border-top: 1px solid #f1f3f5 !important; }
        .footer { background: #ffffff; border-top: 1px solid #edf0f2; padding: 20px; text-align: center; color: #8c98a5; margin-top: 50px; font-size: 13px; }
        
        @media print {
            body { background: #fff; color: #000; }
            .navbar-custom, .no-print, .btn, .alert, form, hr { display: none !important; }
            .card-panel { box-shadow: none; border: none; padding: 0; margin-bottom: 10px; }
            .print-header { display: block !important; text-align: center; margin-bottom: 30px; }
            .print-header h2 { color: #7A1C28; margin-bottom: 5px; font-weight: 700; }
            .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .table th, .table td { border: 1px solid #ddd !important; padding: 10px !important; text-align: left; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

    <div class="navbar-custom">
        <div class="container-fluid">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3><i class="fa fa-graduation-cap"></i> Online Student Clearance System</h3>
                </div>
                <div class="no-print" style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-weight: 500; color: rgba(255,255,255,0.95);">
                        <i class="fa fa-user-circle"></i> Hello, <?php echo htmlspecialchars(isset($student['full_name']) ? $student['full_name'] : 'Student'); ?>
                    </span>
                    <a href="logout.php" class="btn btn-xs btn-default" style="color: #7A1C28; font-weight: 700; padding: 6px 14px; background-color: #ffffff; border: none; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <i class="fa fa-sign-out"></i> Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="margin-top: 35px;">
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger no-print" style="border-radius:8px;"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success no-print" style="border-radius:8px;"><i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="print-header">
            <h2>UNIVERSITY OF TECHNOLOGY AND APPLIED SCIENCES</h2>
            <h4>Official Student Clearance Status Report Certificate</h4>
            <hr style="border: 1px solid #333;">
        </div>

        <div class="row">
            <div class="col-md-4 text-center">
                <div class="card-panel">
                    <?php 
                        $photo_src = (!empty($student['profile_pic']) && file_exists($student['profile_pic'])) ? $student['profile_pic'] : 'images/default-avatar.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($photo_src); ?>" alt="Student Photo" class="profile-avatar">
                    <h3 style="margin-top:18px; font-weight:700; font-size:18px; color:#2b303a;"><?php echo htmlspecialchars($student['full_name']); ?></h3>
                    <p class="text-muted" style="font-size:13px;">Matric No: <strong style="color:#56101A;"><?php echo htmlspecialchars(isset($_SESSION['matric_no']) ? $_SESSION['matric_no'] : 'N/A'); ?></strong></p>
                    
                    <div class="no-print" style="margin-top:22px;">
                        <button class="btn btn-block btn-custom-print" onclick="window.print();"><i class="fa fa-print"></i> Print Clearance Sheet</button>
                    </div>
                </div>

                <div class="card-panel text-left no-print">
                    <h4><i class="fa fa-camera" style="color:#7A1C28;"></i> Change Photograph</h4>
                    <form method="POST" enctype="multipart/form-data" action="">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size:11px; font-weight:600; color:#6c757d; text-transform:uppercase;">Upload Profile Picture</label>
                            <input type="file" name="student_photo" class="form-control" style="font-size:12px;" required>
                        </div>
                        <button type="submit" name="btn_upload_photo" class="btn btn-sm btn-info btn-block" style="font-weight:600; border-radius:6px;">Update Image</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card-panel">
                    <h4><i class="fa fa-money" style="color:#7A1C28;"></i> Financial Accounts Ledger</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2;">
                    <?php 
                        $fee_balance = isset($student['fee_balance']) ? $student['fee_balance'] : 0;
                        $fee_paid = isset($student['fee_paid']) ? $student['fee_paid'] : 0;
                        $total_required = $fee_balance + $fee_paid;
                    ?>
                    <div class="row text-center">
                        <div class="col-xs-4">
                            <div class="ledger-box">
                                <h5 style="margin-top:0; font-size:12px; color:#6c757d; font-weight:600;">Total Required</h5>
                                <strong style="font-size: 15px; color:#2b303a;">GHS <?php echo number_format($total_required, 2); ?></strong>
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <div class="ledger-box" style="background:#f4faf6; border-color:#e1f2e8;">
                                <h5 class="text-success" style="margin-top:0; font-size:12px; font-weight:600;">Amount Paid</h5>
                                <strong class="text-success" style="font-size: 15px;">GHS <?php echo number_format($fee_paid, 2); ?></strong>
                            </div>
                        </div>
                        <div class="col-xs-4">
                            <div class="ledger-box" style="background:#fff5f5; border-color:#fde7e7;">
                                <h5 class="text-danger" style="margin-top:0; font-size:12px; font-weight:600;">Balance Due</h5>
                                <strong class="text-danger" style="font-size: 15px;">GHS <?php echo number_format($fee_balance, 2); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 12px 15px; background: #f8f9fa; border-radius: 8px; display:flex; justify-content:space-between; align-items:center;">
                        <span style="font-weight:500; color:#495057;">Current Standing:</span>
                        <strong>
                            <?php if ((int)$student['fee_status'] === 1 || $fee_balance <= 0): ?>
                                <span class="status-badge badge-cleared"><i class="fa fa-check-circle"></i> Fees Cleared Completely</span>
                            <?php else: ?>
                                <span class="status-badge badge-pending"><i class="fa fa-times-circle"></i> Arrears Pending Payment</span>
                            <?php endif; ?>
                        </strong>
                    </div>
                    
                    <?php if ($fee_balance > 0): ?>
                        <div class="text-right no-print" style="margin-top: 20px;">
                            <a href="pay_fee.php" class="btn btn-success btn-sm" style="font-weight:600; border-radius:6px; padding:8px 16px;"><i class="fa fa-credit-card"></i> Pay Outstanding Fees Online</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-panel">
                    <h4><i class="fa fa-history" style="color:#7A1C28;"></i> Recent Transaction History</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2;">
                    <?php if (empty($transactions)): ?>
                        <p class="text-muted" style="font-size:13px; margin:0; padding:10px 0;">No prior payments discovered on this ledger account profile.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-modern" style="margin-bottom:0;">
                                <thead>
                                    <tr>
                                        <th>Reference ID</th>
                                        <th>Method</th>
                                        <th>Date Provided</th>
                                        <th class="text-right">Amount (GHS)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td style="font-family:monospace; font-weight:600; color:#495057;"><?php echo htmlspecialchars($txn['reference']); ?></td>
                                            <td><span class="label label-default" style="background:#e9ecef; color:#495057; font-weight:600;"><?php echo htmlspecialchars($txn['payment_method']); ?></span></td>
                                            <td style="font-size:12px; color:#6c757d;"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($txn['transaction_date']))); ?></td>
                                            <td class="text-right" style="font-weight:700; color:#107c41;">+ <?php echo number_format($txn['amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-panel">
                    <h4><i class="fa fa-building" style="color:#7A1C28;"></i> Clearance Validation Standing</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2;">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Target Bureau / Desk</th>
                                <th class="text-right">Current Validation Standing</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="font-weight:500;"><i class="fa fa-university" style="color:#adb5bd; margin-right:8px;"></i> Bursar Accounts Registry</td>
                                <td class="text-right"><?php echo ($student['clearance_bursar'] == 1) ? '<span class="status-badge badge-cleared">CLEARED</span>' : '<span class="status-badge badge-pending">PENDING</span>'; ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight:500;"><i class="fa fa-book" style="color:#adb5bd; margin-right:8px;"></i> University Library Desk</td>
                                <td class="text-right"><?php echo ($student['clearance_library'] == 1) ? '<span class="status-badge badge-cleared">CLEARED</span>' : '<span class="status-badge badge-pending">PENDING</span>'; ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight:500;"><i class="fa fa-folder-open" style="color:#adb5bd; margin-right:8px;"></i> Academic Faculty Department Log</td>
                                <td class="text-right"><?php echo ($student['clearance_dept'] == 1) ? '<span class="status-badge badge-cleared">CLEARED</span>' : '<span class="status-badge badge-pending">PENDING</span>'; ?></td>
                            </tr>
                            <tr>
                                <td style="font-weight:500;"><i class="fa fa-trophy" style="color:#adb5bd; margin-right:8px;"></i> Sports Directorate Inventory</td>
                                <td class="text-right"><?php echo ($student['clearance_sports'] == 1) ? '<span class="status-badge badge-cleared">CLEARED</span>' : '<span class="status-badge badge-pending">PENDING</span>'; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-panel no-print">
                    <h4><i class="fa fa-lock" style="color:#7A1C28;"></i> Security Access Credentials</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2;">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <input type="password" name="txtold_password" class="form-control" placeholder="Current Password" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <input type="password" name="txtnew_password" class="form-control" placeholder="New Password" required>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <input type="password" name="txtconfirm_password" class="form-control" placeholder="Confirm Password" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-right" style="margin-top: 5px;">
                            <button type="submit" name="btn_change_password" class="btn btn-warning btn-sm" style="font-weight:600; border-radius:6px; padding:7px 15px; background-color:#e67e22; border:none;">Modify Access Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2026. Online Clearance System | UTAS. All Rights Reserved.</p>
    </div>

    <script src="js/jquery-2.1.1.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>