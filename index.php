<?php
/**
 * index.php — Front Controller / Enrutador principal
 * ─────────────────────────────────────────────────────────────────────────
 * Sistema Médico COMEDICA
 *
 * Rutas disponibles (?route=):
 *   (vacío) / dashboard  → DashboardController::index()
 *   login   (GET)        → AuthController::showLogin()
 *   login   (POST)       → AuthController::login()
 *   logout               → AuthController::logout()
 * ─────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

// ── 1. Configuración de errores (ajustar en producción) ──────────────────
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('America/Argentina/Buenos_Aires');

// ── 2. Sesión segura ─────────────────────────────────────────────────────
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── 3. CSRF token (generarlo si no existe) ────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── 4. Autoload de controladores ─────────────────────────────────────────
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/PrestadoresController.php';
require_once __DIR__ . '/controllers/ChatController.php';
require_once __DIR__ . '/controllers/RegistrfController.php';
require_once __DIR__ . '/controllers/RegistrfPdfController.php';
require_once __DIR__ . '/controllers/NomenclaController.php';
require_once __DIR__ . '/controllers/TablaGeneralController.php';
require_once __DIR__ . '/controllers/PlantillasEmailsController.php';

// ── 5. Resolución de ruta ─────────────────────────────────────────────────
$route  = trim($_GET['route'] ?? '');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── 6. Dispatch ───────────────────────────────────────────────────────────
switch ($route) {

    // ── Login ──────────────────────────────────────────────────────────────
    case 'login':
        $controller = new AuthController();
        if ($method === 'POST') {
            // Validación CSRF
            $tokenPost = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $tokenPost)) {
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
        $controller = new AuthController();
        $controller->logout();
        break;

    // ── Módulo Prestadores (Archivos → Prestadores) ───────────────────────
    case 'prestadores':
    case 'MNU_ARC_PRESTADORES':
        $controller = new PrestadoresController();
        $action     = trim($_GET['action'] ?? '');
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
            case 'pracespe_listar':         $controller->pracespeListar();          break;
            case 'pracespe_agregar':         $controller->pracespeAgregar();         break;
            case 'pracespe_grupos':         $controller->pracespeGrupos();          break;
            case 'pracespe_borrar':         $controller->pracespeBorrar();          break;
            case 'pracespe_borrar_grupo':   $controller->pracespeBorrarGrupo();     break;
            case 'pracespe_imprimir':       $controller->pracespeImprimir();        break;
            case 'pracespe_buscar_catalogo':$controller->pracespeBuscarCatalogo();  break;
            case 'adj_listar':        $controller->adjListar();         break;
            case 'adj_subir':         $controller->adjSubir();          break;
            // ── Vista principal ───────────────────────────────────────────
            default:           $controller->index();      break;
        }
        break;

    // ── Módulo Chat de Telegram ────────────────────────────────────────────
    case 'chat':
    case 'MNU_CHAT_TELEGRAM':
        $controller = new ChatController();
        $action     = trim($_GET['action'] ?? '');
        switch ($action) {
            case 'messages':    $controller->getMessages();    break;
            case 'unread':      $controller->getUnreadCount(); break;
            case 'send':        $controller->sendMessage();    break;
            case 'saveContact': $controller->saveContact();    break;
            default:            $controller->index();          break;
        }
        break;

    // ── Módulo Registración de Facturas (Carga Datos → Registro de Facturas) ──
    case 'registro-facturas':
    case 'mnu-reg-facturas':
    case 'MNU_CD_FAC_INGRESO':
    case 'MNU_REG_FACTURAS':
        $controller = new RegistrfController();
        $action     = trim($_GET['action'] ?? '');
        switch ($action) {
            case 'listado':           $controller->listado();          break;
            case 'obtener':           $controller->obtener();          break;
            case 'eliminar':          $controller->eliminar();         break;
            case 'restaurar':         $controller->restaurar();        break;
            case 'buscar_prestador':  $controller->buscarPrestador();  break;
            case 'buscar_os':         $controller->buscarObraSocial(); break;
            case 'os_prestador':      $controller->osDelPrestador();   break;
            case 'guardar':           $controller->guardar();          break;
            case 'rpt_prioritarios':  $controller->rptPrioritarios();  break;
            case 'rpt_recibos':       $controller->rptRecibos();       break;
            default:                  $controller->index();            break;
        }
        break;

    // ── Carga Automática de Facturas PDF ──────────────────────────────────
    case 'registro-facturas-pdf':
    case 'registro_facturas_pdf':
        require __DIR__ . '/registro_facturas_pdf.php';
        break;
    case 'subir-facturas-pdf':
    case 'subir_facturas_pdf':
        require __DIR__ . '/subir_facturas_pdf.php';
        break;

    // ── Módulo Nomenclador Nacional (Archivos → Nomenclador) ──────────────
    case 'nomenclador':
    case 'MNU_ARC_NOMENCLADOR':
        $controller = new NomenclaController();
        $action     = trim($_GET['action'] ?? '');
        switch ($action) {
            case 'accion': $controller->accionSensibles(); break;
            default:       $controller->index();           break;
        }
        break;

    // ── ABM tablas maestras (Archivos → Tablas / diccionario) ─────────────
    case 'tablas-generales':
    case 'mnu-arc-tablas':
    case 'MNU_ARC_TABLAS':
    case 'localidades':
    case 'zonas':
    case 'grupos-web':
    case 'empresas':
    case 'bancos':
    case 'cierre-aumentos-tabla':
    case 'tipos-certificados':
    case 'valores-venta':
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
        $controller = new TablaGeneralController();
        $action     = trim($_GET['action'] ?? '');
        switch ($action) {
            case 'guardar':  $controller->guardar();  break;
            case 'eliminar': $controller->eliminar(); break;
            case 'obtener':  $controller->obtener();  break;
            default:         $controller->index();    break;
        }
        break;

    // ── Cabecera Mails (Archivos → Tablas Generales → plantillas-emails) ──
    case 'plantillas-emails':
    case 'MNU_ARC_TAB_CABECERA_MAILS':
        $controller = new PlantillasEmailsController();
        $action     = trim($_GET['action'] ?? '');
        switch ($action) {
            case 'guardar':  $controller->guardar();  break;
            case 'editar':   $controller->editar();   break;
            case 'obtener':  $controller->obtener();  break;
            case 'eliminar': $controller->eliminar(); break;
            default:         $controller->index();    break;
        }
        break;

    // ── Dashboard (ruta vacía o explícita) ────────────────────────────────
    case '':
    case 'dashboard':
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
            ? [['label' => $moduloLabel]]
            : [['label' => 'Error 404']];

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
function getBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    $script = rtrim($script, '/');
    return "{$scheme}://{$host}{$script}/index.php";
}
