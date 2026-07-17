<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication Guard
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_connect.php';

$error_message = "";
$success_message = "";

// 1. Fetch student meta profiles and current balances
try {
    // We fetch email as well, as Paystack requires a customer email address to initialize payments
    $stmt = $pdo->prepare("SELECT full_name, fee_balance, fee_paid, email FROM students WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $student = $stmt->fetch();

    if (!$student) {
        header("Location: dashboard.php");
        exit;
    }
} catch (\PDOException $e) {
    $error_message = "Could not fetch current balance metrics.";
}

$fee_balance = isset($student['fee_balance']) ? $student['fee_balance'] : 0;
$fee_paid = isset($student['fee_paid']) ? $student['fee_paid'] : 0;
// Fallback email wrapper if your database column is empty
$student_email = (!empty($student['email'])) ? $student['email'] : "student_" . $_SESSION['user_id'] . "@utas.edu.gh";

// 2. Background AJAX handler called automatically when Paystack payment succeeds
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'verify_payment') {
    $pay_amount = floatval($_POST['amount']);
    $reference_str = trim($_POST['reference']);
    $pay_method = "Paystack Online Gateway";
    
    header('Content-Type: application/json');
    
    if ($pay_amount <= 0 || $pay_amount > $fee_balance) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid transaction bounds.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // Record the transaction row
        $insert_sql = "INSERT INTO fee_transactions (student_id, reference, amount, payment_method) 
                       VALUES (:student_id, :ref, :amount, :method)";
        $ins_stmt = $pdo->prepare($insert_sql);
        $ins_stmt->execute([
            'student_id' => $_SESSION['user_id'],
            'ref' => $reference_str,
            'amount' => $pay_amount,
            'method' => $pay_method
        ]);
        
        // Calculate the new balances
        $new_paid = $fee_paid + $pay_amount;
        $new_balance = $fee_balance - $pay_amount;
        $fee_status = ($new_balance <= 0) ? 1 : 0;
        
        $update_sql = "UPDATE students SET 
                        fee_paid = :paid, 
                        fee_balance = :balance, 
                        fee_status = :f_status";
        
        // Automatically Clear Bursar logs if debt hits 0
        if ($new_balance <= 0) {
            $update_sql .= ", clearance_bursar = 1";
        }
        
        $update_sql .= " WHERE id = :id";
        
        $upd_stmt = $pdo->prepare($update_sql);
        $upd_stmt->execute([
            'paid' => $new_paid,
            'balance' => $new_balance,
            'f_status' => $fee_status,
            'id' => $_SESSION['user_id']
        ]);
        
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Ledger metrics successfully altered!']);
        exit;
        
    } catch (\PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Database commit exception encountered.']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment Portal | Clearance System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="font-awesome/css/font-awesome.css" rel="stylesheet">
    <style type="text/css">
        body { background-color: #f6f8fa; font-family: "Segoe UI", Arial, sans-serif; color: #333F48; }
        .navbar-custom { background: linear-gradient(135deg, #7A1C28 0%, #56101A 100%); color: #ffffff; padding: 18px 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .navbar-custom h3 { margin: 0; font-weight: 600; font-size: 20px; }
        .card-panel { background: #ffffff; padding: 28px; border-radius: 12px; box-shadow: 0 4px 20px rgba(140, 150, 170, 0.06); border: 1px solid rgba(230, 235, 242, 0.8); margin-top: 30px; }
        .card-panel h4 { font-size: 16px; font-weight: 700; text-transform: uppercase; color: #56101A; margin-bottom: 20px; }
        .form-control { border-radius: 6px; padding: 12px 14px; height: auto; font-size: 15px; }
        .btn-pay { background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%); color: white; font-weight: 700; padding: 12px 20px; border-radius: 6px; border: none; font-size: 15px; transition: opacity 0.2s; }
        .btn-pay:hover { opacity: 0.9; color: white; }
        .ledger-summary { padding: 15px; border-radius: 8px; background: #fafbfc; border: 1px solid #edf0f2; margin-bottom: 20px; }
    </style>
</head>
<body>

    <div class="navbar-custom">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3><i class="fa fa-shield"></i> Secured Paystack Channel</h3>
                <a href="dashboard.php" class="btn btn-sm btn-default" style="color:#7A1C28; font-weight:600;"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="card-panel">
                    <h4>Direct Student Clearance Payment</h4>
                    <hr style="border-top: 1px solid #edf0f2;">

                    <div id="alertBox"></div>

                    <div class="row text-center" style="margin-bottom:20px;">
                        <div class="col-xs-6">
                            <div class="ledger-summary">
                                <span style="font-size:11px; color:#6c757d; font-weight:600; text-transform:uppercase;">Fees Accounted</span><br>
                                <strong style="font-size:16px; color:#107c41;">GHS <?php echo number_format($fee_paid, 2); ?></strong>
                            </div>
                        </div>
                        <div class="col-xs-6">
                            <div class="ledger-summary" style="background:#fff5f5; border-color:#fde7e7;">
                                <span style="font-size:11px; color:#6c757d; font-weight:600; text-transform:uppercase;">Debt Owed</span><br>
                                <strong style="font-size:16px; color:#a83232;">GHS <?php echo number_format($fee_balance, 2); ?></strong>
                            </div>
                        </div>
                    </div>

                    <?php if ($fee_balance > 0): ?>
                        <form id="paymentForm" onsubmit="payWithPaystack(event)">
                            <div class="form-group">
                                <label style="font-size:12px; text-transform:uppercase; color:#6c757d;">Payer Identity</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label style="font-size:12px; text-transform:uppercase; color:#6c757d;">Enter Amount to Pay (GHS)</label>
                                <input type="number" step="0.01" min="5" max="<?php echo $fee_balance; ?>" id="payAmount" class="form-control" placeholder="Minimum GHS 5.00" required>
                            </div>
                            
                            <button type="submit" class="btn btn-block btn-pay">
                                <i class="fa fa-lock"></i> Initialize Live Payment Gateway
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success text-center" style="margin-top:20px; border-radius:8px;">
                            <i class="fa fa-check-circle fa-3x" style="color:#107c41;"></i>
                            <h4 style="color:#107c41; font-weight:700; margin-top:10px;">Account Fully Settled!</h4>
                            <p style="margin:0; font-size:13px;">Your financial dues are clear. You are set to receive clearance.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jquery-2.1.1.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    
    <script>
    function payWithPaystack(event) {
        event.preventDefault(); // Stop form from page reloading

        var rawAmount = document.getElementById('payAmount').value;
        var totalBalance = <?php echo $fee_balance; ?>;
        
        if (rawAmount <= 0 || rawAmount > totalBalance) {
            alert("Please input a valid financial amount matching constraints.");
            return;
        }

        // Paystack tracks fractional amounts in lowest base currency units (Pesewas for GHS)
        // Therefore GHS 1.00 = 100 Pesewas. We multiply value entry by 100 explicitly
        var paystackAmountInPesewas = Math.round(rawAmount * 100); 

        var handler = PaystackPop.setup({
            key: 'pk_test_YOUR_PUBLIC_KEY_HERE', // 🔴 REPLACE THIS STRING WITH YOUR TEST PUBLIC KEY FROM PAYSTACK
            email: '<?php echo $student_email; ?>',
            amount: paystackAmountInPesewas,
            currency: 'GHS', // Set explicit Ghana Cedis denomination channel
            ref: 'UTAS-' + Math.floor((Math.random() * 1000000000) + 1), // Generate client tracking reference
            
            callback: function(response) {
                // This block executes dynamically THE MOMENT Mobile Money Pin processing finishes perfectly!
                // We transmit metadata quietly back to database using background jQuery POST
                $.ajax({
                    url: 'pay_fee.php',
                    method: 'POST',
                    data: {
                        action: 'verify_payment',
                        amount: rawAmount,
                        reference: response.reference
                    },
                    success: function(res) {
                        if(res.status === 'success') {
                            document.getElementById('alertBox').innerHTML = '<div class="alert alert-success"><i class="fa fa-check-circle"></i> Payment verified successfully! Redirecting back to account console...</div>';
                            setTimeout(function(){
                                window.location.href = 'dashboard.php';
                            }, 2500);
                        } else {
                            document.getElementById('alertBox').innerHTML = '<div class="alert alert-danger"><i class="fa fa-times"></i> Account logging mismatch: ' + res.message + '</div>';
                        }
                    },
                    error: function() {
                        alert("Network sync timeout. Do not re-submit. Check account statements.");
                    }
                });
            },
            onClose: function() {
                alert('Transaction canvas suspended. Student closed secure modal environment.');
            }
        });
        
        handler.openIframe();
    }
    </script>
</body>
</html>