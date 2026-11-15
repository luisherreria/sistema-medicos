<?php
/**
 * index.php — Front Controller / Enrutador principal
 * ─────────────────────────────────────────────────────────────────────────
 * Sistema Médico COMEDICA
 *
 * Dialecto PHP 5.6 (hosting). Sin declare(strict_types), sin ??, sin
 * random_bytes(), sin tipos 7.x. Los controllers se cargan dentro del
 * case de la ruta para que Prestadores/Chat 7.4 no rompan login.
 *
 * Rutas disponibles (?route=):
 *   (vacío) / dashboard  → DashboardController::index()
 *   login   (GET)        → AuthController::showLogin()
 *   login   (POST)       → AuthController::login()
 *   logout               → AuthController::logout()
 *   audit-facturacion    → AuditaFacturacionController (AUDIFACT → SELEFACT → AUDITA)
 *   cierre-automatico    → AuditPadController (autscerr)
 *   liq-auditar          → AuditPadController (liqaaud)
 *   liq-sin-cerrar       → AuditPadController (LIQSCERR)
 *   reimp-liquidaciones  → AuditPadController (reaudifact)
 *   envio-debitos        → AuditPadController (avideb)
 * ─────────────────────────────────────────────────────────────────────────
 */

// ── 1. Configuración de errores (ajustar en producción) ──────────────────
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (!function_exists('hash_equals')) {
    function hash_equals($known, $user)
    {
        $known = (string) $known;
        $user  = (string) $user;
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $res = 0;
        $i = 0;
        $n = strlen($known);
        for ($i = 0; $i < $n; $i++) {
            $res |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $res === 0;
    }
}
if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 0);
}

// ── 2. Sesión segura ─────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/schema.php';

// ── 3. CSRF token (generarlo si no existe) ────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    } else {
        $_SESSION['csrf_token'] = md5(uniqid((string) mt_rand(), true));
    }
}

// ── 4. Resolución de ruta (lazy-load de controllers en cada case) ────────
$route  = isset($_GET['route']) ? trim($_GET['route']) : '';
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

$routeAliases = array(
    'mnu_aud_auditar'        => 'audit-facturacion',
    'mnu-aud-auditar'        => 'audit-facturacion',
    'mnu_af_auditar'         => 'audit-facturacion',
    'mnu-af-auditar'         => 'audit-facturacion',
    'MNU_AUD_AUDITAR'        => 'audit-facturacion',
    'MNU_AF_AUDITAR'         => 'audit-facturacion',
    'auditar-facturacion'    => 'audit-facturacion',
    'audita'                 => 'audit-facturacion',
    'mnu-af-cierre-auto'     => 'cierre-automatico',
    'mnu_af_cierre_auto'     => 'cierre-automatico',
    'MNU_AF_CIERRE_AUTO'     => 'cierre-automatico',
    'cierre-automatico'      => 'cierre-automatico',
    'mnu-af-liq-auditar'     => 'liq-auditar',
    'mnu_af_liq_auditar'     => 'liq-auditar',
    'MNU_AF_LIQ_AUDITAR'     => 'liq-auditar',
    'mnu-af-liq-sin-cerrar'  => 'liq-sin-cerrar',
    'mnu_af_liq_sin_cerrar'  => 'liq-sin-cerrar',
    'MNU_AF_LIQ_SIN_CERRAR'  => 'liq-sin-cerrar',
    'mnu-af-reimp-liq'       => 'reimp-liquidaciones',
    'mnu_af_reimp_liq'       => 'reimp-liquidaciones',
    'MNU_AF_REIMP_LIQ'       => 'reimp-liquidaciones',
    'mnu-af-env-debito'      => 'envio-debitos',
    'mnu_af_env_debito'      => 'envio-debitos',
    'MNU_AF_ENV_DEBITO'      => 'envio-debitos',
);
if (isset($routeAliases[$route])) {
    $route = $routeAliases[$route];
} else {
    $norm = strtolower(str_replace('_', '-', $route));
    if (isset($routeAliases[$norm])) {
        $route = $routeAliases[$norm];
    }
}

/**
 * Carga un controller solo cuando la ruta lo necesita.
 *
 * @param string $file Nombre de archivo dentro de controllers/
 */
function requireController($file)
{
    require_once __DIR__ . '/controllers/' . $file;
}

