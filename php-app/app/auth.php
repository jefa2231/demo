<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

if (!function_exists('admin_user')) {
    function admin_user(): ?array
    {
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        if ($adminId <= 0) {
            return null;
        }

        $stmt = db()->prepare('SELECT id, username FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    function require_admin_login(): void
    {
        if (!admin_user()) {
            header('Location: ' . local_url('/admin/login'));
            exit;
        }
    }

    function attempt_admin_login(string $username, string $password): bool
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return false;
        }

        $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $admin = $stmt->fetch();
        if (!$admin) {
            return false;
        }

        if (!password_verify($password, (string) $admin['password_hash'])) {
            return false;
        }

        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = (string) $admin['username'];
        return true;
    }

    function admin_logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        session_regenerate_id(true);
    }
}

