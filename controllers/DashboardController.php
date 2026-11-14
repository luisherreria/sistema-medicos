<?php
/**
 * controllers/DashboardController.php
 * Panel principal del sistema — requiere sesión activa.
 * Dialecto PHP 5.6.
 */

class DashboardController
{
    /**
     * Punto de entrada al dashboard.
     * Guard: si no hay sesión activa redirige a /login.
     */
    public function index()
    {
        $this->requireAuth();

        // Datos disponibles para la vista
        $user     = $_SESSION['user'];
        $permisos = isset($_SESSION['permisos']) ? $_SESSION['permisos'] : array();

        require_once __DIR__ . '/../views/dashboard/index.php';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Verifica sesión activa; si no existe redirige al login.
     */
    private function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            $base = $this->getBaseUrl();
            header('Location: ' . $base . '?route=login');
            exit;
        }
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
