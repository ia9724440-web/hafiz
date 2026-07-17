<?php
require_once 'db_connect.php';

// The password we want to assign
$plain_password = "password123";
// Let PHP generate the exact, cryptographically sound hash dynamically
$secure_hash = password_hash($plain_password, PASSWORD_DEFAULT);

$target_admin = "admin"; // <--- CHANGE THIS to your preferred admin username

try {
    // 1. Check if the admin username already exists
    $check_stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :user LIMIT 1");
    $check_stmt->execute(['user' => $target_admin]);
    $admin_exists = $check_stmt->fetch();

    if ($admin_exists) {
        // Update the existing row with the fresh hash and ensure role is explicitly set
        $update_stmt = $pdo->prepare("UPDATE admins SET password = :pass, role = 'admin' WHERE username = :user");
        $update_stmt->execute(['pass' => $secure_hash, 'user' => $target_admin]);
        echo "<h3>✅ Admin account '{$target_admin}' password has been successfully reset!</h3>";
    } else {
        // Insert a brand new working admin row if the username didn't exist
        $insert_stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (:user, :pass, 'admin')");
        $insert_stmt->execute(['user' => $target_admin, 'pass' => $secure_hash]);
        echo "<h3>🚀 Admin account '{$target_admin}' did not exist, so a fresh profile was created!</h3>";
    }
    
    echo "<p>Username: <strong>{$target_admin}</strong></p>";
    echo "<p>Password: <strong>{$plain_password}</strong></p>";
    echo "<p>Generated Hash: <code>{$secure_hash}</code></p>";
    echo "<br><a href='index.php'>Go to Unified Gateway Login</a>";

} catch (\PDOException $e) {
    echo "<h3>❌ Error executing repair:</h3> " . $e->getMessage();
}
?>