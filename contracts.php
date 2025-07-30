<?php
$pageTitle = 'Gestión de Contratos';
require_once 'includes/header.php';
require_once 'config/database.php';

$auth->requirePermission('contracts.read');

$db = Database::getInstance();

// Obtener filtros
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

// Construir consulta con filtros
$whereConditions = ['1=1'];
$params = [];

if ($search) {
    $whereConditions[] = "(c.title LIKE ? OR c.contract_number LIKE ? OR cs.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter) {
    $whereConditions[] = "c.category_id = ?";
    $params[] = $categoryFilter;
}

if ($statusFilter) {
    $whereConditions[] = "c.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $whereConditions[] = "c.type = ?";
    $params[] = $typeFilter;
}

$whereClause = implode(' AND ', $whereConditions);

try {
    // Obtener contratos con paginación
    $contracts = $db->fetchAll(
        "SELECT c.*, cc.name as category_name, cc.color as category_color,
                cs.name as client_supplier_name, cs.type as cs_type,
                u.full_name as assigned_name,
                DATEDIFF(c.end_date, NOW()) as days_until_expiry
         FROM contracts c
         JOIN contract_categories cc ON c.category_id = cc.id
         JOIN clients_suppliers cs ON c.client_supplier_id = cs.id
         LEFT JOIN users u ON c.assigned_to = u.id
         WHERE $whereClause
         ORDER BY c.created_at DESC",
        $params
    );
    
    // Obtener categorías para filtros
    $categories = $db->fetchAll(
        "SELECT id, name FROM contract_categories WHERE is_active = 1 ORDER BY name"
    );
    
} catch (Exception $e) {
    error_log("Error getting contracts: " . $e->getMessage());
    $contracts = [];
    $categories = [];
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">Gestión de Contratos</h1>
                    <p class="text-muted">Administre todos los contratos de la empresa</p>
                </div>
                <div>
                    <?php if ($auth->hasPermission('contracts.create')): ?>
                        <a href="contract_form.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Nuevo Contrato
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Buscar</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Título, número o cliente...">
                        </div>
                        
                        <div class="col-md-2">
                            <label for="category" class="form-label">Categoría</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                            <?= $categoryFilter == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Activo</option>
                                <option value="draft" <?= $statusFilter == 'draft' ? 'selected' : '' ?>>Borrador</option>
                                <option value="pending_renewal" <?= $statusFilter == 'pending_renewal' ? 'selected' : '' ?>>Pendiente Renovación</option>
                                <option value="expired" <?= $statusFilter == 'expired' ? 'selected' : '' ?>>Vencido</option>
                                <option value="terminated" <?= $statusFilter == 'terminated' ? 'selected' : '' ?>>Terminado</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label for="type" class="form-label">Tipo</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">Todos</option>
                                <option value="client" <?= $typeFilter == 'client' ? 'selected' : '' ?>>Cliente</option>
                                <option value="supplier" <?= $typeFilter == 'supplier' ? 'selected' : '' ?>>Proveedor</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filtrar
                            </button>
                            <a href="contracts.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contracts Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Contratos (<?= count($contracts) ?> resultados)
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" onclick="exportContracts('pdf')">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                        <button class="btn btn-outline-secondary" onclick="exportContracts('excel')">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($contracts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-contract text-muted fa-3x mb-3"></i>
                            <h5>No se encontraron contratos</h5>
                            <p class="text-muted">
                                <?php if ($search || $categoryFilter || $statusFilter || $typeFilter): ?>
                                    No hay contratos que coincidan con los filtros aplicados.
                                <?php else: ?>
                                    Aún no hay contratos registrados en el sistema.
                                <?php endif; ?>
                            </p>
                            <?php if ($auth->hasPermission('contracts.create')): ?>
                                <a href="contract_form.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Crear Primer Contrato
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="contractsTable">
                                <thead>
                                    <tr>
                                        <th>Contrato</th>
                                        <th>Tipo</th>
                                        <th>Cliente/Proveedor</th>
                                        <th>Categoría</th>
                                        <th>Valor</th>
                                        <th>Inicio</th>
                                        <th>Vencimiento</th>
                                        <th>Estado</th>
                                        <th>Responsable</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <strong><?= htmlspecialchars($contract['title']) ?></strong>
                                                    <small class="text-muted"><?= htmlspecialchars($contract['contract_number']) ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $contract['type'] == 'client' ? 'bg-info' : 'bg-secondary' ?>">
                                                    <?= $contract['type'] == 'client' ? 'Cliente' : 'Proveedor' ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($contract['client_supplier_name']) ?></td>
                                            <td>
                                                <span class="badge" style="background-color: <?= $contract['category_color'] ?>">
                                                    <?= htmlspecialchars($contract['category_name']) ?>
                                                </span>
                                            </td>
                                            <td><?= formatCurrency($contract['value'], $contract['currency']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($contract['start_date'])) ?></td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($contract['end_date'])) ?>
                                                <?php if ($contract['status'] == 'active'): ?>
                                                    <?php 
                                                    $days = $contract['days_until_expiry'];
                                                    if ($days <= 30 && $days > 0): 
                                                    ?>
                                                        <br><small class="text-warning">
                                                            <i class="fas fa-clock"></i> <?= $days ?> días
                                                        </small>
                                                    <?php elseif ($days <= 0): ?>
                                                        <br><small class="text-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> Vencido
                                                        </small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $statusBadges = [
                                                    'active' => 'bg-success',
                                                    'draft' => 'bg-secondary',
                                                    'pending_renewal' => 'bg-warning',
                                                    'expired' => 'bg-danger',
                                                    'terminated' => 'bg-dark'
                                                ];
                                                
                                                $statusLabels = [
                                                    'active' => 'Activo',
                                                    'draft' => 'Borrador',
                                                    'pending_renewal' => 'Pendiente',
                                                    'expired' => 'Vencido',
                                                    'terminated' => 'Terminado'
                                                ];
                                                ?>
                                                <span class="badge <?= $statusBadges[$contract['status']] ?? 'bg-secondary' ?>">
                                                    <?= $statusLabels[$contract['status']] ?? $contract['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($contract['assigned_name'] ?? 'Sin asignar') ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="contract_detail.php?id=<?= $contract['id'] ?>" 
                                                       class="btn btn-outline-primary"
                                                       data-bs-toggle="tooltip" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($auth->hasPermission('contracts.update')): ?>
                                                        <a href="contract_form.php?id=<?= $contract['id'] ?>" 
                                                           class="btn btn-outline-warning"
                                                           data-bs-toggle="tooltip" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($auth->hasPermission('contracts.delete')): ?>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger confirm-action"
                                                                data-url="api/contracts.php?action=delete&id=<?= $contract['id'] ?>"
                                                                data-method="DELETE"
                                                                data-confirm-message="¿Está seguro de eliminar este contrato?"
                                                                data-bs-toggle="tooltip" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
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
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    if ($.fn.DataTable) {
        $('#contractsTable').DataTable({
            order: [[7, 'desc']], // Ordenar por fecha de creación
            columnDefs: [
                { orderable: false, targets: -1 } // Deshabilitar orden en columna de acciones
            ],
            pageLength: 25,
            responsive: true
        });
    }
    
    // Auto-submit del formulario de filtros cuando cambian los selects
    $('select[name="category"], select[name="status"], select[name="type"]').on('change', function() {
        $(this).closest('form').submit();
    });
});

function exportContracts(format) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', format);
    
    // Crear un enlace temporal para descargar
    const link = document.createElement('a');
    link.href = 'api/export_contracts.php?' + currentUrl.searchParams.toString();
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    App.showAlert('info', 'Iniciando descarga del archivo...');
}
</script>

<?php require_once 'includes/footer.php'; ?>