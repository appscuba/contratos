-- Sistema de Gestión de Contratos Empresarial
-- Base de datos optimizada para PHP 8.0 y MySQL 8.0

CREATE DATABASE IF NOT EXISTS contract_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE contract_management;

-- Tabla de usuarios con roles y permisos
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin', 'manager', 'user') DEFAULT 'user',
    department VARCHAR(100),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Tabla de categorías de contratos personalizables
CREATE TABLE contract_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(7) DEFAULT '#007bff',
    icon VARCHAR(50) DEFAULT 'fas fa-file-contract',
    is_active BOOLEAN DEFAULT TRUE,
    workflow_required BOOLEAN DEFAULT FALSE,
    approval_levels INT DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_active (is_active)
);

-- Tabla de clientes y proveedores
CREATE TABLE clients_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    type ENUM('client', 'supplier', 'both') NOT NULL,
    tax_id VARCHAR(50),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    contact_person VARCHAR(100),
    website VARCHAR(200),
    rating DECIMAL(2,1) DEFAULT 5.0,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active),
    INDEX idx_name (name)
);

-- Tabla principal de contratos
CREATE TABLE contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('client', 'supplier') NOT NULL,
    category_id INT NOT NULL,
    client_supplier_id INT NOT NULL,
    value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'active', 'pending_renewal', 'expired', 'terminated', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    auto_renewal BOOLEAN DEFAULT FALSE,
    renewal_period INT DEFAULT 12, -- meses
    renewal_notice_days INT DEFAULT 30,
    assigned_to INT,
    created_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    tags JSON,
    custom_fields JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES contract_categories(id),
    FOREIGN KEY (client_supplier_id) REFERENCES clients_suppliers(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_value (value),
    INDEX idx_assigned (assigned_to),
    FULLTEXT(title, description)
);

-- Tabla de documentos adjuntos
CREATE TABLE contract_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    file_path VARCHAR(500) NOT NULL,
    document_type ENUM('contract', 'addendum', 'invoice', 'report', 'other') DEFAULT 'other',
    uploaded_by INT NOT NULL,
    version INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_contract (contract_id),
    INDEX idx_type (document_type)
);

-- Tabla de notificaciones del sistema
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    category ENUM('contract_expiry', 'renewal_due', 'approval_required', 'system', 'user_action') DEFAULT 'system',
    contract_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
);

-- Tabla de configuración del sistema
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabla de logs de auditoría
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created (created_at)
);

-- Tabla de sesiones de usuario
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Insertar datos iniciales
INSERT INTO users (username, email, password_hash, full_name, role, department) VALUES
('admin', 'admin@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'super_admin', 'Administración'),
('manager', 'manager@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gerente de Contratos', 'manager', 'Gestión'),
('user', 'user@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usuario Regular', 'user', 'Operaciones');

INSERT INTO contract_categories (name, description, color, icon, created_by) VALUES
('Tecnología', 'Contratos relacionados con servicios de TI y software', '#007bff', 'fas fa-laptop-code', 1),
('Mantenimiento', 'Servicios de mantenimiento y reparaciones', '#28a745', 'fas fa-tools', 1),
('Suministros', 'Contratos de suministro de materiales y productos', '#ffc107', 'fas fa-boxes', 1),
('Consultoría', 'Servicios profesionales y de consultoría', '#6f42c1', 'fas fa-user-tie', 1),
('Seguros', 'Pólizas de seguros y coberturas', '#dc3545', 'fas fa-shield-alt', 1);

INSERT INTO clients_suppliers (name, type, tax_id, email, phone, contact_person) VALUES
('TechSolutions S.A.', 'supplier', '12345678-9', 'contacto@techsolutions.com', '+1-555-0123', 'Juan Pérez'),
('GlobalCorp Ltd.', 'client', '98765432-1', 'contracts@globalcorp.com', '+1-555-0456', 'María García'),
('ServicePro Inc.', 'supplier', '11223344-5', 'info@servicepro.com', '+1-555-0789', 'Carlos Rodríguez'),
('MegaClient Corp.', 'client', '55667788-9', 'procurement@megaclient.com', '+1-555-0321', 'Ana López');

INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('company_name', 'Mi Empresa S.A.', 'string', 'Nombre de la empresa', TRUE),
('contract_expiry_warning_days', '30', 'integer', 'Días de anticipación para alertas de vencimiento', FALSE),
('email_notifications_enabled', 'true', 'boolean', 'Habilitar notificaciones por email', FALSE),
('default_currency', 'USD', 'string', 'Moneda por defecto', TRUE),
('max_file_upload_size', '10485760', 'integer', 'Tamaño máximo de archivos en bytes (10MB)', FALSE);