<?php
// Only allow POST logout to reduce risk (CSRF via simple links)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: trajets.php');
    exit;
}

if (session_status() == PHP_SESSION_NONE) session_start();

// Clear all session variables (this removes cart and all user info)
$_SESSION = [];

// If there's a session cookie, remove it
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destroy the session
session_unset();
session_destroy();

// (Optional) Start a fresh session and regenerate id for safety
session_start();
session_regenerate_id(true);

// Redirect to the login/auth page
header('Location: authentification.html');
exit;
