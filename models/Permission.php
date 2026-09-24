<?php
/**
 * models/Permission.php
 * Consulta de permisos asignados a un usuario.
 *
 * Esquema real de vfpmedicos:
 *   users_permissions : ID, USER_ID, PERMISSION_ID (→ permisos.CLAVE), ADDED, REMOVED
 *   permisos          : CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION
 */

require_once __DIR__ . '/../config/database.php';

class Permission
{
    private $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Devuelve la lista de permisos activos (ADDED=1, REMOVED=0 o NULL) para un usuario.
     *
     * @param  int   $userId   Valor de USER_ID en la tabla users.
     * @return array           Filas con: CLAVE, NOMBRE_PERMISO, CLAVE_CATEGORIA, DESCRIPCION
     */
    public function getByUser($userId)
    {
        $sql = "SELECT
                    p.CLAVE,
                    p.NOMBRE_PERMISO,
                    p.CLAVE_CATEGORIA,
                    p.DESCRIPCION
                FROM users_permissions up
                INNER JOIN permisos p ON up.PERMISSION_ID = p.CLAVE
                WHERE up.USER_ID   = :user_id
                  AND up.ADDED     = 1
                  AND (up.REMOVED  = 0 OR up.REMOVED IS NULL)
                ORDER BY p.CLAVE_CATEGORIA, p.NOMBRE_PERMISO";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Protección durante desarrollo: si la tabla aún no existe, no romper el login.
            error_log('Permission::getByUser() — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Agrupa un array de permisos por CLAVE_CATEGORIA.
     *
     * @param  array $permisos  Resultado de getByUser().
     * @return array            Array asociativo: ['CATEGORIA' => [permisos...], ...]
     */
    public static function agruparPorCategoria(array $permisos)
    {
        $grupos = [];
        foreach ($permisos as $p) {
            $cat = isset($p['CLAVE_CATEGORIA']) ? $p['CLAVE_CATEGORIA'] : 'GENERAL';
            $grupos[$cat][] = $p;
        }
        return $grupos;
    }

    /**
     * Verifica si un usuario tiene un permiso específico por CLAVE.
     *
     * @param  array  $permisos  Resultado de getByUser().
     * @param  string $clave     CLAVE a verificar (ej: 'MNU_FACTURACION').
     * @return bool
     */
    public static function tiene(array $permisos, $clave)
    {
        foreach ($permisos as $p) {
            if (isset($p['CLAVE']) && $p['CLAVE'] === $clave) {
                return true;
            }
        }
        return false;
    }

    /**
     * Devuelve el ícono FontAwesome correspondiente a una CLAVE de permiso.
     * Primero busca por CLAVE exacta, luego por prefijo de categoría.
     *
     * @param  string $clave          CLAVE del permiso (ej: 'MNU_ARCHIVOS').
     * @param  string $claveCategoria CLAVE_CATEGORIA (ej: 'ARCHIVOS').
     * @return string                 Clases FontAwesome completas (ej: 'fa-solid fa-folder').
     */
    public static function iconoPorClave($clave, $claveCategoria = '')
    {
        // ── Mapa por CLAVE exacta ─────────────────────────────────────────
        $mapaClave = [
            // Archivos / Prestadores
            'MNU_ARCHIVOS'              => 'fa-solid fa-folder',
            'MNU_ARC_PRESTADORES'       => 'fa-solid fa-stethoscope',
            'MNU_ARC_ESPECIALIDADES'    => 'fa-solid fa-briefcase-medical',
            'MNU_ARC_NOMENCLADOR'       => 'fa-solid fa-book-medical',
            'MNU_ARC_PLANES'            => 'fa-solid fa-layer-group',
            'MNU_ARC_LOCALIDADES'       => 'fa-solid fa-map-location-dot',
            'MNU_ARC_DIAGNOSTICOS'      => 'fa-solid fa-notes-medical',
            // Afiliados
            'MNU_AFILIADOS'             => 'fa-solid fa-id-card',
            'MNU_AFI_BUSQUEDA'          => 'fa-solid fa-magnifying-glass',
            'MNU_AFI_ALTA'              => 'fa-solid fa-user-plus',
            'MNU_AFI_BAJA'              => 'fa-solid fa-user-minus',
            'MNU_AFI_MODIFICACION'      => 'fa-solid fa-user-pen',
            // Autorizaciones / Órdenes
            'MNU_ORDENES'               => 'fa-solid fa-clipboard-check',
            'MNU_AUTORIZACIONES'        => 'fa-solid fa-file-circle-check',
            'MNU_ORD_NUEVA'             => 'fa-solid fa-file-medical',
            'MNU_ORD_CONSULTA'          => 'fa-solid fa-file-magnifying-glass',
            // Internaciones
            'MNU_INTERNACIONES'         => 'fa-solid fa-bed-pulse',
            'MNU_INT_ALTA'              => 'fa-solid fa-hospital-user',
            'MNU_INT_CONSULTA'          => 'fa-solid fa-clipboard-list',
            // Facturación
            'MNU_FACTURACION'           => 'fa-solid fa-file-invoice-dollar',
            'MNU_FAC_LIQUIDACION'       => 'fa-solid fa-cash-register',
            'MNU_FAC_RENDICION'         => 'fa-solid fa-receipt',
            // Cartilla
            'MNU_CARTILLA'              => 'fa-solid fa-address-book',
            'MNU_CAR_CONSULTA'          => 'fa-solid fa-book-open',
            // Call Center
            'MNU_CALLCENTER'            => 'fa-solid fa-headset',
            'MNU_CALL_BASE'             => 'fa-solid fa-phone-volume',
            'MNU_CALL_ESTADO'           => 'fa-solid fa-signal',
            'MNU_CALL_LLAMADOS'         => 'fa-solid fa-phone',
            // Telemedicina
            'MNU_TELEMEDICINA'          => 'fa-solid fa-video',
            'MNU_TEL_TURNOS'            => 'fa-solid fa-calendar-check',
            // Convenios / Obras Sociales
            'MNU_CONVENIOS'             => 'fa-solid fa-handshake',
            'MNU_OBRAS_SOCIALES'        => 'fa-solid fa-building-columns',
            // Negociaciones
            'MNU_NEGOCIACIONES'         => 'fa-solid fa-scale-balanced',
            // Expedientes
            'MNU_EXPEDIENTES'           => 'fa-solid fa-folder-open',
            // Valores / Prestaciones
            'MNU_VALORES'               => 'fa-solid fa-dollar-sign',
            'MNU_PRESTACIONES'          => 'fa-solid fa-list-check',
            // Traslados
            'MNU_TRASLADO'              => 'fa-solid fa-ambulance',
            'MNU_DERIVACION'            => 'fa-solid fa-right-left',
            // Recursos Humanos
            'MNU_RRHH'                  => 'fa-solid fa-people-group',
            'MNU_RH_PERSONAL'           => 'fa-solid fa-user-tie',
            'MNU_RH_LIQUIDACION'        => 'fa-solid fa-money-check-dollar',
            // Administración / Config
            'MNU_ADMIN'                 => 'fa-solid fa-user-shield',
            'MNU_CONFIGURACION'         => 'fa-solid fa-gear',
            'MNU_CONFIG_USUARIOS'       => 'fa-solid fa-users-gear',
            'MNU_CONFIG_ROLES'          => 'fa-solid fa-user-lock',
            'MNU_CONFIG_PERMISOS'       => 'fa-solid fa-key',
            // Reportes
            'MNU_REPORTES'              => 'fa-solid fa-chart-bar',
            'MNU_REP_ESTADISTICAS'      => 'fa-solid fa-chart-line',
            // Dashboard
            'MNU_DASHBOARD'             => 'fa-solid fa-gauge-high',
            // Chat Telegram
            'MNU_CHAT_TELEGRAM'         => 'fa-brands fa-telegram',
        ];

        if (isset($mapaClave[$clave])) {
            return $mapaClave[$clave];
        }

        // ── Fallback por CLAVE_CATEGORIA ──────────────────────────────────
        $mapaCategoria = [
            'ARCHIVOS'        => 'fa-solid fa-folder',
            'AFILIADOS'       => 'fa-solid fa-id-card',
            'AUTORIZACIONES'  => 'fa-solid fa-clipboard-check',
            'ORDENES'         => 'fa-solid fa-clipboard-check',
            'INTERNACIONES'   => 'fa-solid fa-bed-pulse',
            'FACTURACION'     => 'fa-solid fa-file-invoice-dollar',
            'CARTILLA'        => 'fa-solid fa-address-book',
            'CALLCENTER'      => 'fa-solid fa-headset',
            'TELEMEDICINA'    => 'fa-solid fa-video',
            'CONVENIOS'       => 'fa-solid fa-handshake',
            'NEGOCIACIONES'   => 'fa-solid fa-scale-balanced',
            'EXPEDIENTES'     => 'fa-solid fa-folder-open',
            'VALORES'         => 'fa-solid fa-dollar-sign',
            'TRASLADO'        => 'fa-solid fa-ambulance',
            'RRHH'            => 'fa-solid fa-people-group',
            'ADMIN'           => 'fa-solid fa-user-shield',
            'CONFIGURACION'   => 'fa-solid fa-gear',
            'REPORTES'        => 'fa-solid fa-chart-bar',
            'UTILES'          => 'fa-solid fa-toolbox',
            'GENERAL'         => 'fa-solid fa-circle-dot',
        ];

        $cat = strtoupper($claveCategoria);
        if (isset($mapaCategoria[$cat])) {
            return $mapaCategoria[$cat];
        }

        // ── Fallback por prefijo de la CLAVE ─────────────────────────────
        $prefijos = [
            'MNU_ARC'   => 'fa-solid fa-folder',
            'MNU_AFI'   => 'fa-solid fa-id-card',
            'MNU_ORD'   => 'fa-solid fa-clipboard-check',
            'MNU_INT'   => 'fa-solid fa-bed-pulse',
            'MNU_FAC'   => 'fa-solid fa-file-invoice-dollar',
            'MNU_CAR'   => 'fa-solid fa-address-book',
            'MNU_CALL'  => 'fa-solid fa-headset',
            'MNU_TEL'   => 'fa-solid fa-video',
            'MNU_NEG'   => 'fa-solid fa-scale-balanced',
            'MNU_EXP'   => 'fa-solid fa-folder-open',
            'MNU_VAL'   => 'fa-solid fa-dollar-sign',
            'MNU_TRA'   => 'fa-solid fa-ambulance',
            'MNU_RH'    => 'fa-solid fa-people-group',
            'MNU_CON'   => 'fa-solid fa-handshake',
            'MNU_ADM'   => 'fa-solid fa-user-shield',
            'MNU_REP'   => 'fa-solid fa-chart-bar',
            'MNU_CFG'   => 'fa-solid fa-gear',
        ];

        foreach ($prefijos as $prefijo => $icono) {
            if (strpos($clave, $prefijo) === 0) {
                return $icono;
            }
        }

        return 'fa-solid fa-circle-dot'; // ícono genérico final
    }

    /**
     * Devuelve el nombre legible de una CLAVE_CATEGORIA para mostrar como encabezado de sección.
     *
     * @param  string $claveCategoria
     * @return string
     */
    public static function nombreCategoria($claveCategoria)
    {
        $mapa = [
            'ARCHIVOS'        => 'Archivos',
            'AFILIADOS'       => 'Afiliados',
            'AUTORIZACIONES'  => 'Autorizaciones',
            'ORDENES'         => 'Órdenes',
            'INTERNACIONES'   => 'Internaciones',
            'FACTURACION'     => 'Facturación',
            'CARTILLA'        => 'Cartilla',
            'CALLCENTER'      => 'Call Center',
            'TELEMEDICINA'    => 'Telemedicina',
            'CONVENIOS'       => 'Convenios',
            'NEGOCIACIONES'   => 'Negociaciones',
            'EXPEDIENTES'     => 'Expedientes',
            'VALORES'         => 'Valores',
            'TRASLADO'        => 'Traslados',
            'RRHH'            => 'Recursos Humanos',
            'ADMIN'           => 'Administración',
            'CONFIGURACION'   => 'Configuración',
            'REPORTES'        => 'Reportes',
            'UTILES'          => 'Útiles',
            'GENERAL'         => 'General',
        ];

        $key = strtoupper($claveCategoria);
        return isset($mapa[$key]) ? $mapa[$key] : ucwords(strtolower(str_replace('_', ' ', $claveCategoria)));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RUTAS Y ORDENAMIENTO DE CATEGORÍAS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Devuelve el ?route= (slug URL-friendly) para una CLAVE de permiso.
     * Todos los módulos están mapeados. Si la CLAVE no está en el mapa,
     * convierte a minúsculas con guiones (ej: MNU_FOO_BAR → mnu-foo-bar).
     *
     * @param  string $clave  CLAVE del permiso (ej: 'MNU_ARC_PRESTADORES').
     * @return string         Valor de ?route= (ej: 'prestadores').
     */
    public static function getRoute($clave)
    {
        $map = self::_routeMap();
        return isset($map[$clave])
            ? $map[$clave]
            : strtolower(str_replace('_', '-', $clave));
    }

    /**
     * Dado un route slug, devuelve el nombre visible del módulo.
     * Primero busca en $_SESSION['permisos'] (más preciso),
     * luego cae al mapa estático de etiquetas.
     *
     * @param  string $route
     * @return string|null
     */
    public static function getLabelForRoute($route)
    {
        // Buscar en sesión activa (tiene NOMBRE_PERMISO real de la BD)
        if (!empty($_SESSION['permisos'])) {
            foreach ($_SESSION['permisos'] as $p) {
                $clave = isset($p['CLAVE']) ? $p['CLAVE'] : '';
                if (self::getRoute($clave) === $route) {
                    return isset($p['NOMBRE_PERMISO']) ? $p['NOMBRE_PERMISO'] : null;
                }
            }
        }
        // Fallback al mapa estático de etiquetas
        $labels = self::_routeLabelMap();
        return isset($labels[$route]) ? $labels[$route] : null;
    }

    /**
     * Dado un route slug, devuelve el ícono FontAwesome del módulo.
     */
    public static function getIconForRoute($route)
    {
        if (!empty($_SESSION['permisos'])) {
            foreach ($_SESSION['permisos'] as $p) {
                $clave = isset($p['CLAVE']) ? $p['CLAVE'] : '';
                if (self::getRoute($clave) === $route) {
                    $cat = isset($p['CLAVE_CATEGORIA']) ? $p['CLAVE_CATEGORIA'] : '';
                    return self::iconoPorClave($clave, $cat);
                }
            }
        }
        return 'fa-solid fa-tools';
    }

    /**
     * Mapa completo CLAVE → route slug URL-friendly.
     */
    private static function _routeMap()
    {
        return [
            // ── ARCHIVOS ──────────────────────────────────────────────────
            'MNU_ARC_PRESTADORES'     => 'prestadores',
            'MNU_ARC_ESPECIALIDADES'  => 'especialidades',
            'MNU_ARC_OBRAS_SOCIALES'  => 'obras-sociales',
            'MNU_ARC_NOMENCLADOR'     => 'nomenclador',
            'MNU_ARC_NBU'             => 'nbu',
            'MNU_ARC_KAIROS'          => 'kairos',
            'MNU_ARC_TABLAS'          => 'tablas-generales',
            // Tablas de Archivos
            'MNU_ARC_TAB_LOCALIDADES' => 'tablas-generales&ref=LOC',
            'MNU_ARC_TAB_ZONAS'       => 'tablas-generales&ref=ZON',
            'MNU_ARC_TAB_GRUPO_WEB'   => 'tablas-generales&ref=GRW',
            'MNU_ARC_TAB_EMPRESAS'    => 'tablas-generales&ref=EMP',
            'MNU_ARC_TAB_BANCOS'      => 'tablas-generales&ref=BAN',
            'MNU_ARC_TAB_CIERRE_AUM'  => 'tablas-generales&ref=AUM',
            'MNU_ARC_TAB_PCT_AUM'     => 'pct-aumento',
            'MNU_ARC_TAB_GRP_CARTILLA'=> 'tablas-generales&ref=GRUPOCARTI',
            'MNU_ARC_TAB_TIT_CARTILLA'=> 'tablas-generales&ref=GRUPOSUP',
            'MNU_ARC_TAB_CONC_INT'    => 'conceptos-internacion',
            'MNU_ARC_TAB_PROC_INT'    => 'procesos-internacion',
            'MNU_ARC_TAB_TIPO_CERT'   => 'tablas-generales&ref=CER',
            'MNU_ARC_TAB_VAL_VENTA'   => 'tablas-generales&ref=LST',
            'MNU_ARC_TAB_CTRL_WEB'    => 'control-web',
            'MNU_ARC_TAB_URL_REF'     => 'tablas-generales&ref=URL',
            'MNU_ARC_TAB_TIPOS_CX'    => 'tablas-generales&ref=TCX',
            'MNU_ARC_TAB_SUBCAT'      => 'tablas-generales&ref=SBC',
            'MNU_ARC_TAB_REP_LEY'     => 'reportes-leyendas',
            'MNU_ARC_TAB_HOMOL'       => 'tablas-generales&ref=HOM',
            'MNU_ARC_TAB_GRP_AUT'     => 'tablas-generales&ref=GR1',
            'MNU_ARC_TAB_MOT_DEB'     => 'tablas-generales&ref=DEB',
            'MNU_ARC_TAB_GRP_NN'      => 'tablas-generales&ref=GRUPONN',
            'MNU_ARC_TAB_SUBGRP_NN'   => 'tablas-generales&ref=SUB',
            'MNU_ARC_TAB_AJ_IMP'      => 'tablas-generales&ref=AJU',
            'MNU_ARC_TAB_AJ_AFI'      => 'tablas-generales&ref=AAF',
            'MNU_ARC_TAB_TXT_REC'     => 'tablas-generales&ref=TXT',
            'MNU_ARC_TAB_DIAG'        => 'tablas-generales&ref=DIAGNO',
            'MNU_ARC_TAB_NOMENTP'     => 'tablas-generales&ref=NOMENTP',
            'MNU_ARC_TAB_GRUPOCARTI'  => 'tablas-generales&ref=GRUPOCARTI',
            'MNU_ARC_TAB_GRUPONN'     => 'tablas-generales&ref=GRUPONN',
            'MNU_ARC_TAB_GRUPOSUP'    => 'tablas-generales&ref=GRUPOSUP',
            // ── CARGA DATOS ───────────────────────────────────────────────
            'MNU_CD_PREST_INGRESO'    => 'carga-prestaciones',
            'MNU_CD_PREST_MOD'        => 'mod-prestaciones',
            'MNU_CD_FAC_INGRESO'      => 'registro-facturas',
            'MNU_REG_FACTURAS'        => 'registro-facturas',
            'MNU_CD_FAC_LST_DET'      => 'listado-detallado-os',
            'MNU_CD_FAC_LST_TOT_AC'   => 'totales-acumulados-os',
            'MNU_CD_FAC_LST_TOT_CMP'  => 'totales-comparados-os',
            'MNU_CD_DIAG'             => 'carga-diagnosticos',
            // ── AUTORIZACIONES ────────────────────────────────────────────
            'MNU_AUT_AUTORIZACIONES'  => 'autorizaciones',
            // ── AUDIT. FACTURAC. ──────────────────────────────────────────
            'MNU_AF_AUDITAR'          => 'audit-facturacion',
            'MNU_AF_CIERRE_AUTO'      => 'cierre-automatico',
            'MNU_AF_LIQ_AUDITAR'      => 'liq-auditar',
            'MNU_AF_LIQ_SIN_CERRAR'   => 'liq-sin-cerrar',
            'MNU_AF_REIMP_LIQ'        => 'reimp-liquidaciones',
            'MNU_AF_ENV_DEBITO'       => 'envio-debitos',
            'MNU_AF_EXP_PREST_GEN'    => 'export-prestaciones-general',
            'MNU_AF_EXP_PREST_AYER'   => 'export-prestaciones-ayer',
            'MNU_AF_EXP_PREST_VENT'   => 'export-prestaciones-ventas',
            'MNU_AF_CALC_CAPITAS'     => 'calculo-capitas',
            // ── CONTADURÍA ────────────────────────────────────────────────
            'MNU_CONT_LC_PERIODO'     => 'liq-por-periodo',
            'MNU_CONT_LC_OS_PER'      => 'liq-os-periodo',
            'MNU_CONT_LC_CERR_PER'    => 'liq-cerradas-periodo',
            'MNU_CONT_LC_OS_FCH'      => 'liq-os-fecha-cierre',
            'MNU_CONT_LC_CUIDAR'      => 'pago-cuidar',
            'MNU_CONT_LC_VARIOS'      => 'liq-varios-prestadores',
            'MNU_CONT_LC_FECHAS'      => 'reporte-entre-fechas',
            'MNU_CONT_LIQ_EST'        => 'liq-estimadas',
            'MNU_CONT_SIN_CERRAR'     => 'sin-cerrar-hasta-ayer',
            'MNU_CONT_SALDOS'         => 'listado-saldos',
            'MNU_CONT_RECIBOS'        => 'recibos-faltantes',
            'MNU_CONT_FECHAS_PAGO'    => 'fechas-pago',
            'MNU_CONT_RES_OPAGO'      => 'resumen-opago',
            'MNU_CONT_HIST_PREST'     => 'historial-por-prestador',
            'MNU_CONT_ANUL_OPAGO'     => 'anulacion-opago',
            // ── RESÚMENES / REPORTES ──────────────────────────────────────
            'MNU_REP_HIST_CLIN'       => 'historias-clinicas',
            'MNU_REP_AFIL_PADRON'     => 'afiliados-fuera-padron',
            'MNU_REP_AFIL_PAD_PREST'  => 'afil-padron-por-prestador',
            'MNU_REP_VENTAS_GEN'      => 'ventas-general',
            'MNU_REP_VENTAS_GRP'      => 'ventas-grupo',
            'MNU_REP_VENTAS_AYER'     => 'ventas-ayer',
            'MNU_REP_VENTAS_AYER_GRP' => 'ventas-ayer-grupo',
            'MNU_REP_NORM_650'        => 'normativa-650',
            'MNU_REP_AUD_CTRL'        => 'auditoria-control-prestadores',
            // ── CONFIGURACIÓN ─────────────────────────────────────────────
            'MNU_CFG_CAMBIO_USR'      => 'cambio-usuario',
            'MNU_CFG_CARGA_USR'       => 'carga-usuarios',
            'MNU_CFG_CONV_PAD'        => 'conversion-padrones',
            'MNU_CFG_EMAIL_MAS'       => 'email-masivo',
            'MNU_CFG_CARTILLA'        => 'cartilla',
            'MNU_CFG_LOG_MAILS'       => 'log-mails',
            'MNU_CFG_LOG_PAGOS'       => 'log-pagos',
            'MNU_CFG_LOG_RES'         => 'log-resumen',
            'MNU_CFG_LOG_PREST'       => 'log-prestadores',
            'MNU_CFG_LOG_DEB'         => 'log-debitos',
            'MNU_CFG_LOG_LIQ_SC'      => 'log-liq-sin-cerrar',
            'MNU_CFG_LOG_COSEG'       => 'log-coseguros',
            'MNU_CFG_LOG_HIST_AUM'    => 'log-hist-aumentos',
            'MNU_CFG_LOG_PDF_AUT'     => 'log-pdf-autorizaciones',
            'MNU_CFG_LOG_NOV_OS'      => 'log-novedades-os',
            'MNU_CFG_LOG_USO_PREST'   => 'log-uso-prestaciones',
            'MNU_CFG_LOG_MAS_PREST'   => 'log-masivo-prestadores',
            'MNU_CFG_LOG_MAS_ERR'     => 'log-masivo-error',
            'MNU_CFG_LOG_BAJADA'      => 'log-bajada-ordenes',
            'MNU_CFG_LOG_TOPE'        => 'log-tope-carga',
            'MNU_CFG_LOG_PAGOS_AUT'   => 'log-pagos-automatico',
            'MNU_CFG_CIE_PERIODO'     => 'cierre-periodo',
            'MNU_CFG_CIE_AUM'         => 'cierre-aumentos-cfg',
            // ── MÓDULOS EXTRA (la BD puede tener CLAVEs adicionales) ──────
            'MNU_CHAT_TELEGRAM'       => 'chat',
            'MNU_INTERNACIONES'       => 'internaciones',
            'MNU_FACTURACION'         => 'facturacion',
            'MNU_AFILIADOS'           => 'afiliados',
            'MNU_CARTILLA'            => 'cartilla-web',
            'MNU_TELEMEDICINA'        => 'telemedicina',
            'MNU_CALLCENTER'          => 'call-center',
            'MNU_NEGOCIACIONES'       => 'negociaciones',
            'MNU_EXPEDIENTES'         => 'expedientes',
            'MNU_TRASLADO'            => 'traslados',
            'MNU_RRHH'                => 'rrhh',
            'MNU_ADMIN'               => 'admin',
        ];
    }

    /** Mapa route → etiqueta visible para la página "Módulo en implementación". */
    private static function _routeLabelMap()
    {
        return [
            'prestadores'                   => 'Prestadores',
            'especialidades'                => 'Especialidades',
            'obras-sociales'                => 'Obras Sociales',
            'nomenclador'                   => 'Nomenclador Nacional',
            'nbu'                           => 'NBU',
            'kairos'                        => 'Kairos',
            'autorizaciones'                => 'Autorizaciones',
            'audit-facturacion'             => 'Auditar Facturación',
            'cierre-automatico'             => 'Cierre Automático',
            'liq-auditar'                   => 'Liquidaciones a Auditar',
            'liq-sin-cerrar'                => 'Liquidaciones sin Cerrar',
            'reimp-liquidaciones'           => 'Reimpresión de Liquidaciones',
            'envio-debitos'                 => 'Envío Avisos de Débito',
            'export-prestaciones-general'   => 'Exportación de Prestaciones (General)',
            'export-prestaciones-ayer'      => 'Exportación Hasta Ayer',
            'export-prestaciones-ventas'    => 'Exportación Hasta Ayer Ventas',
            'calculo-capitas'               => 'Cálculo Cápitas',
            'liq-estimadas'                 => 'Liquidaciones Estimadas',
            'listado-saldos'                => 'Listado de Saldos',
            'recibos-faltantes'             => 'Recibos Faltantes',
            'fechas-pago'                   => 'Fechas de Pago',
            'resumen-opago'                 => 'Resumen O.Pago',
            'historial-por-prestador'       => 'Historial por Prestador',
            'anulacion-opago'               => 'Anulación O.Pago',
            'historias-clinicas'            => 'Historias Clínicas',
            'afiliados-fuera-padron'        => 'Afiliados Fuera Padrón',
            'ventas-general'                => 'Lista de Ventas (General)',
            'ventas-grupo'                  => 'Lista de Ventas (Grupo)',
            'normativa-650'                 => 'Normativa 650',
            'auditoria-control-prestadores' => 'Auditoría Control Prestadores',
            'cambio-usuario'                => 'Cambio de Usuario',
            'carga-usuarios'                => 'Carga de Usuarios',
            'email-masivo'                  => 'Envío eMail Masivo',
            'cartilla'                      => 'Cartilla',
            'chat'                          => 'Chat Telegram',
            'internaciones'                 => 'Internaciones',
            'facturacion'                   => 'Facturación',
            'afiliados'                     => 'Información Afiliados',
            'cartilla-web'                  => 'Cartilla Web',
            'telemedicina'                  => 'Telemedicina',
            'call-center'                   => 'Call Center',
            'negociaciones'                 => 'Negociaciones',
            'expedientes'                   => 'Expedientes',
            'traslados'                     => 'Traslados / Derivación',
            'rrhh'                          => 'Recursos Humanos',
            'admin'                         => 'Administración',
            'carga-prestaciones'            => 'Ingreso de Prestaciones',
            'registro-facturas'             => 'Registración de Facturas',
            'mnu-reg-facturas'              => 'Registración de Facturas',
            'registro-facturas-pdf'         => 'Pendientes de Facturas PDF',
            'registro_facturas_pdf'         => 'Pendientes de Facturas PDF',
            'subir-facturas-pdf'            => 'Subir Facturas PDF',
            'subir_facturas_pdf'            => 'Subir Facturas PDF',
            'carga-diagnosticos'            => 'Carga de Diagnósticos',
            'cierre-periodo'                => 'Cierre de Período',
        ];
    }

    /**
     * Orden de visualización preferido de las categorías en el menú.
     * Las categorías de BD que no estén en esta lista se añaden al final
     * en el orden en que las devuelve la consulta.
     *
     * @return string[]
     */
    public static function getOrdenCategorias()
    {
        return [
            'ARCHIVOS',
            'CARGA_DATOS',
            'AUTORIZACIONES',
            'AUDIT_FAC',
            'AUDIT_FACTURACION',
            'CONTADURIA',
            'REPORTES',
            'CONFIGURACION',
            'UTILES',
            'GENERAL',
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ESTRUCTURA JERÁRQUICA DEL MENÚ
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Devuelve la estructura completa del menú jerárquico del sistema.
     *
     * Tipos de nodo:
     *   'category' → Acordeón de nivel 1 (sección principal del menú)
     *   'group'    → Submenú colapsable (nivel 2 ó 3)
     *   'item'     → Enlace hoja; requiere 'clave' en $_SESSION['permisos']
     *
     * Claves de cada nodo:
     *   type   → 'category' | 'group' | 'item'
     *   cat    → ID único de categoría (solo en 'category')
     *   clave  → CLAVE del permiso en BD (solo en 'item')
     *   label  → Texto visible
     *   icon   → Clase FontAwesome (opcional; si se omite, se resuelve por iconoPorClave)
     *   route  → ?route= a usar (opcional; si se omite, usa la 'clave')
     *   children → array de nodos hijo (en 'category' y 'group')
     *
     * @return array
     */
    public static function getMenuStructure()
    {
        return [

            // ═══════════════════════════════════════════════
            //  1. ARCHIVOS
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'ARCHIVOS',
                'label' => 'Archivos', 'icon' => 'fa-solid fa-folder',
                'children' => [
                    ['type'=>'item','clave'=>'MNU_ARC_PRESTADORES',   'label'=>'Prestadores',         'route'=>'prestadores','icon'=>'fa-solid fa-stethoscope'],
                    ['type'=>'item','clave'=>'MNU_ARC_ESPECIALIDADES', 'label'=>'Especialidades',       'icon'=>'fa-solid fa-briefcase-medical'],
                    ['type'=>'item','clave'=>'MNU_ARC_OBRAS_SOCIALES', 'label'=>'Obras Sociales',       'icon'=>'fa-solid fa-building-columns'],
                    ['type'=>'item','clave'=>'MNU_ARC_NOMENCLADOR',   'label'=>'Nomenclador Nacional', 'icon'=>'fa-solid fa-book-medical'],
                    ['type'=>'item','clave'=>'MNU_ARC_NBU',           'label'=>'NBU',                  'icon'=>'fa-solid fa-file-medical'],
                    ['type'=>'item','clave'=>'MNU_ARC_KAIROS',        'label'=>'Kairos',               'icon'=>'fa-solid fa-clock-rotate-left'],
                    [
                        'type'=>'group','label'=>'Tablas','icon'=>'fa-solid fa-table-cells',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_LOCALIDADES', 'label'=>'Localidades'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_ZONAS',       'label'=>'Zonas'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_GRUPO_WEB',   'label'=>'Grupo Web'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_EMPRESAS',    'label'=>'Empresas'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_BANCOS',      'label'=>'Bancos'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_CIERRE_AUM',  'label'=>'Cierre de Aumentos'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_PCT_AUM',     'label'=>'% Aumento Presupuesto'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_GRP_CARTILLA','label'=>'Grupos Cartilla'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_TIT_CARTILLA','label'=>'Título Cartilla'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_CONC_INT',    'label'=>'Conceptos de Internación'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_PROC_INT',    'label'=>'Procesos de Internación'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_TIPO_CERT',   'label'=>'Tipos Certificados'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_VAL_VENTA',   'label'=>'Valores Lista Venta'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_CTRL_WEB',    'label'=>'Control Actualización Web'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_URL_REF',     'label'=>'URL e Referencias'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_TIPOS_CX',    'label'=>'Tipos CX para Estadística'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_SUBCAT',      'label'=>'Subcategoría Prestadores'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_REP_LEY',     'label'=>'Reportes Leyendas'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_HOMOL',       'label'=>'Homologación Nombres Nomenclador'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_GRP_AUT',     'label'=>'Grupos Autorizaciones'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_MOT_DEB',     'label'=>'Motivos de Débitos'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_GRP_NN',      'label'=>'Grupos N.Nacional'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_SUBGRP_NN',   'label'=>'Subgrupos N.Nacional'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_AJ_IMP',      'label'=>'Ajustes Aud. Impuestos'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_AJ_AFI',      'label'=>'Ajustes Aud. Afiliado'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_TXT_REC',     'label'=>'Textos de Rechazo'],
                            ['type'=>'item','clave'=>'MNU_ARC_TAB_DIAG',        'label'=>'Diagnósticos'],
                        ]
                    ],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  2. CARGA DATOS
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'CARGA_DATOS',
                'label' => 'Carga Datos', 'icon' => 'fa-solid fa-upload',
                'children' => [
                    [
                        'type'=>'group','label'=>'Carga de Prestaciones','icon'=>'fa-solid fa-file-medical',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_CD_PREST_INGRESO','label'=>'Ingreso'],
                            ['type'=>'item','clave'=>'MNU_CD_PREST_MOD',    'label'=>'Modificación Nuevo'],
                        ]
                    ],
                    [
                        'type'=>'group','label'=>'Registración de Facturas','icon'=>'fa-solid fa-file-invoice',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_CD_FAC_INGRESO','label'=>'Ingreso'],
                            [
                                'type'=>'group','label'=>'Listados','icon'=>'fa-solid fa-list',
                                'children' => [
                                    ['type'=>'item','clave'=>'MNU_CD_FAC_LST_DET',    'label'=>'Detallado x OS'],
                                    ['type'=>'item','clave'=>'MNU_CD_FAC_LST_TOT_AC', 'label'=>'Totales Acumulados x OS'],
                                    ['type'=>'item','clave'=>'MNU_CD_FAC_LST_TOT_CMP','label'=>'Totales Comparados x OS'],
                                ]
                            ],
                        ]
                    ],
                    ['type'=>'item','clave'=>'MNU_CD_DIAG','label'=>'Carga de Diagnósticos','icon'=>'fa-solid fa-notes-medical'],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  3. AUTORIZACIONES
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'AUTORIZACIONES',
                'label' => 'Autorizaciones', 'icon' => 'fa-solid fa-clipboard-check',
                'children' => [
                    ['type'=>'item','clave'=>'MNU_AUT_AUTORIZACIONES','label'=>'Autorizaciones','icon'=>'fa-solid fa-clipboard-check'],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  4. AUDIT. FACTURAC.
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'AUDIT_FAC',
                'label' => 'Audit. Facturac.', 'icon' => 'fa-solid fa-magnifying-glass-dollar',
                'children' => [
                    ['type'=>'item','clave'=>'MNU_AF_AUDITAR',        'label'=>'Auditar Facturación',          'icon'=>'fa-solid fa-magnifying-glass-dollar'],
                    ['type'=>'item','clave'=>'MNU_AF_CIERRE_AUTO',    'label'=>'Cierre Automático',            'icon'=>'fa-solid fa-lock'],
                    ['type'=>'item','clave'=>'MNU_AF_LIQ_AUDITAR',    'label'=>'Liquidaciones a Auditar',      'icon'=>'fa-solid fa-file-circle-question'],
                    ['type'=>'item','clave'=>'MNU_AF_LIQ_SIN_CERRAR', 'label'=>'Liquidaciones sin Cerrar',     'icon'=>'fa-solid fa-file-circle-exclamation'],
                    ['type'=>'item','clave'=>'MNU_AF_REIMP_LIQ',      'label'=>'Reimpresión de Liquidaciones', 'icon'=>'fa-solid fa-print'],
                    ['type'=>'item','clave'=>'MNU_AF_ENV_DEBITO',     'label'=>'Envío avisos de débito',       'icon'=>'fa-solid fa-envelope'],
                    ['type'=>'item','clave'=>'MNU_AF_EXP_PREST_GEN',  'label'=>'Export. Prestaciones General', 'icon'=>'fa-solid fa-file-export'],
                    ['type'=>'item','clave'=>'MNU_AF_EXP_PREST_AYER', 'label'=>'Export. Hasta Ayer',           'icon'=>'fa-solid fa-file-export'],
                    ['type'=>'item','clave'=>'MNU_AF_EXP_PREST_VENT', 'label'=>'Export. Hasta Ayer Ventas',    'icon'=>'fa-solid fa-file-export'],
                    ['type'=>'item','clave'=>'MNU_AF_CALC_CAPITAS',   'label'=>'Cálculo Cápitas',              'icon'=>'fa-solid fa-calculator'],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  5. CONTADURÍA
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'CONTADURIA',
                'label' => 'Contaduría', 'icon' => 'fa-solid fa-coins',
                'children' => [
                    [
                        'type'=>'group','label'=>'Liquidaciones Cerradas','icon'=>'fa-solid fa-file-invoice-dollar',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_CONT_LC_PERIODO', 'label'=>'x Período'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_OS_PER',  'label'=>'x O.S. y Período'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_CERR_PER','label'=>'Cerradas por Período'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_OS_FCH',  'label'=>'x O.Social y Fecha Cierre'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_CUIDAR',  'label'=>'Pago de CUIDAR'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_VARIOS',  'label'=>'x Varios Prestadores'],
                            ['type'=>'item','clave'=>'MNU_CONT_LC_FECHAS',  'label'=>'Reporte entre Fechas'],
                        ]
                    ],
                    ['type'=>'item','clave'=>'MNU_CONT_LIQ_EST',    'label'=>'Liquidaciones Estimadas',   'icon'=>'fa-solid fa-file-circle-plus'],
                    ['type'=>'item','clave'=>'MNU_CONT_SIN_CERRAR', 'label'=>'Sin cerrar hasta Ayer',     'icon'=>'fa-solid fa-clock'],
                    ['type'=>'item','clave'=>'MNU_CONT_SALDOS',     'label'=>'Listado de Saldos',         'icon'=>'fa-solid fa-scale-balanced'],
                    ['type'=>'item','clave'=>'MNU_CONT_RECIBOS',    'label'=>'Recibos Faltantes',         'icon'=>'fa-solid fa-file-circle-xmark'],
                    ['type'=>'item','clave'=>'MNU_CONT_FECHAS_PAGO','label'=>'Fechas de Pago',            'icon'=>'fa-solid fa-calendar-days'],
                    ['type'=>'item','clave'=>'MNU_CONT_RES_OPAGO',  'label'=>'Resumen O.Pago',            'icon'=>'fa-solid fa-receipt'],
                    ['type'=>'item','clave'=>'MNU_CONT_HIST_PREST', 'label'=>'Historial x Prestador',    'icon'=>'fa-solid fa-clock-rotate-left'],
                    ['type'=>'item','clave'=>'MNU_CONT_ANUL_OPAGO', 'label'=>'Anulación O.Pago',         'icon'=>'fa-solid fa-ban'],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  6. RESÚMENES / REPORTES
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'REPORTES',
                'label' => 'Resúmenes / Reportes', 'icon' => 'fa-solid fa-chart-bar',
                'children' => [
                    ['type'=>'item','clave'=>'MNU_REP_HIST_CLIN',      'label'=>'Historias Clínicas',              'icon'=>'fa-solid fa-book'],
                    ['type'=>'item','clave'=>'MNU_REP_AFIL_PADRON',    'label'=>'Afil. Fuera Padrón',              'icon'=>'fa-solid fa-users-slash'],
                    ['type'=>'item','clave'=>'MNU_REP_AFIL_PAD_PREST', 'label'=>'Afil. fuera padrón x prestador', 'icon'=>'fa-solid fa-user-slash'],
                    ['type'=>'item','clave'=>'MNU_REP_VENTAS_GEN',     'label'=>'Lista de Ventas (General)',       'icon'=>'fa-solid fa-list'],
                    ['type'=>'item','clave'=>'MNU_REP_VENTAS_GRP',     'label'=>'Lista de Ventas (Grupo)',         'icon'=>'fa-solid fa-layer-group'],
                    ['type'=>'item','clave'=>'MNU_REP_VENTAS_AYER',    'label'=>'Lista de Ventas (Ayer)',          'icon'=>'fa-solid fa-calendar-minus'],
                    ['type'=>'item','clave'=>'MNU_REP_VENTAS_AYER_GRP','label'=>'Lista de Ventas (Ayer Grupo)',    'icon'=>'fa-solid fa-calendar-week'],
                    ['type'=>'item','clave'=>'MNU_REP_NORM_650',       'label'=>'Normativa 650',                  'icon'=>'fa-solid fa-gavel'],
                    ['type'=>'item','clave'=>'MNU_REP_AUD_CTRL',       'label'=>'Auditoría Control Prestadores',  'icon'=>'fa-solid fa-magnifying-glass-chart'],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  7. CONFIGURACIÓN
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'CONFIGURACION',
                'label' => 'Configuración', 'icon' => 'fa-solid fa-gear',
                'children' => [
                    ['type'=>'item','clave'=>'MNU_CFG_CAMBIO_USR','label'=>'Cambio de Usuario',     'icon'=>'fa-solid fa-user-pen'],
                    ['type'=>'item','clave'=>'MNU_CFG_CARGA_USR', 'label'=>'Carga de Usuarios',     'icon'=>'fa-solid fa-user-plus'],
                    ['type'=>'item','clave'=>'MNU_CFG_CONV_PAD',  'label'=>'Conversión de Padrones','icon'=>'fa-solid fa-arrows-rotate'],
                    ['type'=>'item','clave'=>'MNU_CFG_EMAIL_MAS', 'label'=>'Envío eMail Masivo',    'icon'=>'fa-solid fa-envelope-open-text'],
                    ['type'=>'item','clave'=>'MNU_CFG_CARTILLA',  'label'=>'Cartilla',              'icon'=>'fa-solid fa-address-book'],
                    [
                        'type'=>'group','label'=>'Logs y Mails','icon'=>'fa-solid fa-scroll',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_MAILS',     'label'=>'Mails Enviados'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_PAGOS',     'label'=>'Pagos'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_RES',       'label'=>'Resumen'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_PREST',     'label'=>'Prestadores'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_DEB',       'label'=>'Débitos'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_LIQ_SC',    'label'=>'Liq. sin cerrar'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_COSEG',     'label'=>'Coseguros'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_HIST_AUM',  'label'=>'Hist PAumentos'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_PDF_AUT',   'label'=>'PDF Autorizaciones'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_NOV_OS',    'label'=>'Novedades OS'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_USO_PREST', 'label'=>'Uso Prestaciones'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_MAS_PREST', 'label'=>'Masivo Prestadores'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_MAS_ERR',   'label'=>'Masivo Error'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_BAJADA',    'label'=>'Bajada Órdenes MO'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_TOPE',      'label'=>'Valor Tope Carga'],
                            ['type'=>'item','clave'=>'MNU_CFG_LOG_PAGOS_AUT', 'label'=>'Pagos Resumen Automático'],
                        ]
                    ],
                    [
                        'type'=>'group','label'=>'Cierres','icon'=>'fa-solid fa-lock',
                        'children' => [
                            ['type'=>'item','clave'=>'MNU_CFG_CIE_PERIODO','label'=>'Cierre de período'],
                            ['type'=>'item','clave'=>'MNU_CFG_CIE_AUM',   'label'=>'Cierre de Aumentos'],
                        ]
                    ],
                ]
            ],

            // ═══════════════════════════════════════════════
            //  8. ÚTILES
            // ═══════════════════════════════════════════════
            [
                'type' => 'category', 'cat' => 'UTILES',
                'label' => 'Útiles', 'icon' => 'fa-solid fa-toolbox',
                'children' => [
                    [
                        'type'  => 'item',
                        'clave' => 'MNU_CHAT_TELEGRAM',
                        'label' => 'Chat Telegram',
                        'route' => 'chat',
                        'icon'  => 'fa-brands fa-telegram',
                    ],
                ]
            ],

        ]; // end getMenuStructure
    }

    /**
     * Devuelve el ícono FontAwesome para una CLAVE_CATEGORIA (encabezados de sección).
     *
     * @param  string $claveCategoria
     * @return string
     */
    public static function iconoCategoria($claveCategoria)
    {
        $mapa = [
            'ARCHIVOS'        => 'fa-solid fa-folder',
            'AFILIADOS'       => 'fa-solid fa-id-card',
            'AUTORIZACIONES'  => 'fa-solid fa-clipboard-check',
            'ORDENES'         => 'fa-solid fa-clipboard-list',
            'INTERNACIONES'   => 'fa-solid fa-bed-pulse',
            'FACTURACION'     => 'fa-solid fa-file-invoice-dollar',
            'CARTILLA'        => 'fa-solid fa-address-book',
            'CALLCENTER'      => 'fa-solid fa-headset',
            'TELEMEDICINA'    => 'fa-solid fa-video',
            'CONVENIOS'       => 'fa-solid fa-handshake',
            'NEGOCIACIONES'   => 'fa-solid fa-scale-balanced',
            'EXPEDIENTES'     => 'fa-solid fa-folder-open',
            'VALORES'         => 'fa-solid fa-dollar-sign',
            'TRASLADO'        => 'fa-solid fa-ambulance',
            'RRHH'            => 'fa-solid fa-people-group',
            'ADMIN'           => 'fa-solid fa-user-shield',
            'CONFIGURACION'   => 'fa-solid fa-gear',
            'REPORTES'        => 'fa-solid fa-chart-bar',
            'UTILES'          => 'fa-solid fa-toolbox',
        ];

        $key = strtoupper($claveCategoria);
        return isset($mapa[$key]) ? $mapa[$key] : 'fa-solid fa-layer-group';
    }
}
