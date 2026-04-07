<?php
$pdo = new PDO(
    "mysql:host=" . (getenv('DB_HOST') ?: 'synergygig-mysql') . ";dbname=" . (getenv('DB_NAME') ?: 'finale_synergygig'),
    getenv('DB_USER') ?: 'seji',
    getenv('DB_PASSWORD') ?: 'MORTALkombat9pd6S##E'
);
echo "=== ROLES IN DB ===\n";
foreach ($pdo->query("SELECT DISTINCT role, COUNT(*) as cnt FROM user GROUP BY role")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "  {$r['role']}: {$r['cnt']} users\n";
}
