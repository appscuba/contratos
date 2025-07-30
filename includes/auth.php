<?php
/**
 * Sistema de Autenticación
 * Manejo de sesiones, login y permisos
 */

require_once 'config/database.php';
require_once 'config/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function login($username, $password) {
        try {
            $user = $this->db->fetch(
                "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1",
                [$username, $username]
            );
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Actualizar último login
                $this->db->query(
                    "UPDATE users SET last_login = NOW() WHERE id = ?",
                    [$user['id']]
                );
                
                // Crear sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['login_time'] = time();
                
                // Log de auditoría
                $this->logActivity($user['id'], 'login', 'users', $user['id']);
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetch(
            "SELECT id, username, email, full_name, role, department, phone, last_login 
             FROM users WHERE id = ? AND is_active = 1",
            [$_SESSION['user_id']]
        );
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $role = $_SESSION['role'];
        $permissions = PERMISSIONS[$role] ?? [];
        
        // Super admin tiene todos los permisos
        if (in_array('*', $permissions)) {
            return true;
        }
        
        // Verificar permiso específico
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Verificar permisos con wildcards
        foreach ($permissions as $perm) {
            if (strpos($perm, '*') !== false) {
                $pattern = str_replace('*', '.*', $perm);
                if (preg_match("/^{$pattern}$/", $permission)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            header('HTTP/1.1 403 Forbidden');
            include 'includes/403.php';
            exit;
        }
    }
    
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            $user = $this->db->fetch(
                "SELECT password_hash FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return false;
            }
            
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $this->db->query(
                "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
                [$newHash, $userId]
            );
            
            $this->logActivity($userId, 'password_change', 'users', $userId);
            return true;
            
        } catch (Exception $e) {
            error_log("Error cambiando contraseña: " . $e->getMessage());
            return false;
        }
    }
    
    public function createUser($data) {
        try {
            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $this->db->query(
                "INSERT INTO users (username, email, password_hash, full_name, role, department, phone) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $data['username'],
                    $data['email'],
                    $passwordHash,
                    $data['full_name'],
                    $data['role'],
                    $data['department'] ?? null,
                    $data['phone'] ?? null
                ]
            );
            
            $userId = $this->db->lastInsertId();
            $this->logActivity($_SESSION['user_id'], 'create', 'users', $userId);
            
            return $userId;
            
        } catch (Exception $e) {
            error_log("Error creando usuario: " . $e->getMessage());
            return false;
        }
    }
    
    private function logActivity($userId, $action, $table, $recordId, $oldValues = null, $newValues = null) {
        try {
            $this->db->query(
                "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userId,
                    $action,
                    $table,
                    $recordId,
                    $oldValues ? json_encode($oldValues) : null,
                    $newValues ? json_encode($newValues) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
        }
    }
    
    public function getRecentActivity($limit = 50) {
        return $this->db->fetchAll(
            "SELECT al.*, u.full_name, u.username 
             FROM audit_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             ORDER BY al.created_at DESC 
             LIMIT ?",
            [$limit]
        );
    }
}

// Instancia global de autenticación
$auth = new Auth();