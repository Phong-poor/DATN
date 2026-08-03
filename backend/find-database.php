<?php

$host = 'localhost';
$user = 'root';
$pass = '';

echo "=== CHECKING ALL DATABASES ===\n\n";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "  - $db\n";
    }
    
    echo "\n=== CHECKING FOR sanpham TABLE ===\n\n";
    
    foreach ($databases as $db) {
        if (in_array($db, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
            continue;
        }
        
        try {
            $pdo->query("USE `$db`");
            $result = $pdo->query("SHOW TABLES LIKE 'sanpham'");
            
            if ($result->rowCount() > 0) {
                $count = $pdo->query("SELECT COUNT(*) FROM sanpham")->fetchColumn();
                echo "✅ Database '$db' has 'sanpham' table with $count products\n";
                
                if ($count > 0) {
                    echo "   📦 Sample products:\n";
                    $products = $pdo->query("SELECT id_sanpham, tenSP FROM sanpham LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($products as $p) {
                        echo "      - ID {$p['id_sanpham']}: {$p['tenSP']}\n";
                    }
                }
            }
        } catch (Exception $e) {
            // Skip if error
        }
    }
    
    echo "\n=== DONE ===\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
