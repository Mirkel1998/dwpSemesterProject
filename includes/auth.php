<?php

function current_user(PDO $pdo): ?array
{
    // Cache the session's user lookup for the remainder of this request.
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['userID']) ? (new UserRepository($pdo))->findProfile((int) $_SESSION['userID']) : null;
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
