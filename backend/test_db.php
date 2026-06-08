<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=laravel", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected successfully\n";
    $stmt = $pdo->query("SELECT * FROM danhmuc LIMIT 1");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
