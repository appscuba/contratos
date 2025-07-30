<?php
require_once 'includes/auth.php';

// Verificar si el usuario está logueado (excepto en login.php)
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php') {
    $auth->requireLogin();
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Dashboard' ?> - <?= APP_NAME ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Estilos personalizados -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= $currentPage === 'login.php' ? 'login-page' : '' ?>">

<?php if ($currentPage !== 'login.php'): ?>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button class="btn btn-primary d-lg-none" type="button" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            
            <a class="navbar-brand d-none d-lg-block" href="index.php">
                <i class="fas fa-file-contract me-2"></i>
                ContractPro
            </a>
            
            <div class="navbar-nav ms-auto d-flex flex-row align-items-center">
                <!-- Notifications -->
                <div class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notificationsDropdown" 
                       role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                              id="notificationCount">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" 
                        style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notificaciones</span>
                            <small><a href="notifications.php">Ver todas</a></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1">Contrato próximo a vencer</h6>
                                        <p class="mb-1 text-muted small">El contrato con TechSolutions vence en 15 días</p>
                                        <small class="text-muted">Hace 2 horas</small>
                                    </div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li class="text-center p-2">
                            <small><a href="notifications.php">Ver todas las notificaciones</a></small>
                        </li>
                    </ul>
                </div>
                
                <!-- User Menu -->
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" 
                       id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($user['full_name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                            <small class="text-muted"><?= ROLES[$user['role']] ?></small>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user me-2"></i>Mi Perfil
                        </a></li>
                        <li><a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-file-contract me-2"></i>ContractPro</h4>
        </div>
        
        <nav class="sidebar-nav">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= strpos($currentPage, 'contract') !== false ? 'active' : '' ?>" 
                       href="contracts.php">
                        <i class="fas fa-file-contract"></i>
                        <span>Contratos</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>" 
                       href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Categorías</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'clients.php' ? 'active' : '' ?>" 
                       href="clients.php">
                        <i class="fas fa-handshake"></i>
                        <span>Clientes/Proveedores</span>
                    </a>
                </li>
                
                <?php if ($auth->hasPermission('users.read')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" 
                       href="users.php">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" 
                       href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reportes</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'notifications.php' ? 'active' : '' ?>" 
                       href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span>Notificaciones</span>
                        <span class="badge bg-danger ms-auto" id="sidebarNotificationCount">3</span>
                    </a>
                </li>
                
                <?php if ($auth->hasPermission('settings.read')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>" 
                       href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
<?php endif; ?>