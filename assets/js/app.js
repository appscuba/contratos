/**
 * Sistema de Gestión de Contratos
 * JavaScript principal de la aplicación
 */

// Configuración global
const App = {
    config: {
        baseUrl: window.location.origin,
        apiUrl: window.location.origin + '/api',
        dateFormat: 'DD/MM/YYYY',
        currencySymbol: '$',
        language: 'es'
    },
    
    // Estado de la aplicación
    state: {
        sidebarCollapsed: false,
        currentUser: null,
        notifications: [],
        contracts: []
    },
    
    // Inicialización
    init: function() {
        this.setupEventListeners();
        this.initializeComponents();
        this.loadInitialData();
    },
    
    // Configurar event listeners
    setupEventListeners: function() {
        // Toggle sidebar
        $('#sidebarToggle').on('click', this.toggleSidebar.bind(this));
        
        // Cerrar sidebar en móvil al hacer clic en overlay
        $(document).on('click', '.sidebar-overlay', this.closeSidebar.bind(this));
        
        // Formularios con AJAX
        $(document).on('submit', '.ajax-form', this.handleAjaxForm.bind(this));
        
        // Botones de confirmación
        $(document).on('click', '.confirm-action', this.handleConfirmAction.bind(this));
        
        // Búsqueda global
        $('#globalSearch').on('input', this.debounce(this.handleGlobalSearch.bind(this), 300));
        
        // Notificaciones
        $(document).on('click', '.mark-notification-read', this.markNotificationRead.bind(this));
        
        // Tooltips dinámicos
        $(document).on('mouseenter', '[data-bs-toggle="tooltip"]', function() {
            if (!$(this).data('bs.tooltip')) {
                new bootstrap.Tooltip(this);
                $(this).tooltip('show');
            }
        });
    },
    
    // Inicializar componentes
    initializeComponents: function() {
        // DataTables configuración por defecto
        if ($.fn.DataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                responsive: true,
                pageLength: 25,
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                pagingType: 'full_numbers'
            });
        }
        
        // Configurar moment.js
        if (typeof moment !== 'undefined') {
            moment.locale('es');
        }
        
        // Inicializar gráficos si Chart.js está disponible
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
            Chart.defaults.color = '#6c757d';
        }
    },
    
    // Cargar datos iniciales
    loadInitialData: function() {
        this.loadNotifications();
        this.checkContractExpirations();
    },
    
    // Toggle sidebar
    toggleSidebar: function() {
        const sidebar = $('#sidebar');
        const mainContent = $('#mainContent');
        
        if (window.innerWidth <= 991.98) {
            // Móvil: mostrar/ocultar sidebar
            sidebar.toggleClass('show');
            if (sidebar.hasClass('show')) {
                $('body').append('<div class="sidebar-overlay"></div>');
            } else {
                $('.sidebar-overlay').remove();
            }
        } else {
            // Desktop: colapsar sidebar
            sidebar.toggleClass('collapsed');
            mainContent.toggleClass('collapsed');
            this.state.sidebarCollapsed = sidebar.hasClass('collapsed');
            
            // Guardar estado en localStorage
            localStorage.setItem('sidebarCollapsed', this.state.sidebarCollapsed);
        }
    },
    
    // Cerrar sidebar en móvil
    closeSidebar: function() {
        $('#sidebar').removeClass('show');
        $('.sidebar-overlay').remove();
    },
    
    // Manejar formularios AJAX
    handleAjaxForm: function(e) {
        e.preventDefault();
        
        const form = $(e.target);
        const url = form.attr('action') || window.location.href;
        const method = form.attr('method') || 'POST';
        const formData = new FormData(form[0]);
        
        // Mostrar loading
        const submitBtn = form.find('[type="submit"]');
        const originalText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<span class="loading"></span> Procesando...');
        
        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    App.showAlert('success', response.message || 'Operación completada exitosamente');
                    
                    // Recargar datos si es necesario
                    if (response.reload) {
                        setTimeout(() => location.reload(), 1000);
                    }
                    
                    // Resetear formulario si es necesario
                    if (response.reset) {
                        form[0].reset();
                    }
                    
                    // Cerrar modal si existe
                    const modal = form.closest('.modal');
                    if (modal.length) {
                        bootstrap.Modal.getInstance(modal[0]).hide();
                    }
                } else {
                    App.showAlert('error', response.message || 'Error al procesar la solicitud');
                }
            },
            error: function(xhr) {
                let message = 'Error interno del servidor';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                App.showAlert('error', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    },
    
    // Manejar acciones de confirmación
    handleConfirmAction: function(e) {
        e.preventDefault();
        
        const element = $(e.target).closest('.confirm-action');
        const message = element.data('confirm-message') || '¿Estás seguro de realizar esta acción?';
        const url = element.attr('href') || element.data('url');
        const method = element.data('method') || 'GET';
        
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
            if (result.isConfirmed) {
                if (method === 'GET') {
                    window.location.href = url;
                } else {
                    $.ajax({
                        url: url,
                        method: method,
                        success: function(response) {
                            if (response.success) {
                                App.showAlert('success', response.message);
                                if (response.reload) {
                                    setTimeout(() => location.reload(), 1000);
                                }
                            } else {
                                App.showAlert('error', response.message);
                            }
                        },
                        error: function() {
                            App.showAlert('error', 'Error al procesar la solicitud');
                        }
                    });
                }
            }
        });
    },
    
    // Búsqueda global
    handleGlobalSearch: function(e) {
        const query = $(e.target).val().trim();
        
        if (query.length < 3) {
            $('#searchResults').hide();
            return;
        }
        
        $.get('/api/search.php', { q: query }, function(response) {
            if (response.success && response.results.length > 0) {
                App.displaySearchResults(response.results);
            } else {
                $('#searchResults').hide();
            }
        });
    },
    
    // Mostrar resultados de búsqueda
    displaySearchResults: function(results) {
        const container = $('#searchResults');
        let html = '<div class="list-group">';
        
        results.forEach(function(result) {
            html += `
                <a href="${result.url}" class="list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${result.title}</h6>
                        <small class="text-muted">${result.type}</small>
                    </div>
                    <p class="mb-1">${result.description}</p>
                </a>
            `;
        });
        
        html += '</div>';
        container.html(html).show();
    },
    
    // Cargar notificaciones
    loadNotifications: function() {
        $.get('/api/notifications.php', function(response) {
            if (response.success) {
                App.state.notifications = response.notifications;
                App.updateNotificationUI(response.unread_count);
            }
        }).fail(function() {
            console.error('Error loading notifications');
        });
    },
    
    // Actualizar UI de notificaciones
    updateNotificationUI: function(unreadCount) {
        const badges = $('#notificationCount, #sidebarNotificationCount');
        
        if (unreadCount > 0) {
            badges.text(unreadCount).show();
        } else {
            badges.hide();
        }
    },
    
    // Marcar notificación como leída
    markNotificationRead: function(e) {
        e.preventDefault();
        
        const notificationId = $(e.target).data('notification-id');
        
        $.post('/api/notifications.php', {
            action: 'mark_read',
            id: notificationId
        }, function(response) {
            if (response.success) {
                $(e.target).closest('.notification-item').addClass('read');
                App.loadNotifications(); // Recargar contador
            }
        });
    },
    
    // Verificar contratos próximos a vencer
    checkContractExpirations: function() {
        $.get('/api/contracts.php?action=check_expirations', function(response) {
            if (response.success && response.expiring_contracts.length > 0) {
                const count = response.expiring_contracts.length;
                const message = `Tienes ${count} contrato${count > 1 ? 's' : ''} próximo${count > 1 ? 's' : ''} a vencer`;
                
                App.showAlert('warning', message, 'Contratos por vencer');
            }
        });
    },
    
    // Formatear moneda
    formatCurrency: function(amount, currency = 'USD') {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'MXN': '$',
            'GBP': '£'
        };
        
        const symbol = symbols[currency] || currency;
        return symbol + parseFloat(amount).toLocaleString('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    // Formatear fecha
    formatDate: function(date, format = null) {
        if (!moment) return date;
        
        format = format || this.config.dateFormat;
        return moment(date).format(format);
    },
    
    // Calcular días hasta una fecha
    daysUntil: function(date) {
        if (!moment) return null;
        
        const target = moment(date);
        const now = moment();
        return target.diff(now, 'days');
    },
    
    // Mostrar alertas
    showAlert: function(type, message, title = null) {
        const icons = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };
        
        const titles = {
            success: 'Éxito',
            error: 'Error',
            warning: 'Advertencia',
            info: 'Información'
        };
        
        Swal.fire({
            icon: icons[type] || 'info',
            title: title || titles[type] || 'Notificación',
            text: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    },
    
    // Debounce function
    debounce: function(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // Crear gráfico de dona
    createDoughnutChart: function(ctx, data, options = {}) {
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };
        
        return new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: { ...defaultOptions, ...options }
        });
    },
    
    // Crear gráfico de barras
    createBarChart: function(ctx, data, options = {}) {
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };
        
        return new Chart(ctx, {
            type: 'bar',
            data: data,
            options: { ...defaultOptions, ...options }
        });
    },
    
    // Crear gráfico de líneas
    createLineChart: function(ctx, data, options = {}) {
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        };
        
        return new Chart(ctx, {
            type: 'line',
            data: data,
            options: { ...defaultOptions, ...options }
        });
    }
};

// Inicializar aplicación cuando el DOM esté listo
$(document).ready(function() {
    App.init();
    
    // Restaurar estado del sidebar
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    if (sidebarCollapsed === 'true' && window.innerWidth > 991.98) {
        $('#sidebar').addClass('collapsed');
        $('#mainContent').addClass('collapsed');
        App.state.sidebarCollapsed = true;
    }
});

// Exponer App globalmente para uso en otras páginas
window.App = App;