<?php
// Try environment variable first (set by Docker/docker-compose)
$dsn = getenv('DATABASE_URL') ?: '';

if (!$dsn) {
    foreach (['/srv/web_synergygig/.env.local', '/srv/web_synergygig/.env'] as $f) {
        if (!file_exists($f)) continue;
        foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, 'DATABASE_URL=') === 0) {
                $dsn = trim(substr($line, 13), '"\'');
                break 2;
            }
        }
    }
}

if (!$dsn) { echo "ERR: no DATABASE_URL found\n"; exit(1); }

$u = parse_url($dsn);
if (!$u || !isset($u['scheme'])) { echo "ERR: cannot parse URL\n"; exit(1); }

$host = $u['host'] ?? '127.0.0.1';
// In Docker, 127.0.0.1 resolves to localhost inside the container - use the MySQL container name instead
if ($host === '127.0.0.1' || $host === 'localhost') { $host = 'synergygig-mysql'; }
// In Docker, 127.0.0.1 resolves to localhost inside the container - use the MySQL container name instead
if ($host === '127.0.0.1' || $host === 'localhost') { $host = 'synergygig-mysql'; }
$port = $u['port'] ?? 3306;
$user = urldecode($u['user'] ?? '');
$pass = urldecode($u['pass'] ?? '');
$db   = explode('?', ltrim($u['path'] ?? '', '/'))[0];

echo "Connecting: host=$host port=$port db=$db user=$user\n";

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("ALTER TABLE attendance ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NOT NULL DEFAULT 'PENDING', ADD COLUMN IF NOT EXISTS rejection_reason TEXT NULL");
$pdo->exec("UPDATE attendance SET approval_status='APPROVED' WHERE status IN ('PRESENT','LATE') AND approval_status='PENDING'");
echo "MIGRATION_OK\n";
