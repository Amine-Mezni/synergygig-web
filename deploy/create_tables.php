<?php
require __DIR__ . '/vendor/autoload.php';

$kernel = new App\Kernel('prod', false);
$kernel->boot();
$conn = $kernel->getContainer()->get('doctrine.dbal.default_connection');

$sqls = [
    "CREATE TABLE IF NOT EXISTS bookmarks (
        id INT AUTO_INCREMENT NOT NULL,
        created_at DATETIME NOT NULL,
        user_id INT NOT NULL,
        post_id INT NOT NULL,
        INDEX IDX_78D2C140A76ED395 (user_id),
        INDEX IDX_78D2C1404B89032C (post_id),
        UNIQUE INDEX UNIQ_78D2C140A76ED3954B89032C (user_id, post_id),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS call_signals (
        id INT AUTO_INCREMENT NOT NULL,
        signal_type VARCHAR(20) NOT NULL,
        payload LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        call_id INT NOT NULL,
        from_user_id INT NOT NULL,
        INDEX IDX_3085C3850A89B2C (call_id),
        INDEX IDX_3085C382130303A (from_user_id),
        PRIMARY KEY(id)
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

    "ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C140A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)",
    "ALTER TABLE bookmarks ADD CONSTRAINT FK_78D2C1404B89032C FOREIGN KEY (post_id) REFERENCES posts (id)",
    "ALTER TABLE call_signals ADD CONSTRAINT FK_3085C3850A89B2C FOREIGN KEY (call_id) REFERENCES calls (id)",
    "ALTER TABLE call_signals ADD CONSTRAINT FK_3085C382130303A FOREIGN KEY (from_user_id) REFERENCES user (id)",
];

foreach ($sqls as $sql) {
    try {
        $conn->executeStatement($sql);
        $short = substr(trim($sql), 0, 60);
        echo "OK: $short...\n";
    } catch (\Exception $e) {
        echo "WARN: " . $e->getMessage() . "\n";
    }
}

echo "\nDone.\n";
