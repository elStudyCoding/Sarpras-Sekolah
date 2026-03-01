<?php

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input()
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_is_valid_request')) {
    function csrf_is_valid_request()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return false;
        }

        $sessionToken = $_SESSION['_csrf_token'] ?? '';
        $requestToken = $_POST['_csrf_token'] ?? '';

        if ($sessionToken === '' || $requestToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $requestToken);
    }
}
