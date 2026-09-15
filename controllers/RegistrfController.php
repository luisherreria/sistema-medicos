<?php
/**
 * controllers/RegistrfController.php
 * Módulo: Carga Datos → Registración de Facturas
 *
 * Acciones:
 *   index()             → Vista HTML del listado
 *   listado()           → AJAX JSON: grilla paginada
 *   buscarPrestador()   → AJAX JSON: autocomplete / exacto en ebamp
 *   buscarObraSocial()  → AJAX JSON: autocomplete / exacto en obrasoc
 *   osDelPrestador()    → AJAX JSON: OS habilitadas del prestador (obramed)
 *   guardar()           → AJAX POST: inserta factura (columnas reales)
 *   rptPrioritarios()   → HTML preview (RPT_CONFLIF / prioritarios)
 *   rptRecibos()        → HTML preview recibos ingresados del período
 */

class RegistrfController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        require_once __DIR__ . '/../views/registrf/list.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — GRILLA
    // ══════════════════════════════════════════════════════════════════════

    public function listado(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        header('Content-Type: application/json; charset=utf-8');

        require_once __DIR__ . '/../models/RegistrfModel.php';
        $model = new RegistrfModel();

        $busqueda = trim($_GET['busqueda'] ?? '');
        $pagina   = max(1, (int)($_GET['pagina']    ?? 1));
        $porPag   = max(1, min(200, (int)($_GET['por_pagina'] ?? 50)));
        $orderCol = trim($_GET['order_col'] ?? 'COPERIODO');
        $orderDir = strtoupper(trim($_GET['order_dir'] ?? 'DESC'));
        $offset   = ($pagina - 1) * $porPag;

        try {
            $res     = $model->obtenerFacturas($busqueda, $porPag, $offset, $orderCol, $orderDir);
            $total   = $res['total'];
            $datos   = $res['datos'];
            $paginas = $total > 0 ? (int) ceil($total / $porPag) : 1;

            $this->utf8($datos);

            echo json_encode([
                'ok'      => true,
                'total'   => $total,
                'paginas' => $paginas,
                'pagina'  => $pagina,
                'datos'   => $datos,
            ], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — PRESTADOR / OS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?action=buscar_prestador&q=&exact=0|1&ocultar_adef=1&ocultar_baja=1
     *
     * exact=1 busca por codigo o matricula (Enter / blur).
     * Devuelve flags VFP: recomenda (isprioritario), confact, valereci, conflicto.
     */
    public function buscarPrestador(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        header('Content-Type: application/json; charset=utf-8');

        $q           = trim($_GET['q'] ?? '');
        $exact       = ($_GET['exact'] ?? '0') === '1';
        $ocultarAdef = ($_GET['ocultar_adef'] ?? '1') !== '0';
        $ocultarBaja = ($_GET['ocultar_baja'] ?? '1') !== '0';

        if ($q === '') { echo json_encode(['ok' => true, 'datos' => []]); exit; }

        $cols = $this->sqlColsEbampPrestador();

        try {
            if ($exact) {
                $stmt = $this->db()->prepare(
                    "SELECT {$cols}
                     FROM ebamp
                     WHERE TRIM(codigo) = :cod OR TRIM(matricula) = :mat
                     ORDER BY CASE WHEN TRIM(codigo) = :cod2 THEN 0 ELSE 1 END
                     LIMIT 1"
                );
                $stmt->execute([':cod' => $q, ':mat' => $q, ':cod2' => $q]);
            } else {
                $conds = ['(nombre LIKE :b1 OR codigo LIKE :b2 OR matricula LIKE :b3)'];
                $params = [
                    ':b1' => '%' . $q . '%',
                    ':b2' => '%' . $q . '%',
                    ':b3' => '%' . $q . '%',
                ];
                if ($ocultarAdef) {
                    $conds[] = "TRIM(categ) != 'ADEF'";
                }
                if ($ocultarBaja) {
                    $conds[] = "(fechabaja IS NULL
                              OR fechabaja = '0000-00-00'
                              OR fechabaja = '0000-00-00 00:00:00'
                              OR fechabaja > DATE_SUB(CURDATE(), INTERVAL 180 DAY))";
                }
                $stmt = $this->db()->prepare(
                    "SELECT {$cols} FROM ebamp WHERE " . implode(' AND ', $conds)
                    . " ORDER BY nombre ASC LIMIT 20"
                );
                $stmt->execute($params);
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->utf8($rows);

            foreach ($rows as &$r) {
                $r['isprioritario'] = self::flagActivo($r['recomenda'] ?? '');
                $r['isconfact']     = self::flagActivo($r['confact'] ?? '');
                $r['isvalereci']    = self::flagActivo($r['valereci'] ?? '');
                $r['isconflicto']   = self::flagActivo($r['conflicto'] ?? '');
            }
            unset($r);

            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** GET ?action=buscar_os&q=&exact=0|1&prestador= */
    public function buscarObraSocial(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        header('Content-Type: application/json; charset=utf-8');

        $q         = trim($_GET['q'] ?? '');
        $exact     = ($_GET['exact'] ?? '0') === '1';
        $prestador = trim($_GET['prestador'] ?? '');
        if ($q === '') { echo json_encode(['ok' => true, 'datos' => []]); exit; }

        try {
            $db = $this->db();

            if ($exact) {
                $stmt = $db->prepare(
                    "SELECT TRIM(TACODIGO) AS cosoc, TRIM(TADESCRIP) AS nombre
                     FROM obrasoc
                     WHERE TRIM(TACODIGO) = :cod LIMIT 1"
                );
                $stmt->execute([':cod' => $q]);
            } else {
                $val    = '%' . $q . '%';
                $sqlCat = "SELECT TRIM(TACODIGO) AS cosoc, TRIM(TADESCRIP) AS nombre
                           FROM obrasoc
                           WHERE (TAFECHAFIN IS NULL OR TAFECHAFIN='0000-00-00 00:00:00' OR TAFECHAFIN>CURDATE())
                             AND (TACODIGO LIKE :b1 OR TADESCRIP LIKE :b2)
                           ORDER BY TADESCRIP ASC LIMIT 15";
                $params = [':b1' => $val, ':b2' => $val];

                if ($prestador !== '') {
                    $ids = $this->idsPrestador($db, $prestador);
                    if ($ids) {
                        $in = implode(',', array_fill(0, count($ids), '?'));
                        $sqlOs = "SELECT DISTINCT TRIM(om.OBRASOC) AS cosoc,
                                       COALESCE(os.TADESCRIP, om.NOMOBRA, om.OBRASOC) AS nombre
                                FROM obramed om
                                LEFT JOIN obrasoc os ON TRIM(os.TACODIGO) = TRIM(om.OBRASOC)
                                WHERE TRIM(om.MEDICO) IN ($in)
                                  AND (om.FECHABAJA IS NULL
                                       OR om.FECHABAJA = '0000-00-00 00:00:00'
                                       OR om.FECHABAJA > CURDATE())
                                  AND (om.OBRASOC LIKE ? OR COALESCE(os.TADESCRIP, om.NOMOBRA, '') LIKE ?)
                                ORDER BY nombre ASC LIMIT 15";
                        $stmt = $db->prepare($sqlOs);
                        $stmt->execute(array_merge($ids, [$val, $val]));
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if ($rows) {
                            $this->utf8($rows);
                            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
                            exit;
                        }
                    }
                }

                $stmt = $db->prepare($sqlCat);
                $stmt->execute($params);
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->utf8($rows);
            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /**
     * GET ?action=os_prestador&codigo=XXXX
     * OS activas del prestador (obramed). Si hay una sola, el form la completa.
     */
    public function osDelPrestador(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo === '') { echo json_encode(['ok' => true, 'datos' => []]); exit; }

        try {
            $db  = $this->db();
            $ids = $this->idsPrestador($db, $codigo);
            if (!$ids) {
                echo json_encode(['ok' => true, 'datos' => []]);
                exit;
            }
            $in   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare(
                "SELECT DISTINCT TRIM(om.OBRASOC) AS cosoc,
                        COALESCE(os.TADESCRIP, om.NOMOBRA, om.OBRASOC) AS nombre
                 FROM obramed om
                 LEFT JOIN obrasoc os ON TRIM(os.TACODIGO) = TRIM(om.OBRASOC)
                 WHERE TRIM(om.MEDICO) IN ($in)
                   AND (om.FECHABAJA IS NULL
                        OR om.FECHABAJA = '0000-00-00 00:00:00'
                        OR om.FECHABAJA > CURDATE())
                 ORDER BY nombre ASC"
            );
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->utf8($rows);
            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — GUARDAR
    // ══════════════════════════════════════════════════════════════════════

    public function guardar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');
        header('Content-Type: application/json; charset=utf-8');

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $g = fn(string $k): string => trim($_POST[$k] ?? '');
        $n = fn(string $k): float  => (float)($_POST[$k] ?? 0);
        $i = fn(string $k): int    => (int)($_POST[$k] ?? 0);

        $coprestado = $g('COPRESTADO');
        $coobrasoc  = $g('COOBRASOC');
        $cosucfac   = str_pad((string)preg_replace('/\D/', '', $g('COSUCFAC')), 4, '0', STR_PAD_LEFT);
        $conrofac   = str_pad((string)preg_replace('/\D/', '', $g('CONROFAC')), 8, '0', STR_PAD_LEFT);
        $periodo    = strtoupper(str_replace('/', '', $g('COPERIODO')));

        if ($periodo === '')         { $this->jsonError('Debe colocar el Período...'); }
        if (!$coprestado)            { $this->jsonError('El código de Prestador es requerido.'); }
        if (!$coobrasoc)             { $this->jsonError('El código de Obra Social es requerido.'); }
        if ($cosucfac === '0000')    { $this->jsonError('Ingresá el punto de venta de la factura.'); }
        if ($conrofac === '00000000'){ $this->jsonError('Ingresá el número de factura.'); }

        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        try {
            $db = $this->db();

            $conomprest = $g('CONOMPREST');
            $cocateg    = $g('COCATEG');
            if ($conomprest === '' || $cocateg === '') {
                $ep = $db->prepare(
                    "SELECT TRIM(nombre) AS n, TRIM(categ) AS c
                     FROM ebamp
                     WHERE TRIM(codigo)=:c OR TRIM(matricula)=:m
                     LIMIT 1"
                );
                $ep->execute([':c' => $coprestado, ':m' => $coprestado]);
                $row = $ep->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    if ($conomprest === '') $conomprest = (string)($row['n'] ?? '');
                    if ($cocateg    === '') $cocateg    = (string)($row['c'] ?? '');
                }
            }

            $fecha     = $g('COFECHA')    ?: date('Y-m-d');
            $fecFac    = $g('COFECFAC')   ?: $fecha;
            $fecRecib  = $g('COFECRECIB') ?: $fecha;
            $total     = $n('COTOTALFAC');
            if ($total <= 0) {
                $total = $n('COIMPORTE') + $n('COIVA') + $n('COCOSEGURO');
            }

            require_once __DIR__ . '/../models/RegistrfModel.php';
            $model = new RegistrfModel();

            // Mapa lógico → valor. insertar() descarta columnas que no existan.
            $model->insertar([
                'COFECHA'     => $fecha,
                'COPERIODO'   => $periodo,
                'COOBRASOC'   => $coobrasoc,
                'COCATEG'     => $cocateg,
                'COPRESTADO'  => $coprestado,
                'CONOMPREST'  => $conomprest,
                'COSUCFAC'    => $cosucfac,
                'CONROFAC'    => $conrofac,
                'COFECFAC'    => $fecFac,
                'COFECRECIB'  => $fecRecib,
                'COTIPO'      => $g('COTIPO') ?: 'F',
                'COCANTIDAD'  => $i('COCANTIDAD'),
                'COIMPORTE'   => $n('COIMPORTE'),
                'COIVA'       => $n('COIVA'),
                'COCOSEGURO'  => $n('COCOSEGURO'),
                'COTOTALFAC'  => $total,
                'COMONTO'     => $n('COMONTO'),
                'COCANTPREST' => $i('COCANTPREST'),
                'COTIENEFAC'  => $g('COTIENEFAC') ?: 'S',
                'COEMPRESA'   => $g('COEMPRESA'),
                'COUSUARIO'   => $usr,
                'COFECCARGA'  => date('Y-m-d H:i:s'),
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Factura registrada correctamente.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  REPORTES
    // ══════════════════════════════════════════════════════════════════════

    /** GET ?action=rpt_prioritarios&periodo=AA/MM|&tipo=prioritario|conflicto */
    public function rptPrioritarios(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');

        $periodo = strtoupper(trim(str_replace('/', '', $_GET['periodo'] ?? '')));
        $tipo    = trim($_GET['tipo'] ?? 'prioritario');
        if (!in_array($tipo, ['prioritario', 'conflicto'], true)) {
            $tipo = 'prioritario';
        }

        if ($periodo === '') {
            $this->rptError('Debe colocar el Período...');
            return;
        }

        require_once __DIR__ . '/../models/RegistrfModel.php';
        try {
            $model = new RegistrfModel();
            $filas = $model->reportePorFlag($periodo, $tipo);
            $this->utf8($filas);
        } catch (PDOException $e) {
            $this->rptError('Error al generar el reporte: ' . $e->getMessage());
            return;
        }

        $titulo = $tipo === 'conflicto'
            ? 'Facturas — Prestadores Conflictivos'
            : 'Facturas — Prestadores Prioritarios';
        $this->renderReporte($titulo, $periodo, $filas, $tipo);
    }

    /** GET ?action=rpt_recibos&periodo=AAMM */
    public function rptRecibos(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_CD_FAC_INGRESO');

        $periodo = strtoupper(trim(str_replace('/', '', $_GET['periodo'] ?? '')));
        if ($periodo === '') {
            $this->rptError('Debe colocar el Período...');
            return;
        }

        require_once __DIR__ . '/../models/RegistrfModel.php';
        try {
            $model = new RegistrfModel();
            $filas = $model->reportePorFlag($periodo, 'recibos');
            $this->utf8($filas);
        } catch (PDOException $e) {
            $this->rptError('Error al generar el reporte: ' . $e->getMessage());
            return;
        }

        $this->renderReporte('Recibos Ingresados', $periodo, $filas, 'recibos');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    public static function flagActivo(string $v): bool
    {
        $v = strtoupper(trim($v));
        return $v !== '' && !in_array($v, ['0', 'N', 'F', '.F.', 'FALSE', 'NO'], true);
    }

    private function sqlColsEbampPrestador(): string
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }
        $wanted = [
            'codigo'    => 'TRIM(codigo) AS codigo',
            'matricula' => 'TRIM(matricula) AS matricula',
            'nombre'    => 'TRIM(nombre) AS nombre',
            'categ'     => 'TRIM(categ) AS categ',
            'empresa'   => 'TRIM(empresa) AS empresa',
            'recomenda' => 'TRIM(recomenda) AS recomenda',
            'confact'   => 'TRIM(confact) AS confact',
            'valereci'  => 'TRIM(valereci) AS valereci',
            'conflicto' => 'TRIM(conflicto) AS conflicto',
            'fechabaja' => 'fechabaja',
        ];
        $have = [];
        try {
            foreach ($this->db()->query('SHOW COLUMNS FROM ebamp') as $row) {
                $have[strtolower((string)$row['Field'])] = true;
            }
        } catch (PDOException $e) {
            $sql = implode(",\n                 ", array_values($wanted));
            return $sql;
        }
        $sel = [];
        foreach ($wanted as $col => $expr) {
            if (isset($have[$col])) {
                $sel[] = $expr;
            }
        }
        $sql = $sel ? implode(",\n                 ", $sel) : implode(",\n                 ", array_values($wanted));
        return $sql;
    }

    /** codigo + matricula de ebamp para cruzar con obramed.MEDICO */
    private function idsPrestador(PDO $db, string $codigo): array
    {
        $stmt = $db->prepare(
            "SELECT TRIM(codigo) AS codigo, TRIM(matricula) AS matricula
             FROM ebamp
             WHERE TRIM(codigo) = :c OR TRIM(matricula) = :m
             LIMIT 1"
        );
        $stmt->execute([':c' => $codigo, ':m' => $codigo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [$codigo];
        }
        $ids = [];
        foreach (['codigo', 'matricula'] as $k) {
            $v = trim((string)($row[$k] ?? ''));
            if ($v !== '' && !in_array($v, $ids, true)) {
                $ids[] = $v;
            }
        }
        return $ids ?: [$codigo];
    }

    private function renderReporte(string $titulo, string $periodo, array $filas, string $tipo): void
    {
        $periodoFmt = strlen($periodo) === 4
            ? substr($periodo, 0, 2) . '/' . substr($periodo, 2, 2)
            : $periodo;
        $usuario = htmlspecialchars($_SESSION['user']['username'] ?? '', ENT_QUOTES, 'UTF-8');
        $hoy     = date('d/m/Y H:i');
        $esc     = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
        $fmtF    = static function ($s): string {
            $s = (string)$s;
            if ($s === '' || str_starts_with($s, '0000-00-00')) return '—';
            $p = explode('-', substr($s, 0, 10));
            return count($p) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : $s;
        };

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
        echo '<title>' . $esc($titulo) . '</title>';
        echo '<style>
            body{font-family:"Segoe UI",Arial,sans-serif;font-size:12px;color:#1e293b;margin:24px;}
            h1{font-size:16px;margin:0 0 4px;color:#0d47a1;}
            .meta{color:#64748b;font-size:11px;margin-bottom:14px;}
            table{width:100%;border-collapse:collapse;}
            th{background:#1e293b;color:#fff;font-size:10px;letter-spacing:.04em;text-align:left;padding:6px 8px;}
            td{border-bottom:1px solid #e2e8f0;padding:5px 8px;font-size:11px;}
            tr:nth-child(even) td{background:#f8fafc;}
            .empty{padding:24px;text-align:center;color:#64748b;}
            .mono{font-family:ui-monospace,monospace;}
            @media print{button{display:none;} body{margin:10px;}}
        </style></head><body>';
        echo '<button onclick="window.print()" style="margin-bottom:12px;padding:6px 12px;">Imprimir</button>';
        echo '<h1>' . $esc($titulo) . '</h1>';
        echo '<div class="meta">Período <strong>' . $esc($periodoFmt) . '</strong>'
           . ' &nbsp;·&nbsp; ' . count($filas) . ' registro(s)'
           . ' &nbsp;·&nbsp; ' . $esc($hoy)
           . ' &nbsp;·&nbsp; Usuario ' . $usuario
           . ($tipo !== '' ? ' &nbsp;·&nbsp; ' . $esc($tipo) : '')
           . '</div>';

        if (!$filas) {
            echo '<div class="empty">No hay facturas para este período.</div></body></html>';
            return;
        }

        echo '<table><thead><tr>
                <th>Período</th><th>Fecha</th><th>Código</th><th>Prestador</th>
                <th>Suc</th><th>N° Factura</th><th>F. Factura</th>
              </tr></thead><tbody>';
        foreach ($filas as $r) {
            echo '<tr>'
               . '<td class="mono">' . $esc($periodoFmt) . '</td>'
               . '<td class="mono">' . $esc($fmtF($r['cofecha'] ?? '')) . '</td>'
               . '<td class="mono">' . $esc($r['coprestado'] ?? '') . '</td>'
               . '<td>' . $esc($r['conomprest'] ?: ($r['nombre_ebamp'] ?? '')) . '</td>'
               . '<td class="mono">' . $esc($r['cosucfac'] ?? '') . '</td>'
               . '<td class="mono">' . $esc($r['conrofac'] ?? '') . '</td>'
               . '<td class="mono">' . $esc($fmtF($r['cofecfac'] ?? '')) . '</td>'
               . '</tr>';
        }
        echo '</tbody></table></body></html>';
    }

    private function rptError(string $msg): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Reporte</title></head><body style="font-family:sans-serif;padding:32px;">';
        echo '<p style="color:#b91c1c;font-weight:700;">' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><button onclick="window.close()">Cerrar</button></p></body></html>';
    }

    private function utf8(array &$data): void
    {
        array_walk_recursive($data, function (&$v) {
            if (is_string($v)) {
                $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
            }
        });
    }

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sesión expirada.']);
                exit;
            }
            header('Location: ' . $this->baseUrl() . '?route=login');
            exit;
        }
    }

    private function requirePermiso(string $clave): void
    {
        $permisos = $_SESSION['permisos'] ?? [];
        foreach ($permisos as $p) {
            if (isset($p['CLAVE']) && ($p['CLAVE'] === $clave || $p['CLAVE'] === 'MNU_REG_FACTURAS')) {
                return;
            }
        }
        // Si el usuario tiene cualquiera de las dos claves del módulo, ok
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Sin permiso para esta operación.']);
            exit;
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    private function db(): PDO
    {
        require_once __DIR__ . '/../config/database.php';
        return getDBConnection();
    }

    private function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $dir    = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return "{$scheme}://{$host}{$dir}/index.php";
    }

    private function jsonError(string $msg): never
    {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
