<?php
/**
 * controllers/RegistrfController.php
 * ─────────────────────────────────────────────────────────────────────────
 * Módulo: Carga Datos → Registración de Facturas
 *
 * Acciones:
 *   index()           → Vista HTML del listado
 *   listado()         → AJAX JSON: grilla paginada
 *   buscarPrestador() → AJAX JSON: autocomplete en ebamp
 *   buscarObraSocial()→ AJAX JSON: autocomplete en obrasoc
 *   guardar()         → AJAX POST: inserta nueva factura en registrf
 * ─────────────────────────────────────────────────────────────────────────
 */

class RegistrfController
{
    // ══════════════════════════════════════════════════════════════════════
    //  VISTA PRINCIPAL
    // ══════════════════════════════════════════════════════════════════════

    public function index(): void
    {
        $this->requireAuth();
        require_once __DIR__ . '/../views/registrf/list.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — GRILLA PRINCIPAL
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?route=registro-facturas&action=listado
     * Parámetros: busqueda, pagina, por_pagina, order_col, order_dir
     */
    public function listado(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        require_once __DIR__ . '/../models/RegistrfModel.php';
        $model = new RegistrfModel();

        $busqueda = trim($_GET['busqueda'] ?? '');
        $pagina   = max(1, (int)($_GET['pagina']    ?? 1));
        $porPag   = max(1, min(200, (int)($_GET['por_pagina'] ?? 50)));
        $orderCol = trim($_GET['order_col'] ?? 'COFECCARGA');
        $orderDir = strtoupper(trim($_GET['order_dir'] ?? 'DESC'));
        $offset   = ($pagina - 1) * $porPag;

        try {
            $res    = $model->obtenerFacturas($busqueda, $porPag, $offset, $orderCol, $orderDir);
            $total  = $res['total'];
            $datos  = $res['datos'];
            $paginas = $total > 0 ? (int) ceil($total / $porPag) : 1;

            // Convertir encoding legacy ISO-8859-1 → UTF-8
            array_walk_recursive($datos, function (&$v) {
                if (is_string($v)) {
                    $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
                }
            });

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
    //  AJAX — BÚSQUEDA PRESTADOR / OBRA SOCIAL  (para modal de ingreso)
    // ══════════════════════════════════════════════════════════════════════

    /** GET ?action=buscar_prestador&q=texto&ocultar_adef=1&ocultar_baja=1 */
    public function buscarPrestador(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $q           = trim($_GET['q'] ?? '');
        $ocultarAdef = ($_GET['ocultar_adef'] ?? '1') !== '0';
        $ocultarBaja = ($_GET['ocultar_baja'] ?? '1') !== '0';

        if ($q === '') { echo json_encode(['ok' => true, 'datos' => []]); exit; }

        try {
            $conds = ['(nombre LIKE :b1 OR codigo LIKE :b2 OR matricula LIKE :b3)'];
            $params = [
                ':b1' => '%' . $q . '%',
                ':b2' => '%' . $q . '%',
                ':b3' => '%' . $q . '%',
            ];

            // Ocultar ADEF salvo que se desactive el filtro
            if ($ocultarAdef) {
                $conds[] = "TRIM(categ) != 'ADEF'";
            }

            // Ocultar dados de baja hace más de 180 días
            if ($ocultarBaja) {
                $conds[] = "(fechabaja IS NULL"
                         . " OR fechabaja = '0000-00-00'"
                         . " OR fechabaja = '0000-00-00 00:00:00'"
                         . " OR fechabaja > DATE_SUB(CURDATE(), INTERVAL 180 DAY))";
            }

            $where = 'WHERE ' . implode(' AND ', $conds);

            $stmt = $this->db()->prepare(
                "SELECT TRIM(codigo)    AS codigo,
                        TRIM(nombre)    AS nombre,
                        TRIM(categ)     AS categ,
                        TRIM(empresa)   AS empresa,
                        TRIM(recomenda) AS recomenda,
                        TRIM(confact)   AS confact,
                        fechabaja
                 FROM ebamp
                 {$where}
                 ORDER BY nombre ASC LIMIT 20"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            array_walk_recursive($rows, function (&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
            });
            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** GET ?action=buscar_os&q=texto */
    public function buscarObraSocial(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $q = trim($_GET['q'] ?? '');
        if ($q === '') { echo json_encode(['ok' => true, 'datos' => []]); exit; }

        try {
            $val  = '%' . $q . '%';
            $stmt = $this->db()->prepare(
                "SELECT TRIM(TACODIGO) AS cosoc, TRIM(TADESCRIP) AS nombre
                 FROM obrasoc
                 WHERE (TAFECHAFIN IS NULL OR TAFECHAFIN='0000-00-00 00:00:00' OR TAFECHAFIN>CURDATE())
                   AND (TACODIGO LIKE :b1 OR TADESCRIP LIKE :b2)
                 ORDER BY TADESCRIP ASC LIMIT 15"
            );
            $stmt->execute([':b1' => $val, ':b2' => $val]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            array_walk_recursive($rows, function (&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
            });
            echo json_encode(['ok' => true, 'datos' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — GUARDAR NUEVA FACTURA
    // ══════════════════════════════════════════════════════════════════════

    /** POST ?action=guardar */
    public function guardar(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? ''))
            { $this->jsonError('Token de seguridad inválido.'); }

        $g = fn(string $k): string => trim($_POST[$k] ?? '');
        $n = fn(string $k): float  => (float)($_POST[$k] ?? 0);
        $i = fn(string $k): int    => (int)($_POST[$k] ?? 0);

        $coprestado = $g('COPRESTADO');
        $coobrasoc  = $g('COOBRASOC');
        $cosucfac   = str_pad($g('COSUCFAC'), 4, '0', STR_PAD_LEFT);
        $conrofac   = str_pad($g('CONROFAC'), 8, '0', STR_PAD_LEFT);

        if (!$coprestado)            { $this->jsonError('El código de Prestador es requerido.'); }
        if (!$coobrasoc)             { $this->jsonError('El código de Obra Social es requerido.'); }
        if ($cosucfac === '0000')    { $this->jsonError('Ingresá el punto de venta de la factura.'); }
        if ($conrofac === '00000000'){ $this->jsonError('Ingresá el número de factura.'); }

        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        try {
            $db = $this->db();

            // Completar CONOMPREST y COCATEG desde ebamp si no vienen del form
            $conomprest = $g('CONOMPREST');
            $cocateg    = $g('COCATEG');
            if ($conomprest === '' || $cocateg === '') {
                $ep = $db->prepare("SELECT TRIM(nombre) AS n, TRIM(categ) AS c FROM ebamp WHERE TRIM(codigo)=:c LIMIT 1");
                $ep->execute([':c' => $coprestado]);
                $row = $ep->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    if ($conomprest === '') $conomprest = $row['n'] ?? '';
                    if ($cocateg    === '') $cocateg    = $row['c'] ?? '';
                }
            }

            require_once __DIR__ . '/../models/RegistrfModel.php';
            (new RegistrfModel())->insertar([
                ':fecha'    => $g('COFECHA')   ?: date('Y-m-d'),
                ':periodo'  => $g('COPERIODO') ?: date('ym'),
                ':obrasoc'  => $coobrasoc,
                ':categ'    => mb_convert_encoding($cocateg,    'ISO-8859-1', 'UTF-8'),
                ':prestado' => $coprestado,
                ':nomprest' => mb_convert_encoding($conomprest, 'ISO-8859-1', 'UTF-8'),
                ':sucfac'   => $cosucfac,
                ':nrofac'   => $conrofac,
                ':fecfac'   => $g('COFECFAC')   ?: date('Y-m-d'),
                ':tipo'     => $g('COTIPO')       ?: 'F',
                ':cantidad' => $i('COCANTIDAD'),
                ':importe'  => $n('COIMPORTE'),
                ':iva'      => $n('COIVA'),
                ':coseguro' => $n('COCOSEGURO'),
                ':totalfac' => $n('COTOTALFAC'),
                ':monto'    => $n('COMONTO'),
                ':cantprest'=> $i('COCANTPREST'),
                ':tienefac' => $g('COTIENEFAC')  ?: 'S',
                ':usuario'  => $usr,
            ]);

            echo json_encode(['ok' => true, 'msg' => 'Factura registrada correctamente.'], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════

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

    private function db(): PDO
    {
        require_once __DIR__ . '/../config/database.php';
        return getDBConnection();
    }

    private function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
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
