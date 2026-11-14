<?php
/**
 * controllers/AuthController.php
 * Gestión de autenticación: login, logout y guardado de sesión.
 * Dialecto PHP 5.6.
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Permission.php';

class AuthController
{
    /**
     * Muestra el formulario de login (GET /login).
     */
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard
        if (!empty($_SESSION['user'])) {
            $this->redirect('dashboard');
        }

        $error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
        unset($_SESSION['login_error']);

        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Procesa el formulario de login (POST /login).
     */
    public function login()
    {
        // Regenerar ID de sesión para evitar session fixation
        session_regenerate_id(true);

        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
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
        $_SESSION['user'] = array(
            'id'         => $user['USER_ID'],
            'username'   => $user['USERNAME'],
            'first_name' => $user['FIRST_NAME'],
            'last_name'  => $user['LAST_NAME'],
            'id_rol'     => $user['id_rol'],
            'full_name'  => trim($user['FIRST_NAME'] . ' ' . $user['LAST_NAME']),
        );
        $_SESSION['permisos']   = $permisos;
        $_SESSION['logged_at']  = time();

        $this->redirect('dashboard');
    }

    /**
     * Cierra la sesión y redirige al login (GET /logout).
     */
    public function logout()
    {
        // Destruir datos de sesión
        $_SESSION = array();

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

    /**
     * @param string $route
     */
    private function redirect($route)
    {
        $base = $this->getBaseUrl();
        header('Location: ' . $base . '?route=' . $route);
        exit;
    }

    /**
     * @return string
     */
    private function getBaseUrl()
    {
        if (function_exists('getBaseUrl')) {
            return getBaseUrl();
        }
        $https  = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
        $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
        $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $script = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        $script = rtrim($script, '/');
        return $scheme . '://' . $host . $script . '/index.php';
    }
}
