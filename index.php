<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';
require_once 'config/database.php';

$db = Database::getInstance();

// Obtener estadísticas del dashboard
try {
    // Total de contratos
    $totalContracts = $db->fetch("SELECT COUNT(*) as count FROM contracts")['count'];
    
    // Contratos activos
    $activeContracts = $db->fetch("SELECT COUNT(*) as count FROM contracts WHERE status = 'active'")['count'];
    
    // Contratos próximos a vencer (30 días)
    $expiringContracts = $db->fetch(
        "SELECT COUNT(*) as count FROM contracts 
         WHERE status = 'active' AND end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)"
    )['count'];
    
    // Valor total de contratos activos
    $totalValue = $db->fetch(
        "SELECT SUM(value) as total FROM contracts WHERE status = 'active'"
    )['total'] ?? 0;
    
    // Contratos por categoría
    $contractsByCategory = $db->fetchAll(
        "SELECT cc.name, cc.color, COUNT(c.id) as count, SUM(c.value) as total_value
         FROM contract_categories cc
         LEFT JOIN contracts c ON cc.id = c.category_id AND c.status = 'active'
         WHERE cc.is_active = 1
         GROUP BY cc.id, cc.name, cc.color
         ORDER BY count DESC"
    );
    
    // Contratos por estado
    $contractsByStatus = $db->fetchAll(
        "SELECT 
            CASE 
                WHEN status = 'active' THEN 'Activos'
                WHEN status = 'pending_renewal' THEN 'Pendiente Renovación'
                WHEN status = 'expired' THEN 'Vencidos'
                WHEN status = 'draft' THEN 'Borradores'
                WHEN status = 'terminated' THEN 'Terminados'
                ELSE 'Otros'
            END as status_label,
            status,
            COUNT(*) as count
         FROM contracts 
         GROUP BY status 
         ORDER BY count DESC"
    );
    
    // Contratos próximos a vencer (detalles)
    $upcomingExpirations = $db->fetchAll(
        "SELECT c.*, cc.name as category_name, cc.color as category_color,
                cs.name as client_supplier_name,
                DATEDIFF(c.end_date, NOW()) as days_until_expiry
         FROM contracts c
         JOIN contract_categories cc ON c.category_id = cc.id
         JOIN clients_suppliers cs ON c.client_supplier_id = cs.id
         WHERE c.status = 'active' 
         AND c.end_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
         ORDER BY c.end_date ASC
         LIMIT 10"
    );
    
    // Actividad reciente
    $recentActivity = $db->fetchAll(
        "SELECT al.*, u.full_name
         FROM audit_logs al
         LEFT JOIN users u ON al.user_id = u.id
         ORDER BY al.created_at DESC
         LIMIT 15"
    );
    
} catch (Exception $e) {
    error_log("Error getting dashboard stats: " . $e->getMessage());
    $totalContracts = $activeContracts = $expiringContracts = $totalValue = 0;
    $contractsByCategory = $contractsByStatus = $upcomingExpirations = $recentActivity = [];
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Dashboard</h1>
                    <p class="text-muted">Resumen general del sistema de contratos</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-2"></i>Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h2 class="mb-0"><?= number_format($totalContracts) ?></h2>
                        <p class="mb-0">Total Contratos</p>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card success h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h2 class="mb-0"><?= number_format($activeContracts) ?></h2>
                        <p class="mb-0">Contratos Activos</p>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card warning h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h2 class="mb-0"><?= number_format($expiringContracts) ?></h2>
                        <p class="mb-0">Próximos a Vencer</p>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stats-card danger h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h2 class="mb-0"><?= formatCurrency($totalValue) ?></h2>
                        <p class="mb-0">Valor Total Activo</p>
                    </div>
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Contratos por Categoría -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contratos por Categoría</h5>
                    <small class="text-muted">Solo contratos activos</small>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contratos por Estado -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Contratos por Estado</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tables Row -->
    <div class="row">
        <!-- Próximos Vencimientos -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contratos Próximos a Vencer</h5>
                    <a href="contracts.php?filter=expiring" class="btn btn-sm btn-outline-primary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($upcomingExpirations)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <h6>¡Excelente!</h6>
                            <p class="text-muted">No hay contratos próximos a vencer en los próximos 30 días.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Cliente/Proveedor</th>
                                        <th>Categoría</th>
                                        <th>Vencimiento</th>
                                        <th>Días Restantes</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingExpirations as $contract): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($contract['title']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($contract['contract_number']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($contract['client_supplier_name']) ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= $contract['category_color'] ?>">
                                                    <?= htmlspecialchars($contract['category_name']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($contract['end_date'])) ?></td>
                                            <td>
                                                <?php 
                                                $days = $contract['days_until_expiry'];
                                                $badgeClass = $days <= 7 ? 'bg-danger' : ($days <= 15 ? 'bg-warning' : 'bg-info');
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= $days ?> día<?= $days != 1 ? 's' : '' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="contract_detail.php?id=<?= $contract['id'] ?>" 
                                                       class="btn btn-outline-primary" 
                                                       data-bs-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="contract_edit.php?id=<?= $contract['id'] ?>" 
                                                       class="btn btn-outline-warning"
                                                       data-bs-toggle="tooltip" title="Renovar">
                                                        <i class="fas fa-redo"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Actividad Reciente -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Actividad Reciente</h5>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history text-muted fa-2x mb-3"></i>
                            <p class="text-muted">No hay actividad reciente.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentActivity as $activity): ?>
                                <div class="timeline-item mb-3">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <div class="activity-icon">
                                                <?php
                                                $iconClass = 'fas fa-circle';
                                                switch ($activity['action']) {
                                                    case 'create': $iconClass = 'fas fa-plus text-success'; break;
                                                    case 'update': $iconClass = 'fas fa-edit text-warning'; break;
                                                    case 'delete': $iconClass = 'fas fa-trash text-danger'; break;
                                                    case 'login': $iconClass = 'fas fa-sign-in-alt text-info'; break;
                                                    case 'logout': $iconClass = 'fas fa-sign-out-alt text-secondary'; break;
                                                }
                                                ?>
                                                <i class="<?= $iconClass ?>"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <div class="activity-content">
                                                <strong><?= htmlspecialchars($activity['full_name'] ?? 'Usuario') ?></strong>
                                                <?php
                                                $actionText = [
                                                    'create' => 'creó',
                                                    'update' => 'actualizó',
                                                    'delete' => 'eliminó',
                                                    'login' => 'inició sesión',
                                                    'logout' => 'cerró sesión'
                                                ];
                                                echo ($actionText[$activity['action']] ?? $activity['action']) . ' ';
                                                
                                                if ($activity['action'] !== 'login' && $activity['action'] !== 'logout') {
                                                    echo $activity['table_name'];
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= timeAgo($activity['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Datos para gráfico de categorías
    const categoryData = {
        labels: <?= json_encode(array_column($contractsByCategory, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($contractsByCategory, 'count')) ?>,
            backgroundColor: <?= json_encode(array_column($contractsByCategory, 'color')) ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
    
    // Datos para gráfico de estados
    const statusData = {
        labels: <?= json_encode(array_column($contractsByStatus, 'status_label')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($contractsByStatus, 'count')) ?>,
            backgroundColor: [
                '#198754', // Activos - verde
                '#fd7e14', // Pendiente - naranja
                '#dc3545', // Vencidos - rojo
                '#6c757d', // Borradores - gris
                '#495057'  // Terminados - gris oscuro
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
    
    // Crear gráficos
    if (typeof Chart !== 'undefined') {
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        App.createDoughnutChart(categoryCtx, categoryData);
        
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        App.createDoughnutChart(statusCtx, statusData);
    }
    
    // Auto-refresh cada 5 minutos
    setInterval(function() {
        console.log('Auto-refreshing dashboard data...');
        // Aquí puedes agregar lógica para actualizar solo los datos sin recargar la página
    }, 300000);
});
</script>

<?php require_once 'includes/footer.php'; ?>