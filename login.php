<?php
$pageTitle = 'Iniciar Sesión';
require_once 'includes/header.php';

// Si ya está logueado, redirigir al dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($username) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        if ($auth->login($username, $password)) {
            // Configurar cookie "recordar" si está marcado
            if ($remember) {
                $token = generateToken();
                // Aquí podrías guardar el token en la base de datos
                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            }
            
            // Redirigir al dashboard
            header('Location: index.php');
            exit;
        } else {
            $error = 'Credenciales incorrectas. Por favor, verifique su usuario y contraseña.';
        }
    }
}
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="login-card">
                <div class="login-header">
                    <i class="fas fa-file-contract fa-3x mb-3"></i>
                    <h3>Sistema de Contratos</h3>
                    <p class="mb-0">Ingrese sus credenciales para acceder</p>
                </div>
                
                <div class="login-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Usuario o Email
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="username" 
                                   name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   placeholder="Ingrese su usuario o email"
                                   required>
                            <div class="invalid-feedback">
                                Por favor, ingrese su usuario o email.
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Ingrese su contraseña"
                                       required>
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="togglePassword"
                                        data-bs-toggle="tooltip" 
                                        title="Mostrar/Ocultar contraseña">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">
                                Por favor, ingrese su contraseña.
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="remember" 
                                   name="remember"
                                   <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="remember">
                                Recordar mis datos
                            </label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Iniciar Sesión
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Para acceder use: <strong>admin/password</strong>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Información del sistema -->
            <div class="text-center mt-4">
                <small class="text-white-50">
                    <?= APP_NAME ?> v<?= APP_VERSION ?><br>
                    © <?= date('Y') ?> - Todos los derechos reservados
                </small>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Validación de formulario
    const form = document.querySelector('.needs-validation');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    });
    
    // Toggle mostrar/ocultar contraseña
    $('#togglePassword').on('click', function() {
        const password = $('#password');
        const icon = $(this).find('i');
        
        if (password.attr('type') === 'password') {
            password.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            password.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Auto-focus al primer campo
    $('#username').focus();
    
    // Efecto de escritura en el título (opcional)
    let title = "Sistema de Gestión de Contratos";
    let index = 0;
    
    function typeWriter() {
        if (index < title.length) {
            document.title = title.substring(0, index + 1);
            index++;
            setTimeout(typeWriter, 100);
        }
    }
    
    // typeWriter(); // Descomenta si quieres el efecto
});

// Prevenir ataques de fuerza bruta básicos
let loginAttempts = 0;
const maxAttempts = 5;

function handleFailedLogin() {
    loginAttempts++;
    
    if (loginAttempts >= maxAttempts) {
        $('#username, #password, button[type="submit"]').prop('disabled', true);
        $('.login-body').prepend(`
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-ban me-2"></i>
                Demasiados intentos fallidos. Recargue la página para intentar nuevamente.
            </div>
        `);
    }
}

// Si hay error, incrementar contador
<?php if ($error): ?>
handleFailedLogin();
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>