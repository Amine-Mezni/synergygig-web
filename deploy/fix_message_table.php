<?php
$host = getenv('DB_HOST') ?: 'synergygig-mysql';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'finale_synergygig';
$user = getenv('DB_USER') ?: 'seji';
$pass = getenv('DB_PASSWORD') ?: 'MORTALkombat9pd6S##E';

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// First list all tables
echo "=== ALL TABLES ===\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) echo "  $t\n";
echo "\n";

// Find the message table name
$msgTable = null;
foreach ($tables as $t) {
    if (stripos($t, 'message') !== false || stripos($t, 'chat_message') !== false) {
        $msgTable = $t;
        echo "Found message table: $msgTable\n";
    }
}

if (!$msgTable) {
    echo "No message table found! Creating it...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT DEFAULT NULL,
        sender_id INT DEFAULT NULL,
        content LONGTEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        attachment VARCHAR(255) DEFAULT NULL,
        attachment_original_name VARCHAR(255) DEFAULT NULL,
        INDEX idx_room (room_id),
        INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created messages table\n";
} else {
    // Add missing columns
    $columns = [
        'attachment'               => 'VARCHAR(255) DEFAULT NULL',
        'attachment_original_name' => 'VARCHAR(255) DEFAULT NULL',
    ];
    foreach ($columns as $col => $def) {
        $check = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$db' AND TABLE_NAME='$msgTable' AND COLUMN_NAME='$col'")->fetchColumn();
        if ($check == 0) {
            $pdo->exec("ALTER TABLE `$msgTable` ADD COLUMN $col $def");
            echo "Added column $col to $msgTable\n";
        } else {
            echo "Column already exists: $col\n";
        }
    }
}

// Also check/create chat_room and chat_room_member
$neededTables = ['chat_room', 'chat_room_member'];
foreach ($neededTables as $nt) {
    if (!in_array($nt, $tables)) {
        echo "Missing table: $nt\n";
    } else {
        echo "Table exists: $nt\n";
        $cols = $pdo->query("SHOW COLUMNS FROM `$nt`")->fetchAll(PDO::FETCH_COLUMN);
        echo "  Columns: " . implode(', ', $cols) . "\n";
    }
}

echo "\nDONE\n";
