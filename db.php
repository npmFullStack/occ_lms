<?php
$host = '127.0.0.1';
$dbname = 'occ_lms';
$username = 'root';
$password = 'nor';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function log_activity($user_id, $action, $description) {
    global $pdo;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                          VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $ip_address]);
}
?>