<?php
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/Gamification.php';

$pdo = require __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Invalid form token. Go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function current_user(PDO $pdo): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (isset($_SESSION['userID'])) {
            $stmt = $pdo->prepare('SELECT userID, username, role, xp, level, login_streak FROM users WHERE userID = ?');
            $stmt->execute([$_SESSION['userID']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if (!$user) {
        flash('error', 'Insert coin: please log in first.');
        redirect('login.php');
    }
    return $user;
}

function require_admin(PDO $pdo): array
{
    $user = require_login($pdo);
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Admins only.');
    }
    return $user;
}

function login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['userID'] = $userId;
}
