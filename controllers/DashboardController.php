<?php
/**
 * controllers/DashboardController.php
 * Panel principal del sistema — requiere sesión activa.
 */

class DashboardController
{
    /**
     * Punto de entrada al dashboard.
     * Guard: si no hay sesión activa redirige a /login.
     */
    public function index(): void
    {
        $this->requireAuth();

        // Datos disponibles para la vista
        $user     = $_SESSION['user'];
        $permisos = $_SESSION['permisos'] ?? [];

        require_once __DIR__ . '/../views/dashboard/index.php';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Verifica sesión activa; si no existe redirige al login.
     */
    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            $base = $this->getBaseUrl();
            header("Location: {$base}?route=login");
            exit;
        }
    }

    private function getBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME']);
        $script = rtrim($script, '/');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
