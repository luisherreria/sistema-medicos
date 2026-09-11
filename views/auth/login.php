<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COMEDICA — Iniciar Sesión</title>
    <link rel="icon" type="image/x-icon"  href="/sistema-medicos/public/img/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/sistema-medicos/public/img/favicon-gen.php">

    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* ── Fondo degradado corporativo ─────────────────────────────────── */
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1 0%, #1565c0 40%, #1976d2 70%, #42a5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        /* ── Card contenedor ─────────────────────────────────────────────── */
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        /* ── Cabecera azul con logo ───────────────────────────────────────── */
        .login-header {
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            padding: 32px 24px 24px;
            text-align: center;
        }

        .login-header .logo-wrap {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: #fff;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }

        .login-header .logo-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-header h4 {
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin: 0 0 4px;
        }

        .login-header p {
            color: rgba(255,255,255,0.75);
            font-size: 0.82rem;
            margin: 0;
        }

        /* ── Cuerpo del formulario ───────────────────────────────────────── */
        .login-body {
            padding: 28px 32px 32px;
        }

        .login-body .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #37474f;
        }

        .login-body .input-group-text {
            background: #e3f2fd;
            border-right: none;
            color: #1565c0;
        }

        .login-body .form-control {
            border-left: none;
        }

        .login-body .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(21, 101, 192, 0.25);
            border-color: #1565c0;
        }

        .btn-login {
            background: linear-gradient(135deg, #0d47a1, #1976d2);
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            padding: 10px;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.88;
        }

        /* ── Alerta de error ──────────────────────────────────────────────── */
        .alert-login {
            font-size: 0.875rem;
            border-radius: 8px;
        }

        /* ── Footer de la card ────────────────────────────────────────────── */
        .login-footer {
            background: #f5f7fb;
            border-top: 1px solid #e0e6ef;
            padding: 12px 20px;
            text-align: center;
            font-size: 0.75rem;
            color: #90a4ae;
        }

        /* ── Reloj login ─────────────────────────────────────────────────── */
        #login-clock {
            font-size: 0.78rem;
            color: #78909c;
            margin-top: 6px;
        }
    </style>
</head>
<body>

<div class="login-card">

    <!-- Cabecera con logo COMEDICA -->
    <div class="login-header">
        <div class="logo-wrap">
            <img src="/sistema-medicos/public/img/logo.jfif"
                 alt="COMEDICA"
                 onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\'fa-solid fa-hospital fa-3x\' style=\'color:#1565c0\'></i>';">
        </div>
        <h4>COMEDICA</h4>
        <p>Sistema de Gestión Médica</p>
    </div>

    <!-- Cuerpo: formulario -->
    <div class="login-body">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-login d-flex align-items-center gap-2" role="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=login" autocomplete="off" novalidate>

            <!-- CSRF token simple -->
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

            <!-- Usuario -->
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fa-solid fa-user me-1 text-primary"></i>Usuario
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text"
                           id="username"
                           name="username"
                           class="form-control"
                           placeholder="Ingrese su usuario"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autofocus
                           required>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="fa-solid fa-lock me-1 text-primary"></i>Contraseña
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Ingrese su contraseña"
                           required>
                    <button class="btn btn-outline-secondary"
                            type="button"
                            id="togglePass"
                            title="Mostrar/Ocultar contraseña">
                        <i class="fa-solid fa-eye" id="togglePassIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Botón ingresar -->
            <button type="submit" class="btn btn-login btn-primary w-100 text-white">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Ingresar al Sistema
            </button>

        </form>

        <div id="login-clock" class="text-center"></div>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> COMEDICA &mdash; Todos los derechos reservados
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ── Mostrar/ocultar contraseña ────────────────────────────────────────
    document.getElementById('togglePass').addEventListener('click', function () {
        const pwd  = document.getElementById('password');
        const icon = document.getElementById('togglePassIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });

    // ── Reloj en el login ─────────────────────────────────────────────────
    const dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
    const meses  = ['enero','febrero','marzo','abril','mayo','junio',
                    'julio','agosto','septiembre','octubre','noviembre','diciembre'];

    function actualizarRelojLogin() {
        const now = new Date();
        const hh  = String(now.getHours()).padStart(2, '0');
        const mm  = String(now.getMinutes()).padStart(2, '0');
        const ss  = String(now.getSeconds()).padStart(2, '0');
        const dia = dias[now.getDay()];
        const d   = now.getDate();
        const mes = meses[now.getMonth()];
        const yr  = now.getFullYear();
        document.getElementById('login-clock').textContent =
            `${hh}:${mm}:${ss} | ${dia}, ${d} de ${mes} de ${yr}`;
    }

    actualizarRelojLogin();
    setInterval(actualizarRelojLogin, 1000);
</script>

</body>
</html>
