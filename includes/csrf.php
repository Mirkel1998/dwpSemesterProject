<?php

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
