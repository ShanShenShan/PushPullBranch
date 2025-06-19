<?php
require 'includes/connection.php';

$stmt = $pdo->query("SELECT NOW()");
echo "Connected to Render DB. Server time: " . $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PostgreSQL Connection Test</title>
    <link rel="stylesheet" href="css/sb-admin-2.min.css">  
    </head>
<body>
   <a href="inventory_grid/grid.php" class="btn btn-primary">Go to Inventory Grid Management</a>
</body>