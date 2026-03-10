<?php
/**
 * Seed admin user. Hapus file ini setelah dipakai!
 * Akses: https://domainkamu.com/seed-admin.php
 */

require_once __DIR__ . '/../app/db.php';

$username = 'admin';
$password = 'admin12345';

$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo = db();

// Cek kolom timestamp
$cols = $pdo->query("SHOW COLUMNS FROM admins")->fetchAll(PDO::FETCH_COLUMN);
$hasCreatedAt = in_array('createdAt', $cols);
$hasCreatedAtSnake = in_array('created_at', $cols);

$stmt = $pdo->prepare('SELECT id FROM admins WHERE username = :u LIMIT 1');
$stmt->execute(['u' => $username]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('UPDATE admins SET password_hash = :h WHERE id = :id')
        ->execute(['h' => $hash, 'id' => $existing['id']]);
    echo "Admin '{$username}' password updated.<br>";
} else {
    if ($hasCreatedAt) {
        $pdo->prepare('INSERT INTO admins (username, password_hash, createdAt) VALUES (:u, :h, NOW())')
            ->execute(['u' => $username, 'h' => $hash]);
    } elseif ($hasCreatedAtSnake) {
        $pdo->prepare('INSERT INTO admins (username, password_hash, created_at) VALUES (:u, :h, NOW())')
            ->execute(['u' => $username, 'h' => $hash]);
    } else {
        $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :h)')
            ->execute(['u' => $username, 'h' => $hash]);
    }
    echo "Admin '{$username}' created.<br>";
}

echo "Username: {$username}<br>";
echo "Password: {$password}<br>";
echo "<br><strong>HAPUS FILE INI SETELAH SELESAI!</strong>";
