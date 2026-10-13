<?php
// Shared entry point for application pages: start a secure session, load
// gamification behavior and the database connection, then define common helpers.
declare(strict_types=1);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require_once __DIR__ . '/Gamification.php';

$pdo = require __DIR__ . '/db.php';

function e(?string $value): string
{
    // Escape dynamic values before placing them in HTML text or attributes.
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    // Send the browser to another page and stop the current request.
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    // Reuse one unpredictable request token for forms in this session.
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    // Reject state-changing form submissions with a missing or incorrect token.
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('Invalid form token. Go back and try again.');
    }
}

function flash(string $type, string $message): void
{
    // Queue a one-time message for display by the next page header.
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
    // Cache the session's user lookup for the remainder of this request.
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
    // Redirect guests to login; return the signed-in user's account data.
    $user = current_user($pdo);
    if (!$user) {
        flash('error', 'Insert coin: please log in first.');
        redirect('login.php');
    }
    return $user;
}

function require_admin(PDO $pdo): array
{
    // Apply the login check first, then forbid non-admin accounts.
    $user = require_login($pdo);
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Admins only.');
    }
    return $user;
}

function login_user(int $userId): void
{
    // Rotate the session ID on login to prevent session fixation.
    session_regenerate_id(true);
    $_SESSION['userID'] = $userId;
}
