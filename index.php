<?php
require 'includes/connection.php';

$stmt = $pdo->query("SELECT NOW()");
echo "Connected to Render DB. Server time: " . $stmt->fetchColumn();
?>
