<?php
// /includes/functions.php

// Các hàm helper chung

function base_url($path = '') {
    return BASE_URL . ltrim($path, '/');
}

function asset_url($path = '') {
    if (!empty($path)) {
        return BASE_URL . 'assets/' . ltrim($path, '/');
    }
    return BASE_URL . 'assets/';
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN);
}

function getCurrentUser($key = null) {
    if (!isLoggedIn()) {
        return null;
    }
    if ($key) {
        return $_SESSION['user_data'][$key] ?? null;
    }
    return $_SESSION['user_data'];
}

function set_flash_message($name, $message, $type = 'success') {
    if (!empty($name) && !empty($message)) {
        $_SESSION['flash_message'][$name] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

function display_flash_message($name) {
    if (isset($_SESSION['flash_message'][$name])) {
        $flash = $_SESSION['flash_message'][$name];
        $message = $flash['message'];
        $type = $flash['type'];
        unset($_SESSION['flash_message'][$name]);
        return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">' . $message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return '';
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function write_log($message, $level = 'INFO') {
    $log_path = PROJECT_ROOT . '/logs/system.log';
    if (!file_exists(dirname($log_path))) {
        mkdir(dirname($log_path), 0775, true);
    }
    $formatted_message = "[" . date('Y-m-d H:i:s') . "] [" . $level . "] " . $message . PHP_EOL;
    file_put_contents($log_path, $formatted_message, FILE_APPEND);
}

function format_currency($number, $currency = ' VNĐ') {
    if (!is_numeric($number)) { return 'N/A'; }
    return number_format((float)$number, 0, ',', '.') . $currency;
}

function create_excerpt($string, $length = 100, $append = '...') {
    $string = strip_tags($string);
    if (strlen($string) <= $length) {
        return $string;
    }
    $last_space = strrpos(substr($string, 0, $length), ' ');
    return substr($string, 0, $last_space) . $append;
}

function generateRandomAlphanumericString($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        try {
            $randomString .= $characters[random_int(0, $charLength - 1)];
        } catch (Exception $e) {
            $randomString .= $characters[rand(0, $charLength - 1)];
        }
    }
    return $randomString;
}
?>