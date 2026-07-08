<?php
$host = '127.0.0.1';
$db   = 'datn';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $user, $pass, $options);

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

$dbSchema = [];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $dbSchema[$table] = $columns;
}

$sqlContent = file_get_contents('laravel (7) (1).sql');
// Remove ENGINE= since it's causing issues.
preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\)/is', $sqlContent, $matches);

$sqlSchema = [];
foreach ($matches[1] as $index => $table) {
    $columnsBlock = $matches[2][$index];
    preg_match_all('/^\s*`([^`]+)`/m', $columnsBlock, $colMatches);
    $sqlSchema[$table] = $colMatches[1];
}

$missingTables = [];
$missingColumns = [];

foreach ($sqlSchema as $table => $columns) {
    if (!isset($dbSchema[$table])) {
        $missingTables[] = $table;
    } else {
        $diff = array_diff($columns, $dbSchema[$table]);
        if (!empty($diff)) {
            $missingColumns[$table] = $diff;
        }
    }
}

echo "Total tables in DB: " . count($dbSchema) . "\n";
echo "Total tables in SQL file: " . count($sqlSchema) . "\n";

echo "Missing tables in DB:\n";
print_r($missingTables);

echo "\nMissing columns in existing tables:\n";
print_r($missingColumns);
