<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Authentication Guard (Example checks session role setting)
if (!isset($_SESSION['admin_id'])) {
    // If you use a different session variable for admins, replace 'admin_id' here
    // header("Location: admin_login.php");
    // exit;
}

require_once 'db_connect.php';

try {
    // 2. Fetch Summary Statistics Metrics
    $stats_stmt = $pdo->query("SELECT 
        COUNT(id) as total_students,
        SUM(fee_paid) as total_collected,
        SUM(fee_balance) as total_outstanding,
        SUM(CASE WHEN clearance_bursar = 1 THEN 1 ELSE 0 END) as total_cleared
        FROM students");
    $stats = $stats_stmt->fetch();

    // 3. Fetch Master List of Students and Their Financial/Clearance Status
    $students_stmt = $pdo->query("SELECT id, full_name, fee_paid, fee_balance, clearance_bursar FROM students ORDER BY full_name ASC");
    $all_students = $students_stmt->fetchAll();

    // 4. Fetch the 5 Most Recent Real-Time Paystack Transactions Across the Campus
    $log_stmt = $pdo->query("SELECT t.reference, t.amount, t.transaction_date, s.full_name 
                             FROM fee_transactions t 
                             JOIN students s ON t.student_id = s.id 
                             ORDER BY t.transaction_date DESC LIMIT 5");
    $recent_logs = $log_stmt->fetchAll();

} catch (\PDOException $e) {
    die("Database aggregation failure: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bursar Admin Management Console | UTAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style type="text/css">
        body { background-color: #f6f8fa; font-family: "Segoe UI", Arial, sans-serif; color: #333F48; }
        .navbar-admin { background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%); color: #ffffff; padding: 15px 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .navbar-admin h3 { margin: 0; font-weight: 600; font-size: 20px; }
        .card-panel { background: #ffffff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(140, 150, 170, 0.06); border: 1px solid rgba(230, 235, 242, 0.8); margin-bottom: 25px; }
        .card-panel h4 { font-size: 15px; font-weight: 700; text-transform: uppercase; color: #2c3e50; margin-bottom: 20px; letter-spacing: 0.5px; }
        .metric-card { padding: 20px; border-radius: 8px; color: white; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .metric-card:hover { transform: translateY(-2px); } 
        .bg-primary-dark { background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%); }
        .bg-success-dark { background: linear-gradient(135deg, #27ae60 0%, #219653 100%); }
        .bg-danger-dark { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        .bg-info-dark { background: linear-gradient(135deg, #2980b9 0%, #2471a3 100%); }
        .metric-card h2 { margin: 5px 0 0 0; font-weight: 700; font-size: 28px; }
        .status-badge { font-weight: 700; padding: 6px 14px; border-radius: 20px; font-size: 11px; display: inline-block; letter-spacing: 0.3px; }
        .badge-success { background-color: #e6f7ed; color: #107c41; border: 1px solid #c3f2d2; }
        .badge-danger { background-color: #fff5f5; color: #a83232; border: 1px solid #fde7e7; }
        .table-admin th { background-color: #f8f9fa; color: #6c757d; font-weight: 600; text-transform: uppercase; font-size: 11px; border-bottom: 2px solid #edf0f2 !important; padding: 12px 16px; }
        .table-admin td { vertical-align: middle !important; padding: 14px 16px !important; border-top: 1px solid #f1f3f5 !important; }
        
        /* Cleaned up custom button class to remove conflicting Bootstrap color classes */
        .btn-logout {
            background-color: #2ecc71; 
            color: #ffffff;
            font-weight: 600;
            border-radius: 6px;
            padding: 8px 16px;
            transition: background 0.2s ease;
            box-shadow: 0 2px 8px rgba(46, 204, 113, 0.3);
        }
        .btn-logout:hover {
            background-color: #27ae60;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <div class="navbar-admin">
        <div class="container-fluid px-0">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3><i class="fa-solid fa-sliders me-2"></i> Bursar Administrative Control Center</h3>
                </div>
                <!-- Right-aligned action layout container -->
                <div class="d-flex align-items-center gap-4">
                    <span class="d-none d-md-inline" style="font-weight: 500; font-size: 14px; opacity: 0.9;"><i class="fa-solid fa-user-shield me-1"></i> Authenticated Admin Console</span>
                    <a href="admin_logout.php" class="btn btn-sm btn-logout text-decoration-none">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid" style="margin-top: 30px; padding: 0 30px;">                                                                                                                                                                  
        <div class="row">
            <div class="col-md-3">
                <div class="metric-card bg-primary-dark">
                    <span style="font-size:12px; font-weight:600; text-transform:uppercase; opacity:0.8; display:block;">Total Enrolled Students</span>
                    <h2><?php echo intval($stats['total_students']); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card bg-success-dark">
                    <span style="font-size:12px; font-weight:600; text-transform:uppercase; opacity:0.8; display:block;">Total Fees Collected</span>
                    <h2>GHS <?php echo number_format(isset($stats['total_collected']) ? $stats['total_collected'] : 0, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card bg-danger-dark">
                    <span style="font-size:12px; font-weight:600; text-transform:uppercase; opacity:0.8; display:block;">Total Outstanding Debt</span>
                    <h2>GHS <?php echo number_format(isset($stats['total_outstanding']) ? $stats['total_outstanding'] : 0, 2); ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card bg-info-dark">
                    <span style="font-size:12px; font-weight:600; text-transform:uppercase; opacity:0.8; display:block;">Bursar Accounts Cleared</span>
                    <h2><?php echo intval($stats['total_cleared']); ?> / <?php echo intval($stats['total_students']); ?></h2>
                </div>
            </div>
        </div>                                                                                                                                                                                                                                       
        
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-8">
                <div class="card-panel">
                    <h4>Student Accounts Balance Ledger</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2; margin-bottom: 20px;">
                    <div class="table-responsive">
                        <table class="table table-admin table-hover align-middle" style="margin-bottom:0;">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Fees Paid</th>
                                    <th>Remaining Debt</th>
                                    <th class="text-center">Bursar Validation Clearance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_students as $row): ?>
                                    <tr>
                                        <td style="font-weight: 600; color:#2c3e50;"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td style="color:#27ae60; font-weight:600;">GHS <?php echo number_format($row['fee_paid'], 2); ?></td>
                                        <td style="color:<?php echo ($row['fee_balance'] > 0) ? '#c0392b' : '#27ae60'; ?>; font-weight:600;">
                                            GHS <?php echo number_format($row['fee_balance'], 2); ?>
                                        </td>                                                                                                                                          <td class="text-center">
                                            <?php if ((int)$row['clearance_bursar'] === 1): ?>
                                                <span class="status-badge badge-success"><i class="fa-solid fa-circle-check me-1"></i> CLEARED</span>
                                            <?php else: ?>
                                                <span class="status-badge badge-danger"><i class="fa-regular fa-clock me-1"></i> PENDING PAYMENT</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>                                                                                                                                                                
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-panel">
                    <h4>Recent Paystack Logs</h4>
                    <hr style="margin-top:10px; border-top:1px solid #edf0f2; margin-bottom: 15px;">
                    <div class="list-group list-group-flush" style="font-size: 13px;">
                        <?php if (empty($recent_logs)): ?>
                            <div class="text-muted py-2">No recent transaction records tracked.</div>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <div class="py-3" style="border-bottom: 1px dashed #edf0f2;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong style="color: #2c3e50; font-size: 13.5px;"><?php echo htmlspecialchars($log['full_name']); ?></strong>
                                        <span class="text-muted" style="font-size: 11px;"><?php echo date("M d, H:i", strtotime($log['transaction_date'])); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span class="text-success fw-bold" style="font-size: 14px;">+ GHS <?php echo number_format($log['amount'], 2); ?></span>
                                        <span class="text-muted font-monospace" style="font-size: 11px;">Ref: <?php echo htmlspecialchars($log['reference']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>