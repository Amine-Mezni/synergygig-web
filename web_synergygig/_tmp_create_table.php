<?php
$pdo = new PDO('mysql:host=64.23.239.27;port=3306;dbname=finale_synergygig', 'seji', 'MORTALkombat9pd6S##E');
$pdo->exec('CREATE TABLE IF NOT EXISTS call_signals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_id INT NOT NULL,
    from_user_id INT NOT NULL,
    signal_type VARCHAR(20) NOT NULL,
    payload LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_call_signals_call (call_id),
    INDEX idx_call_signals_user (from_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
echo 'Table created successfully';
