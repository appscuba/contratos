<?php
/**
 * Configuración General del Sistema
 * Sistema de Gestión de Contratos
 */

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión de Contratos');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 3600 * 8, // 8 horas
    'path' => '/',
    'domain' => '',
    'secure' => false, // Cambiar a true en HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Configuración de archivos
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

// Configuración de email
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@empresa.com');
define('SMTP_FROM_NAME', 'Sistema de Contratos');

// Roles y permisos
define('ROLES', [
    'super_admin' => 'Super Administrador',
    'admin' => 'Administrador',
    'manager' => 'Gerente',
    'user' => 'Usuario'
]);

define('PERMISSIONS', [
    'super_admin' => ['*'],
    'admin' => ['users.*', 'contracts.*', 'categories.*', 'reports.*', 'settings.*'],
    'manager' => ['contracts.*', 'categories.read', 'reports.read', 'users.read'],
    'user' => ['contracts.read', 'contracts.create', 'contracts.update_own']
]);

// Funciones de utilidad
function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . '_' . mt_rand(1000, 9999);
}

function formatCurrency($amount, $currency = 'USD') {
    $symbols = [
        'USD' => '$',
        'EUR' => '€',
        'MXN' => '$',
        'GBP' => '£'
    ];
    
    $symbol = $symbols[$currency] ?? $currency;
    return $symbol . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    $units = [
        31536000 => 'año',
        2592000 => 'mes',
        604800 => 'semana',
        86400 => 'día',
        3600 => 'hora',
        60 => 'minuto',
        1 => 'segundo'
    ];
    
    foreach ($units as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
    
    return 'justo ahora';
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Crear directorios necesarios
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/contracts',
    __DIR__ . '/../uploads/temp',
    __DIR__ . '/../logs',
    __DIR__ . '/../cache'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}