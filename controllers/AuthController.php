<?php
/**
 * controllers/AuthController.php
 * Gestión de autenticación: login, logout y guardado de sesión.
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Permission.php';

class AuthController
{
    /**
     * Muestra el formulario de login (GET /login).
     */
    public function showLogin(): void
    {
        // Si ya está autenticado, redirigir al dashboard
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesa el formulario de login (POST /login).
     */
    public function login(): void
    {
        // Regenerar ID de sesión para evitar session fixation
        session_regenerate_id(true);

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'Por favor complete usuario y contraseña.';
            $this->redirect('login');
            return;
        }

        $userModel = new User();
        $user      = $userModel->authenticate($username, $password);

        if (!$user) {
            $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            $this->redirect('login');
            return;
        }

        // ── Cargar permisos del usuario ───────────────────────────────────────
        $permissionModel = new Permission();
        $permisos        = $permissionModel->getByUser((int) $user['USER_ID']);

        // ── Guardar datos en sesión ───────────────────────────────────────────
        $_SESSION['user'] = [
            'id'         => $user['USER_ID'],
            'username'   => $user['USERNAME'],
            'first_name' => $user['FIRST_NAME'],
            'last_name'  => $user['LAST_NAME'],
            'id_rol'     => $user['id_rol'],
            'full_name'  => trim($user['FIRST_NAME'] . ' ' . $user['LAST_NAME']),
        ];
        $_SESSION['permisos']   = $permisos;
        $_SESSION['logged_at']  = time();

        $this->redirect('dashboard');
    }

    /**
     * Cierra la sesión y redirige al login (GET /logout).
     */
    public function logout(): void
    {
        // Destruir datos de sesión
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->redirect('login');
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function redirect(string $route): void
    {
        $base = $this->getBaseUrl();
        header("Location: {$base}?route={$route}");
        exit;
    }

    private function getBaseUrl(): string
    {
        $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script   = dirname($_SERVER['SCRIPT_NAME']);
        $script   = rtrim($script, '/');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
