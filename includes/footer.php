<?php
$currentPage = basename($_SERVER['PHP_SELF']);
if ($currentPage !== 'login.php'): ?>
    </div> <!-- End main-content -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="text-muted">© <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?></span>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Última actualización: <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Moment.js para fechas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/es.min.js"></script>

<!-- Scripts personalizados -->
<script src="assets/js/app.js"></script>

<?php if ($currentPage !== 'login.php'): ?>
<script>
// Configuración global
window.APP_CONFIG = {
    baseUrl: '<?= APP_URL ?>',
    currentUser: <?= json_encode($user) ?>,
    hasPermission: function(permission) {
        // Implementar verificación de permisos en frontend si es necesario
        return true;
    }
};

// Inicializar componentes
$(document).ready(function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar moment.js en español
    moment.locale('es');
    
    // Cargar notificaciones
    loadNotifications();
    
    // Actualizar notificaciones cada 5 minutos
    setInterval(loadNotifications, 300000);
});

function loadNotifications() {
    $.get('api/notifications.php', function(data) {
        if (data.success) {
            const count = data.unread_count;
            $('#notificationCount, #sidebarNotificationCount').text(count);
            
            if (count === 0) {
                $('#notificationCount, #sidebarNotificationCount').hide();
            } else {
                $('#notificationCount, #sidebarNotificationCount').show();
            }
        }
    }).fail(function() {
        console.error('Error loading notifications');
    });
}

// Función para mostrar alertas
function showAlert(type, message, title = null) {
    Swal.fire({
        icon: type,
        title: title || (type === 'success' ? 'Éxito' : type === 'error' ? 'Error' : 'Información'),
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// Función para confirmar acciones
function confirmAction(message, callback) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed && typeof callback === 'function') {
            callback();
        }
    });
}
</script>
<?php endif; ?>

</body>
</html>