// ── 5. Dispatch ───────────────────────────────────────────────────────────
switch ($route) {

    // ── Login ──────────────────────────────────────────────────────────────
    case 'login':
        requireController('AuthController.php');
        $controller = new AuthController();
        if ($method === 'POST') {
            $tokenPost = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
            $tokenSess = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';
            if ($tokenSess === '' || $tokenPost === '' || !hash_equals($tokenSess, $tokenPost)) {
                $_SESSION['login_error'] = 'Token de seguridad inválido. Recargue la página.';
                header('Location: ' . getBaseUrl() . '?route=login');
                exit;
            }
            $controller->login();
        } else {
            $controller->showLogin();
        }
        break;

    // ── Logout ─────────────────────────────────────────────────────────────
    case 'logout':
        requireController('AuthController.php');
        $controller = new AuthController();
        $controller->logout();
        break;

    // ── Módulo Prestadores (Archivos → Prestadores) ───────────────────────
    case 'prestadores':
    case 'MNU_ARC_PRESTADORES':
        requireController('PrestadoresController.php');
        $controller = new PrestadoresController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            // ── AJAX: Grilla principal ────────────────────────────────────
            case 'listado':    $controller->listado();    break; // GET: listado paginado
            case 'categorias': $controller->categorias(); break; // GET: lista de categorías
            // ── AJAX: Obras Sociales Habilitadas ──────────────────────────
            case 'os_list':           $controller->osListar();          break;
            case 'os_obrasoc':        $controller->osObrasoc();         break;
            case 'os_add':            $controller->osAgregar();         break;
            case 'os_edit':           $controller->osEditar();          break;
            case 'os_baja':           $controller->osBaja();            break;
            case 'prestador_get':     $controller->prestadorGet();      break;
            case 'prestador_guardar': $controller->prestadorGuardar();  break;
            case 'prestador_catalogo':$controller->prestadorCatalogo(); break;
            case 'prestador_defaults':$controller->prestadorDefaults(); break;
            case 'obs_get':           $controller->obsGet();            break;
            case 'obs_save':          $controller->obsSave();           break;
            case 'infoliq_get':       $controller->infoliqGet();        break;
            case 'infoliq_save':      $controller->infoliqSave();       break;
            case 'suc_listar':        $controller->sucListar();         break;
            case 'suc_guardar':       $controller->sucGuardar();        break;
            case 'suc_borrar':        $controller->sucBorrar();         break;
            case 'suc_defaults':      $controller->sucDefaults();       break;
            case 'suc_exclu_listar':  $controller->sucExcluListar();    break;
            case 'suc_exclu_toggle':  $controller->sucExcluToggle();    break;
            case 'suc_prac_listar':   $controller->sucPracListar();     break;
            case 'adj_listar':        $controller->adjListar();         break;
            case 'adj_subir':         $controller->adjSubir();          break;
            // ── Vista principal ───────────────────────────────────────────
            default:           $controller->index();      break;
        }
        break;

    // ── Módulo Chat de Telegram ────────────────────────────────────────────
    case 'chat':
    case 'MNU_CHAT_TELEGRAM':
        requireController('ChatController.php');
        $controller = new ChatController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            case 'messages':    $controller->getMessages();    break;
            case 'unread':      $controller->getUnreadCount(); break;
            case 'send':        $controller->sendMessage();    break;
            case 'saveContact': $controller->saveContact();    break;
            default:            $controller->index();          break;
        }
        break;

    // ── Audit. Facturac. → Auditar Facturación (form AUDITA / audifac2) ──
    case 'audit-facturacion':
    case 'MNU_AF_AUDITAR':
    case 'MNU_AUD_AUDITAR':
    case 'mnu_aud_auditar':
    case 'mnu-aud-auditar':
        requireController('AuditaFacturacionController.php');
        $controller = new AuditaFacturacionController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            case 'listado':          $controller->listado();         break;
            case 'recalc':           $controller->recalc();          break;
            case 'visualizar':       $controller->visualizar();      break;
            case 'marcar_todo':      $controller->marcarTodo();      break;
            case 'marcar_todo_a':    $controller->marcarTodoA();     break;
            case 'toggle_coselecc':  $controller->toggleCoselecc();  break;
            case 'do_form':          $controller->doForm();          break;
            case 'cierre':           $controller->cierre();          break;
            case 'newcons':          $controller->guardarNewcons();  break;
            case 'cambpres':         $controller->guardarCambpres(); break;
            case 'ajusfact':         $controller->guardarAjusfact(); break;
            case 'ajusfafi':         $controller->guardarAjusfafi(); break;
            case 'cambafil':         $controller->guardarCambafil(); break;
            case 'debitar':          $controller->guardarDebitar();  break;
            case 'buscar_prestador': $controller->buscarPrestador(); break;
            default:                 $controller->index();           break;
        }
        break;

    // ── Audit. Facturac. pad (autscerr / liqaaud / LIQSCERR / reaudifact / avideb)
    case 'cierre-automatico':
    case 'MNU_AF_CIERRE_AUTO':
    case 'liq-auditar':
    case 'MNU_AF_LIQ_AUDITAR':
    case 'liq-sin-cerrar':
    case 'MNU_AF_LIQ_SIN_CERRAR':
    case 'reimp-liquidaciones':
    case 'MNU_AF_REIMP_LIQ':
    case 'envio-debitos':
    case 'MNU_AF_ENV_DEBITO':
        requireController('AuditPadController.php');
        $controller = new AuditPadController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            case 'accion': $controller->accion(); break;
            default:       $controller->index();  break;
        }
        break;

    // ── Tablas generales → Cabecera Mails (t_plantillas_emails) ───────────
    case 'cabecera-mails':
    case 'plantillas-emails':
    case 'MNU_ARC_TAB_CABECERA_MAILS':
        requireController('PlantillasEmailsController.php');
        $controller = new PlantillasEmailsController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            case 'listado':  $controller->listado();  break;
            case 'obtener':  $controller->obtener();  break;
            case 'guardar':  $controller->guardar();  break;
            case 'eliminar': $controller->eliminar(); break;
            default:         $controller->index();    break;
        }
        break;

    // ── ABM tablas maestras (Archivos → Tablas Generales) ─────────────────
    case 'tablas-generales':
    case 'mnu-arc-tablas':
    case 'MNU_ARC_TABLAS':
    case 'localidades':
    case 'zonas':
    case 'grupos-web':
    case 'empresas':
    case 'bancos':
    case 'cierre-aumentos-tabla':
    case 'pct-aumento':
    case 'tipos-certificados':
    case 'valores-venta':
    case 'control-web':
    case 'url-referencias':
    case 'tipos-cx':
    case 'subcategorias':
    case 'homologacion':
    case 'grupos-autorizaciones':
    case 'motivos-debitos':
    case 'subgrupos-nomenclador':
    case 'ajustes-impuestos':
    case 'ajustes-afiliados':
    case 'textos-rechazo':
    case 'grupos-cartilla':
    case 'grupos-nomenclador':
    case 'titulo-cartilla':
    case 'diagnosticos':
    case 'conceptos-internacion':
    case 'procesos-internacion':
    case 'reportes-leyendas':
        requireController('TablaGeneralController.php');
        $controller = new TablaGeneralController();
        $action     = isset($_GET['action']) ? trim($_GET['action']) : '';
        switch ($action) {
            case 'guardar':  $controller->guardar();  break;
            case 'eliminar': $controller->eliminar(); break;
            case 'obtener':  $controller->obtener();  break;
            default:         $controller->index();    break;
        }
        break;

    // ── Dashboard (ruta vacía o explícita) ────────────────────────────────
    case '':
    case 'dashboard':
        requireController('DashboardController.php');
        $controller = new DashboardController();
        $controller->index();
        break;

    // ── Ruta desconocida: módulo en implementación o 404 ────────────────
    default:
        if (empty($_SESSION['user'])) {
            header('Location: ' . getBaseUrl() . '?route=login');
            exit;
        }

        require_once __DIR__ . '/models/Permission.php';

        // 1. Buscar el módulo por route slug en la sesión del usuario
        //    (incluye NOMBRE_PERMISO real de la BD y su ícono)
        $moduloLabel = Permission::getLabelForRoute($route);
        $moduloIcono = Permission::getIconForRoute($route);

        // 2. Si no está en permisos del usuario, el route podría ser una CLAVE directa
        //    (ej: el usuario escribió ?route=MNU_ARC_PRESTADORES en la barra)
        if ($moduloLabel === null && !empty($_SESSION['permisos'])) {
            foreach ($_SESSION['permisos'] as $p) {
                if (isset($p['CLAVE']) && $p['CLAVE'] === $route) {
                    $moduloLabel = isset($p['NOMBRE_PERMISO']) ? $p['NOMBRE_PERMISO'] : $route;
                    $moduloIcono = Permission::iconoPorClave($p['CLAVE'], isset($p['CLAVE_CATEGORIA']) ? $p['CLAVE_CATEGORIA'] : '');
                    // Redirigir a la URL limpia si existe mapeo
                    $rutaLimpia = Permission::getRoute($p['CLAVE']);
                    if ($rutaLimpia !== $route) {
                        header('Location: ' . getBaseUrl() . '?route=' . urlencode($rutaLimpia));
                        exit;
                    }
                    break;
                }
            }
        }

        $esModuloConocido = ($moduloLabel !== null);
        http_response_code($esModuloConocido ? 200 : 404);

        $pageTitle  = $esModuloConocido
            ? 'COMEDICA — ' . $moduloLabel
            : 'COMEDICA — Página no encontrada';
        $breadcrumb = $esModuloConocido
            ? array(array('label' => $moduloLabel))
            : array(array('label' => 'Error 404'));

        require_once __DIR__ . '/views/layouts/header.php';

        if ($esModuloConocido): ?>

        <!-- ── Módulo en implementación ──────────────────────────────── -->
        <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="card border-0 shadow" style="border-radius:16px; overflow:hidden;">
                <!-- Franja de color -->
                <div style="height:5px;background:linear-gradient(90deg,#0d47a1,#42a5f5,#0d47a1);"></div>

                <div class="card-body text-center py-5 px-4"
                     style="background:linear-gradient(160deg,#f0f7ff 0%,#fff 55%,#f5f0ff 100%);">

                    <!-- Ícono del módulo -->
                    <div class="mb-3 mx-auto d-flex align-items-center justify-content-center"
                         style="width:82px;height:82px;border-radius:50%;
                                background:linear-gradient(135deg,#e3f2fd,#bbdefb);
                                box-shadow:0 6px 20px rgba(21,101,192,.2);">
                        <i class="<?= htmlspecialchars($moduloIcono) ?> fa-2x"
                           style="color:#1565c0;"></i>
                    </div>

                    <!-- Nombre del módulo -->
                    <h4 class="fw-bold text-primary mb-1">
                        <?= htmlspecialchars($moduloLabel) ?>
                    </h4>
                    <p class="text-muted mb-3" style="font-size:0.9rem;">
                        <i class="fa-solid fa-wrench me-1 text-warning"></i>
                        <em>Módulo en proceso de implementación</em>
                    </p>

                    <!-- Barra de progreso decorativa -->
                    <div class="mx-auto mb-4" style="max-width:340px;">
                        <div class="d-flex justify-content-between mb-1"
                             style="font-size:0.73rem;color:#78909c;">
                            <span>Migración VFP → Web</span><span>En curso</span>
                        </div>
                        <div class="progress" style="height:7px;border-radius:20px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 style="width:40%;"></div>
                        </div>
                    </div>

                    <!-- Alert informativo -->
                    <div class="alert alert-info d-inline-flex align-items-start gap-2 text-start mb-4"
                         style="border-radius:10px;font-size:0.83rem;max-width:480px;">
                        <i class="fa-solid fa-circle-info fa-lg mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Próximamente disponible</strong><br>
                            Este módulo está siendo migrado desde Visual FoxPro.
                            Los formularios y datos estarán disponibles en breve.
                            <br><small class="text-muted">Ruta: <code><?= htmlspecialchars($route) ?></code></small>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="index.php?route=dashboard" class="btn btn-primary">
                            <i class="fa-solid fa-gauge-high me-1"></i>Volver al Dashboard
                        </a>
                        <button onclick="history.back()" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-1"></i>Volver atrás
                        </button>
                    </div>
                </div>
            </div>

        </div>
        </div>

        <?php else: ?>

        <!-- ── 404 real ───────────────────────────────────────────────── -->
        <div class="text-center py-5">
            <i class="fa-solid fa-triangle-exclamation text-warning"
               style="font-size:4rem;"></i>
            <h3 class="mt-3 text-muted">404 — Página no encontrada</h3>
            <p class="text-muted">
                La ruta <code><?= htmlspecialchars($route) ?></code>
                no existe en este sistema.
            </p>
            <a href="index.php?route=dashboard" class="btn btn-primary mt-2">
                <i class="fa-solid fa-house me-1"></i>Ir al Dashboard
            </a>
        </div>

        <?php endif;

        require_once __DIR__ . '/views/layouts/footer.php';
        break;
}

// ── Helper: URL base del proyecto ─────────────────────────────────────────
function getBaseUrl()
{
    $https  = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
    $scheme = (!empty($https) && $https !== 'off') ? 'https' : 'http';
    $host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $script = rtrim($script, '/');
    return $scheme . '://' . $host . $script . '/index.php';
}
