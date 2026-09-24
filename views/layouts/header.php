<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'COMEDICA — Sistema Médico') ?></title>

    <!-- Favicon: usa .ico si existe, fallback al SVG dinámico -->
    <!-- Favicon oficial COMEDICA -->
    <link rel="icon" type="image/x-icon" href="/sistema-medicos/public/img/favicon.ico?v=1.0">
    <link rel="shortcut icon" type="image/x-icon" href="/sistema-medicos/public/img/favicon.ico?v=1.0">
    <!-- <link rel="icon" type="image/svg+xml" href="/sistema-medicos/public/img/favicon-gen.php"> -->


    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- DataTables 1.13 + Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <style>
        /* ── Layout base ──────────────────────────────────────────────────── */
        body {
            padding-top: 64px;   /* compensa la navbar fija */
            padding-bottom: 52px; /* compensa el footer flotante */
            background: #f0f4f8;
            font-family: 'Segoe UI', system-ui, sans-serif;
            font-size: 0.9rem;
        }

        /* ── TopBar ──────────────────────────────────────────────────────── */
        .navbar-comedica {
            background: linear-gradient(90deg, #0d47a1 0%, #1565c0 60%, #1976d2 100%);
            height: 56px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }

        .navbar-comedica .navbar-brand {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.3px;
            color: #fff !important;
        }

        /* Avatar del usuario */
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            color: #fff;
            border: 2px solid rgba(255,255,255,0.5);
            flex-shrink: 0;
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 2px 7px;
            border-radius: 20px;
        }

        /* Botón Menú Principal */
        .btn-menu-principal {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.35);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-menu-principal:hover {
            background: rgba(255,255,255,0.28);
            color: #fff;
        }

        /* ── Offcanvas Menú Principal ─────────────────────────────────────── */
        #offcanvasMenu {
            width: 300px;
        }

        #offcanvasMenu .offcanvas-header {
            background: linear-gradient(135deg, #0d47a1, #1565c0);
            color: #fff;
            padding: 16px 20px;
        }

        #offcanvasMenu .offcanvas-header .btn-close {
            filter: invert(1) grayscale(1);
        }

        #offcanvasMenu .offcanvas-body {
            padding: 0;
            background: #fafbfd;
        }

        /* Ítems del menú */
        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 20px;
            color: #263238;
            text-decoration: none;
            border-bottom: 1px solid #eaeff5;
            transition: background 0.15s, color 0.15s;
            font-size: 0.875rem;
        }

        .menu-item:hover,
        .menu-item.active {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .menu-item .menu-icon {
            width: 28px;
            text-align: center;
            font-size: 1rem;
            color: #1565c0;
        }

        .menu-item:hover .menu-icon {
            color: #0d47a1;
        }

        .menu-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #90a4ae;
            padding: 14px 20px 4px;
        }

        /* Etiqueta de ítem */
        .menu-label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Separador logout */
        .menu-logout {
            color: #c62828 !important;
            border-top: 2px solid #ffcdd2;
            margin-top: 8px;
        }

        .menu-logout .menu-icon {
            color: #c62828 !important;
        }

        /* ── Acordeón multinivel: Nivel 1 (categorías) ──────────────────── */
        #offcanvasMenu .accordion-item {
            border: none;
            border-radius: 0 !important;
            border-bottom: 1px solid #e5eaf2;
        }

        #offcanvasMenu .accordion-button {
            padding: 10px 16px;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #1565c0;
            background: #f0f4fb;
            box-shadow: none;
        }

        #offcanvasMenu .accordion-button:not(.collapsed) {
            color: #0d47a1;
            background: #dbeafe;
            box-shadow: none;
        }

        #offcanvasMenu .accordion-button::after {
            width: 14px;
            height: 14px;
            background-size: 14px;
            margin-left: auto;
            flex-shrink: 0;
        }

        #offcanvasMenu .accordion-body {
            padding: 0;
            background: #fafbfd;
        }

        /* ── Submenú colapsable: Nivel 2 header ─────────────────────────── */
        .menu-subgroup-hdr {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #37474f;
            font-size: 0.82rem;
            font-weight: 600;
            border-bottom: 1px solid #eaeff5;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s;
            text-decoration: none;
        }

        .menu-subgroup-hdr:hover {
            background: #e8f0fe;
            color: #1565c0;
        }

        .menu-subgroup-hdr .menu-icon {
            width: 24px;
            text-align: center;
            color: #5c8fd6;
            flex-shrink: 0;
        }

        .menu-subgroup-hdr:hover .menu-icon {
            color: #1565c0;
        }

        /* Chevron animado */
        .menu-chevron {
            font-size: 0.65rem;
            color: #90a4ae;
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        [data-bs-toggle="collapse"][aria-expanded="true"] .menu-chevron {
            transform: rotate(180deg);
        }

        /* ── Ítem de menú (nivel 2 directo y nivel 3) ───────────────────── */
        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #263238;
            text-decoration: none;
            border-bottom: 1px solid #eaeff5;
            transition: background 0.12s, color 0.12s;
            font-size: 0.845rem;
        }

        .menu-item:hover,
        .menu-item.active {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .menu-item .menu-icon {
            width: 24px;
            text-align: center;
            font-size: 0.9rem;
            color: #1565c0;
            flex-shrink: 0;
        }

        .menu-item:hover .menu-icon,
        .menu-item.active .menu-icon {
            color: #0d47a1;
        }

        /* Indicador de ítem activo */
        .menu-item.active {
            border-left: 3px solid #1565c0;
            font-weight: 600;
        }

        /* ── Breadcrumb / barra sub-header ───────────────────────────────── */
        .sub-header {
            background: #fff;
            border-bottom: 1px solid #dce3ee;
            padding: 8px 20px;
            font-size: 0.8rem;
            color: #546e7a;
        }

        /* ── Contenedor principal ────────────────────────────────────────── */
        .main-content {
            padding: 24px 20px;
        }

        /* ── Cards de módulos ─────────────────────────────────────────────── */
        .card-module {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }

        .card-module .card-header {
            background: linear-gradient(90deg, #0d47a1, #1976d2);
            color: #fff;
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
        }

        /* ── DataTables overrides ─────────────────────────────────────────── */
        .datatable-wrapper .dt-buttons .btn {
            font-size: 0.78rem;
            padding: 4px 10px;
        }

        table.dataTable thead th {
            background: #e8edf5;
            color: #263238;
            font-weight: 700;
            font-size: 0.8rem;
            white-space: nowrap;
        }

        table.dataTable tbody tr:hover {
            background: #e3f2fd !important;
        }
    </style>
</head>
<body>

<?php
if (!class_exists('Permission')) {
    require_once __DIR__ . '/../../models/Permission.php';
}

// Datos del usuario logueado
$sessionUser  = $_SESSION['user']     ?? [];
$permisos     = $_SESSION['permisos'] ?? [];
$fullName     = htmlspecialchars($sessionUser['full_name']  ?? 'Usuario');
$initials     = strtoupper(
    substr($sessionUser['first_name'] ?? 'U', 0, 1) .
    substr($sessionUser['last_name']  ?? '',  0, 1)
);
?>

<!-- ═══════════════ TOP BAR ═══════════════════════════════════════════════ -->
<nav class="navbar navbar-comedica fixed-top px-3">

    <!-- IZQUIERDA: Logo + nombre usuario -->
    <div class="d-flex align-items-center gap-2">
        <!-- Avatar con iniciales -->
        <div class="user-avatar"><?= $initials ?></div>

        <div class="d-flex flex-column lh-1">
            <span class="text-white fw-semibold" style="font-size:0.88rem;">
                <?= $fullName ?>
            </span>
            <span class="badge bg-success status-badge mt-1">
                <i class="fa-solid fa-circle" style="font-size:0.45rem; vertical-align:middle;"></i>
                &nbsp;En Línea
            </span>
        </div>
    </div>

    <!-- CENTRO: Nombre del sistema -->
    <!-- <span class="navbar-brand d-none d-md-block mx-auto"> -->
    <!--     <i class="fa-solid fa-hospital me-1"></i>COMEDICA -->
    <!-- </span> -->

<a href="index.php?route=dashboard" class="navbar-brand d-none d-md-block mx-auto">
    <img src="public/img/comedica.png" alt="COMEDICA" style="height: 38px; width: auto; object-fit: contain;">
</a>

    <!-- DERECHA: Acciones (Telegram + Menú Principal) -->
    <div class="d-flex align-items-center gap-2">

        <!-- ── Botón Chat Telegram con badge de no leídos ─────────────── -->
        <a href="index.php?route=chat"
           id="btn-telegram-chat"
           title="Chat Telegram"
           style="position:relative; display:inline-flex; align-items:center; justify-content:center;
                  width:38px; height:38px; border-radius:50%;
                  background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.35);
                  color:#fff; text-decoration:none; transition:background 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.28)'"
           onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            <i class="fa-brands fa-telegram" style="font-size:1.15rem;"></i>
            <!-- Badge contador de no leídos -->
            <span id="telegram-unread-badge"
                  style="display:none; position:absolute; top:-4px; right:-4px;
                         background:#e53935; color:#fff; font-size:0.6rem; font-weight:700;
                         border-radius:50px; padding:1px 5px; min-width:16px;
                         text-align:center; line-height:1.4; border:2px solid #1565c0;
                         pointer-events:none;">
                0
            </span>
        </a>

        <!-- ── Botón Menú Principal ────────────────────────────────────── -->
        <button class="btn btn-menu-principal"
                type="button"
                data-bs-toggle="offcanvas"
                data-bs-target="#offcanvasMenu"
                aria-controls="offcanvasMenu">
            <i class="fa-solid fa-gear me-1"></i>Menú Principal
        </button>

    </div>

</nav>
<!-- ════════════════════════════════════════════════════════════════════════ -->


<!-- ═══════════════ OFFCANVAS MENÚ PRINCIPAL ════════════════════════════  -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMenu"
     aria-labelledby="offcanvasMenuLabel">

    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-hospital fa-lg"></i>
            <div>
                <h6 class="offcanvas-title mb-0 fw-bold" id="offcanvasMenuLabel">Menú Principal</h6>
                <small style="opacity:.8; font-size:0.72rem;"><?= $fullName ?></small>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>

    <div class="offcanvas-body p-0" style="overflow-y:auto;">

        <?php
        // ─────────────────────────────────────────────────────────────────
        // AGRUPAMIENTO: $_SESSION['permisos'] por CLAVE_CATEGORIA
        // Cada permiso tiene: CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION
        // ─────────────────────────────────────────────────────────────────
        $routeActual  = isset($_GET['route']) ? $_GET['route'] : 'dashboard';
        $grupos       = [];

        foreach ($permisos as $p) {
            $cat = (isset($p['CLAVE_CATEGORIA']) && $p['CLAVE_CATEGORIA'] !== '')
                   ? trim($p['CLAVE_CATEGORIA'])
                   : 'GENERAL';
            $grupos[$cat][] = $p;
        }

        // Ordenar las categorías según el orden lógico definido en Permission
        $ordenDef         = Permission::getOrdenCategorias();
        $gruposOrdenados  = [];
        foreach ($ordenDef as $catKey) {
            if (isset($grupos[$catKey])) {
                $gruposOrdenados[$catKey] = $grupos[$catKey];
                unset($grupos[$catKey]);
            }
        }
        // Añadir al final las categorías no previstas en el orden
        foreach ($grupos as $catKey => $items) {
            $gruposOrdenados[$catKey] = $items;
        }
        ?>

        <!-- ── Dashboard — siempre visible en la cima ───────────────────── -->
        <a href="index.php?route=dashboard"
           class="menu-item menu-nav-link<?= ($routeActual === 'dashboard' || $routeActual === '') ? ' active' : '' ?>"
           style="padding:10px 16px; font-weight:700;
                  background:#eef2fb; border-bottom:2px solid #d0daf0;">
            <span class="menu-icon">
                <i class="fa-solid fa-gauge-high" style="color:#1565c0;"></i>
            </span>
            <span class="menu-label">Dashboard</span>
        </a>

        <!-- ── Acordeón de categorías (1 abierto a la vez) ──────────────── -->
        <?php if (!empty($gruposOrdenados)): ?>

        <div class="accordion accordion-flush" id="mainMenu">

        <?php foreach ($gruposOrdenados as $cat => $items):
            $catId    = 'cat-' . preg_replace('/[^a-zA-Z0-9]/', '', $cat);
            $catLabel = Permission::nombreCategoria($cat);
            $catIcon  = Permission::iconoCategoria($cat);

            // ¿Algún ítem de esta categoría está activo?
            $catActiva = false;
            foreach ($items as $p) {
                $ruta = Permission::getRoute(isset($p['CLAVE']) ? $p['CLAVE'] : '');
                if ($ruta === $routeActual) { $catActiva = true; break; }
            }
        ?>

            <!-- ── Categoría: <?= htmlspecialchars($catLabel) ?> ── -->
            <div class="accordion-item">

                <div class="accordion-header" id="hdr-<?= $catId ?>">
                    <button class="accordion-button <?= $catActiva ? '' : 'collapsed' ?>"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= $catId ?>"
                            data-bs-parent="#mainMenu"
                            aria-expanded="<?= $catActiva ? 'true' : 'false' ?>"
                            aria-controls="<?= $catId ?>">
                        <i class="<?= htmlspecialchars($catIcon) ?> me-2"
                           style="width:15px; text-align:center; flex-shrink:0;"></i>
                        <?= htmlspecialchars($catLabel) ?>
                        <span class="badge bg-secondary bg-opacity-25 text-secondary ms-auto me-2"
                              style="font-size:0.65rem; font-weight:500;">
                            <?= count($items) ?>
                        </span>
                    </button>
                </div>

                <div id="<?= $catId ?>"
                     class="accordion-collapse collapse <?= $catActiva ? 'show' : '' ?>"
                     aria-labelledby="hdr-<?= $catId ?>"
                     data-bs-parent="#mainMenu">

                    <div class="accordion-body p-0">
                    <?php foreach ($items as $p):
                        $clave  = isset($p['CLAVE'])          ? $p['CLAVE']          : '';
                        $nombre = isset($p['NOMBRE_PERMISO']) ? $p['NOMBRE_PERMISO'] : $clave;
                        $descr  = isset($p['DESCRIPCION'])    ? $p['DESCRIPCION']    : '';
                        $icon   = Permission::iconoPorClave($clave, $cat);
                        $ruta   = Permission::getRoute($clave);
                        $active = ($ruta === $routeActual) ? ' active' : '';
                    ?>
                        <a href="index.php?route=<?= htmlspecialchars($ruta) ?>"
                           class="menu-item menu-nav-link<?= $active ?>"
                           style="padding:9px 12px 9px 36px;"
                           <?= $descr ? 'title="' . htmlspecialchars($descr) . '"' : '' ?>>
                            <span class="menu-icon">
                                <i class="<?= htmlspecialchars($icon) ?>"></i>
                            </span>
                            <span class="menu-label"><?= htmlspecialchars($nombre) ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>

                </div>
            </div><!-- /.accordion-item -->

        <?php endforeach; ?>

        </div><!-- /#mainMenu -->

        <?php else: ?>
            <!-- Sin permisos asignados -->
            <div class="text-center py-5 px-3">
                <i class="fa-solid fa-lock fa-2x text-muted mb-3 d-block"></i>
                <p class="text-muted mb-0" style="font-size:0.82rem;">
                    No hay módulos asignados a su usuario.<br>
                    <span class="text-primary">Contacte al administrador.</span>
                </p>
            </div>
        <?php endif; ?>

        <!-- ── Cerrar Sesión ─────────────────────────────────────────────── -->
        <a href="index.php?route=logout"
           class="menu-item menu-nav-link menu-logout"
           style="padding:10px 16px; margin-top:4px;">
            <span class="menu-icon">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
            <span class="menu-label">Cerrar Sesión</span>
        </a>

    </div>
</div>
<!-- ════════════════════════════════════════════════════════════════════════ -->

<!--
    NOTA TÉCNICA: Bootstrap 5 llama a event.preventDefault() para toda etiqueta
    <a> con data-bs-dismiss, bloqueando la navegación del href.
    Solución: los links del menú NO usan data-bs-dismiss; este script cierra el
    offcanvas Y navega en paralelo (la recarga de página elimina el offcanvas
    visualmente de inmediato).
-->
<script>
(function () {
    var offcanvasEl = document.getElementById('offcanvasMenu');
    if (!offcanvasEl) return;

    offcanvasEl.addEventListener('click', function (e) {
        // Buscar el <a> más cercano con clase menu-nav-link
        var link = e.target.closest('a.menu-nav-link');
        if (!link) return;

        var href = link.getAttribute('href');
        if (!href || href === '#') return;

        e.preventDefault(); // evitar cualquier handler de Bootstrap

        // Obtener instancia del offcanvas y cerrarlo
        var offcanvasInst = (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas)
            ? bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl)
            : null;

        if (offcanvasInst) {
            offcanvasInst.hide();
        }

        // Navegar de inmediato (la página se recarga, el offcanvas desaparece solo)
        window.location.href = href;
    });
})();
</script>

<!-- Barra sub-header con breadcrumb -->
<div class="sub-header d-flex align-items-center gap-1">
    <i class="fa-solid fa-house-medical me-1"></i>
    <a href="index.php?route=dashboard" class="text-decoration-none text-secondary">Inicio</a>
    <?php if (!empty($breadcrumb)): ?>
        <?php foreach ($breadcrumb as $bc): ?>
            <span class="mx-1">/</span>
            <?php if (!empty($bc['url'])): ?>
                <a href="<?= htmlspecialchars($bc['url']) ?>"
                   class="text-decoration-none text-secondary"><?= htmlspecialchars($bc['label']) ?></a>
            <?php else: ?>
                <span class="text-primary fw-semibold"><?= htmlspecialchars($bc['label']) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Contenido principal -->
<div class="main-content">
