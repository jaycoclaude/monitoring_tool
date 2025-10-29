<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mt_db');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!-- $host = 'localhost';
$db = 'newrwandafdagov_fdaweb';
$user = 'newrwandafdagov';
//$pass = 'hzNyh6,CRrI2';Rwandafda@2025#
$pass = 'Rwandafda@2025#';
$charset = 'utf8mb4';
$datetoday = date("Y-m-d"); -->