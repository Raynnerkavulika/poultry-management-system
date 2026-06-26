<?php
// logout.php - Log out user and destroy session
session_start();

// Unset all session variables
$_SESSION = array();

// If a session cookie exists, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete the "remember me" cookie if it exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect to login page with a logout success message (optional)
header('Location: login.php?logout=success');
exit;
?>