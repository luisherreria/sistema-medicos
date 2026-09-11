<?php
/**
 * controllers/PrestadoresController.php
 * Módulo: Archivos → Prestadores + Obras Sociales Habilitadas
 *
 * Acciones disponibles:
 *   index()       → Vista principal del módulo (GET ?route=prestadores)
 *   listado()     → JSON: listado paginado de prestadores con filtros (GET ?action=listado)
 *   categorias()  → JSON: lista de categorías para el filtro (GET ?action=categorias)
 *   osListar()    → JSON: OS asignadas a un prestador (GET ?action=os_list)
 *   osObrasoc()   → JSON: catálogo OS vigentes (GET ?action=os_obrasoc)
 *   osAgregar()   → Agrega OS + log novedade (POST ?action=os_add)
 *   osBaja()      → Da de baja OS + log novedade (POST ?action=os_baja)
 *
 * Nomenclatura real de columnas MySQL:
 *   ebamp  : todas en MINÚSCULAS (ej: codigo, trabajaos, tienesuc, fechabaja …)
 *   obramed: MAYÚSCULAS (MEDICO, OBRASOC, FECHAALTA, FECHABAJA, EXCLUCART …)
 *   obrasoc: MAYÚSCULAS (TACODIGO, TADESCRIP, TAFECHAFIN …)
 */

class PrestadoresController
{
    // ── Acción principal del módulo (vista HTML) ──────────────────────────

    public function index()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');

        $user     = $_SESSION['user'];
        $permisos = $_SESSION['permisos'] ?? [];

        require_once __DIR__ . '/../views/prestadores/index.php';
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX — GRILLA PRINCIPAL
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?route=prestadores&action=listado
     * Parámetros opcionales:
     *   ocultar_inactivos = 1|0
     *   categ             = código de categoría o ''
     *   busqueda          = texto libre
     *   pagina            = número (1-based)
     *   por_pagina        = registros por página
     */
    public function listado()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        require_once __DIR__ . '/../models/Prestador.php';
        $model = new Prestador();

        try {
            $filtros = [
                'ocultar_inactivos' => ($_GET['ocultar_inactivos'] ?? '1') !== '0',
                'categ'             => trim($_GET['categ']    ?? ''),
                'busqueda'          => trim($_GET['busqueda'] ?? ''),
                'pagina'            => (int)($_GET['pagina']    ?? 1),
                'por_pagina'        => (int)($_GET['por_pagina'] ?? 100),
            ];

            $resultado = $model->listar($filtros);

            // Enriquecer con clase CSS de fila
            foreach ($resultado['datos'] as &$row) {
                $row['_row_class']   = Prestador::claseFilaTabla($row);
                $row['_badge_categ'] = Prestador::badgeCategoria($row['categ'] ?? '');
            }
            unset($row);

            echo json_encode(['ok' => true] + $resultado);
        } catch (PDOException $e) {
            $this->jsonError('Error al consultar prestadores: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * GET ?route=prestadores&action=categorias
     * Devuelve lista de categorías para el filtro.
     */
    public function categorias()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        require_once __DIR__ . '/../models/Prestador.php';
        $model = new Prestador();

        try {
            $cats = $model->categorias();
            echo json_encode(['ok' => true, 'data' => $cats]);
        } catch (PDOException $e) {
            $this->jsonError('Error al obtener categorías: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  OBRAS SOCIALES HABILITADAS — CRUD AJAX
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?route=prestadores&action=os_list&codigo=XXXX
     *
     * obramed.MEDICO  = MATRICULA del prestador (ebamp.MATRICULA)
     * obramed.OBRASOC = código de la OS (obrasoc.TACODIGO)
     */
    public function osListar()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo === '') {
            echo json_encode(['ok' => false, 'error' => 'Código de prestador requerido.']);
            exit;
        }

        try {
            $db        = $this->db();
            $matricula = $this->resolverMatricula($db, $codigo);

            // DISTINCT sobre OBRASOC para evitar duplicados de registros históricos
            $sql = "SELECT DISTINCT
                        om.OBRASOC   AS cosoc,
                        COALESCE(os.TADESCRIP, om.NOMOBRA, om.OBRASOC) AS nombre,
                        om.FECHAALTA AS fechaalta,
                        om.FECHABAJA AS fechabaja,
                        om.EXCLUCART AS exclucart,
                        om.EXCLUCALL AS exclucall,
                        om.EXCEP     AS excep,
                        om.EMPRESA   AS empresa
                    FROM obramed om
                    LEFT JOIN obrasoc os ON TRIM(os.TACODIGO) = TRIM(om.OBRASOC)
                    WHERE TRIM(om.MEDICO) = :matricula
                    ORDER BY om.OBRASOC ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([':matricula' => $matricula]);
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            $this->jsonError('Error al consultar obras sociales: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * GET ?route=prestadores&action=os_obrasoc
     * Catálogo de OS vigentes: TAFECHAFIN > CURDATE() o NULL.
     */
    public function osObrasoc()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');

        try {
            $db = $this->db();

            // OS vigentes (tafechafin en el futuro o sin vencimiento)
            $sql = "SELECT TACODIGO AS cosoc, TADESCRIP AS nombre
                    FROM obrasoc
                    WHERE (
                              TAFECHAFIN IS NULL
                           OR TAFECHAFIN = '0000-00-00 00:00:00'
                           OR TAFECHAFIN > CURDATE()
                          )";

            $params = [];

            if ($codigo !== '') {
                // Excluir las OS que el prestador ya tiene activas (fechabaja futura o nula)
                $matricula = $this->resolverMatricula($db, $codigo);
                $sql .= " AND TACODIGO NOT IN (
                              SELECT DISTINCT TRIM(OBRASOC)
                              FROM obramed
                              WHERE TRIM(MEDICO) = :matricula
                                AND (FECHABAJA IS NULL
                                     OR FECHABAJA = '0000-00-00 00:00:00'
                                     OR FECHABAJA > CURDATE())
                          )";
                $params[':matricula'] = $matricula;
            }

            $sql .= " ORDER BY TADESCRIP ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
        } catch (PDOException $e) {
            $this->jsonError('Error al obtener catálogo de obras sociales: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=prestadores&action=os_add
     * Body: codigo (prestador), cosoc, [exclucart], [exclucall]
     */
    public function osAgregar()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $csrfPost = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfPost)) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $codigo    = trim($_POST['codigo']    ?? '');
        $cosoc     = trim($_POST['cosoc']     ?? '');
        $exclucart = isset($_POST['exclucart']) ? '1' : '0';
        $exclucall = isset($_POST['exclucall']) ? '1' : '0';

        if ($codigo === '') { $this->jsonError('Código de prestador requerido.'); }
        if ($cosoc  === '') { $this->jsonError('Debe seleccionar una Obra Social.'); }

        $hoy          = date('Y-m-d H:i:s');
        $fechaBajaDef = date('Y-m-d 00:00:00',
            mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y') + 100));

        try {
            $db        = $this->db();
            $matricula = $this->resolverMatricula($db, $codigo);

            // Verificar que la OS no esté ya activa
            $stmtCheck = $db->prepare(
                "SELECT COUNT(*) FROM obramed
                 WHERE TRIM(MEDICO) = :mat AND TRIM(OBRASOC) = :os
                   AND (FECHABAJA >= CURDATE() OR FECHABAJA IS NULL)"
            );
            $stmtCheck->execute([':mat' => $matricula, ':os' => $cosoc]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $this->jsonError('La Obra Social ya está activa para este prestador.');
            }

            $db->beginTransaction();

            // ¿Existe registro (posiblemente dado de baja)?
            $stmtEx = $db->prepare(
                "SELECT COUNT(*) FROM obramed WHERE TRIM(MEDICO) = :mat AND TRIM(OBRASOC) = :os"
            );
            $stmtEx->execute([':mat' => $matricula, ':os' => $cosoc]);
            $existe = (int)$stmtEx->fetchColumn() > 0;

            // Nombre de la OS
            $stmtNom = $db->prepare("SELECT TADESCRIP FROM obrasoc WHERE TRIM(TACODIGO) = :os LIMIT 1");
            $stmtNom->execute([':os' => $cosoc]);
            $nomOS = (string)($stmtNom->fetchColumn() ?: $cosoc);

            if ($existe) {
                $sql = "UPDATE obramed
                        SET FECHAALTA = :fa, FECHABAJA = :fb,
                            EXCLUCART = :ec, EXCLUCALL = :el,
                            EMPRESA   = 'COME', NOMOBRA = :nom,
                            FECHAUP   = :fu, USER = :usr
                        WHERE TRIM(MEDICO) = :mat AND TRIM(OBRASOC) = :os";
            } else {
                $sql = "INSERT INTO obramed
                            (OBRASOC, MEDICO, NOMOBRA, FECHAALTA, FECHABAJA,
                             EXCLUCART, EXCLUCALL, EMPRESA, EXCEP, FECHAUP, USER)
                        VALUES
                            (:os, :mat, :nom, :fa, :fb, :ec, :el, 'COME', 'N', :fu, :usr)";
            }

            $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));
            $db->prepare($sql)->execute([
                ':mat' => $matricula, ':os'  => $cosoc, ':nom' => $nomOS,
                ':fa'  => $hoy,       ':fu'  => $hoy,   ':fb'  => $fechaBajaDef,
                ':ec'  => $exclucart,                    ':el'  => $exclucall,
                ':usr' => $usr,
            ]);

            $this->registrarNovedad($db, $codigo, $cosoc, 'ALTA');
            $this->actualizarTrabajosOS($db, $codigo, $matricula);

            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Obra Social asignada correctamente.']);
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $this->jsonError('Error al asignar obra social: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=prestadores&action=os_edit
     * Body: codigo, cosoc, fechaalta, fechabaja, exclucart
     */
    public function osEditar()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $csrfPost = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfPost)) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $codigo    = trim($_POST['codigo']    ?? '');
        $cosoc     = trim($_POST['cosoc']     ?? '');
        $fechaalta = trim($_POST['fechaalta'] ?? '');
        $fechabaja = trim($_POST['fechabaja'] ?? '');
        $exclucart = trim($_POST['exclucart'] ?? '0');

        if ($codigo    === '') { $this->jsonError('Código de prestador requerido.'); }
        if ($cosoc     === '') { $this->jsonError('Código de obra social requerido.'); }
        if ($fechaalta === '') { $this->jsonError('La Fecha de Alta es obligatoria.'); }

        $fa  = $fechaalta . ' 00:00:00';
        $fb  = $fechabaja !== '' ? ($fechabaja . ' 00:00:00') : '2099-12-31 00:00:00';
        $now = date('Y-m-d H:i:s');
        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        try {
            $db        = $this->db();
            $matricula = $this->resolverMatricula($db, $codigo);

            $stmt = $db->prepare(
                "UPDATE obramed
                 SET    FECHAALTA = :fa,
                        FECHABAJA = :fb,
                        EXCLUCART = :ec,
                        FECHAUP   = :fu,
                        USER      = :usr
                 WHERE  TRIM(MEDICO)  = :mat
                   AND  TRIM(OBRASOC) = :os"
            );
            $stmt->execute([
                ':fa'  => $fa,   ':fb'  => $fb,   ':ec'  => $exclucart,
                ':fu'  => $now,  ':usr' => $usr,
                ':mat' => $matricula, ':os' => $cosoc,
            ]);

            if ($stmt->rowCount() === 0) {
                $this->jsonError('No se encontró el registro para actualizar.');
            }

            $this->actualizarTrabajosOS($db, $codigo, $matricula);

            echo json_encode(['ok' => true, 'msg' => 'Cambios guardados correctamente.']);
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar los cambios: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=prestadores&action=os_baja
     * Body: codigo (prestador), cosoc
     */
    public function osBaja()
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $csrfPost = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfPost)) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $cosoc  = trim($_POST['cosoc']  ?? '');

        if ($codigo === '') { $this->jsonError('Código de prestador requerido.'); }
        if ($cosoc  === '') { $this->jsonError('Código de obra social requerido.'); }

        $hoy = date('Y-m-d H:i:s');
        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        try {
            $db        = $this->db();
            $matricula = $this->resolverMatricula($db, $codigo);
            $db->beginTransaction();

            $stmt = $db->prepare(
                "UPDATE obramed
                 SET FECHABAJA = :fb, FECHAUP = :fa, USER = :usr
                 WHERE TRIM(MEDICO) = :mat AND TRIM(OBRASOC) = :os"
            );
            $stmt->execute([':fb' => $hoy, ':fa' => $hoy, ':usr' => $usr,
                            ':mat' => $matricula, ':os' => $cosoc]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                $this->jsonError('No se encontró el registro a dar de baja.');
            }

            $this->registrarNovedad($db, $codigo, $cosoc, 'BAJA');
            $this->actualizarTrabajosOS($db, $codigo, $matricula);

            $db->commit();
            echo json_encode(['ok' => true, 'msg' => 'Obra Social dada de baja correctamente.']);
        } catch (PDOException $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $this->jsonError('Error al dar de baja: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    // EDITAR PRESTADOR (ebamp)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?route=prestadores&action=prestador_get&codigo=XXX
     * Devuelve todos los campos de ebamp para el formulario de edición.
     */
    public function prestadorGet(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo === '') { $this->jsonError('Código requerido.'); }

        try {
            $db   = $this->db();
            // Selección explícita de las columnas que sabemos existen en ebamp
            // (evita notices/errors por columnas inexistentes en SELECT *)
            $stmt = $db->prepare(
                "SELECT
                    TRIM(CODIGO)    AS CODIGO,    TRIM(MATRICULA)  AS MATRICULA,
                    TRIM(MATRIPROV) AS MATRIPROV,  TRIM(MATRIMED)   AS MATRIMED,
                    TRIM(NOMBRE)    AS NOMBRE,     TRIM(NOMFANTAS)  AS NOMFANTAS,
                    TRIM(CUIT)      AS CUIT,       LEGAJO,
                    TRIM(CATEG)     AS CATEG,      TRIM(SUBCATEG)   AS SUBCATEG,
                    TRIM(CONVENIO)  AS CONVENIO,   TRIM(GRUPOWEB)   AS GRUPOWEB,
                    TRIM(DIRECC)    AS DIRECC,     TRIM(LOCALIDAD)  AS LOCALIDAD,
                    TRIM(ZONA)      AS ZONA,
                    TRIM(TELCONS)   AS TELCONS,    TRIM(CELULAR)    AS CELULAR,
                    TRIM(FAX)       AS FAX,        TRIM(CONTACTO)   AS CONTACTO,
                    TRIM(EMPRESA)   AS EMPRESA,    TRIM(BANCO)      AS BANCO,
                    TRIM(SUCURSAL)  AS SUCURSAL,   TRIM(CBU)        AS CBU,
                    TRIM(MAIL)      AS MAIL,       TRIM(MAIL_VENC)  AS MAIL_VENC,
                    TRIM(MAIL_DEB)  AS MAIL_DEB,   TRIM(MAIL_PAGO)  AS MAIL_PAGO,
                    TRIM(MAIL_AUTO) AS MAIL_AUTO,  TRIM(MAILCONTRA) AS MAILCONTRA,
                    FECHAALTA, FECHABAJA, FBAJA,
                    TRIM(SSS)       AS SSS,        SSSVENC,
                    TRIM(SEGURO)    AS SEGURO,     SEGUROVENC,
                    TRIM(ISSUSPEND) AS ISSUSPEND,  TRIM(EXCLUCART)  AS EXCLUCART,
                    TRIM(EXCLUCALL) AS EXCLUCALL,  TRIM(ISONCOLOGO) AS ISONCOLOGO,
                    TRIM(CONFLICTO) AS CONFLICTO,  TRIM(MOTIVOBJ)   AS MOTIVOBJ,
                    TRIM(HORARIO1)  AS HORARIO1,   TRIM(BAJA)       AS BAJA,
                    IDWEB,  IDRED,  TRIM(SAB) AS SAB,
                    TRIM(TRABAJAOS) AS TRABAJAOS,  TRIM(TIENEPREST) AS TIENEPREST,
                    TRIM(TIENESUC)  AS TIENESUC,   TRIM(TIENESUCPR) AS TIENESUCPR
                 FROM ebamp WHERE TRIM(CODIGO) = :cod LIMIT 1"
            );
                $stmt->execute([':cod' => $codigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) { $this->jsonError('Prestador no encontrado (código: ' . $codigo . ').'); }

            // Normalizar fechas a ISO YYYY-MM-DD
            $fechaCols = ['FECHAALTA','FECHABAJA','FBAJA','SSSVENC','SEGUROVENC','CONTRAFEC'];
            foreach ($fechaCols as $f) {
                $v = $row[$f] ?? '';
                $row[$f] = ($v && $v !== '0000-00-00' && $v !== '0000-00-00 00:00:00')
                    ? substr($v, 0, 10) : '';
            }

            // Forzar encoding UTF-8 seguro para json_encode
            array_walk_recursive($row, function (&$val) {
                if (is_string($val)) {
                    $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8, ISO-8859-1');
                }
            });

            $json = json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                // Último recurso: reemplazar bytes inválidos
                array_walk_recursive($row, function (&$val) {
                    if (is_string($val)) {
                        $val = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $val);
                        $val = iconv('UTF-8', 'UTF-8//IGNORE', $val);
                    }
                });
                $json = json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }
            echo $json;

        } catch (PDOException $e) {
            $this->jsonError('Error al obtener prestador: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=prestadores&action=prestador_guardar
     * Guarda los cambios de un prestador existente (UPDATE ebamp).
     */
    public function prestadorGuardar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $codigo = trim($_POST['codigo'] ?? '');
        if ($codigo === '') { $this->jsonError('Código de prestador requerido.'); }

        $now = date('Y-m-d H:i:s');
        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        // Helper: convierte fecha vacía a NULL
        $fecha = function (string $val): ?string {
            $v = trim($val);
            return ($v === '' || $v === '0000-00-00') ? null : $v . ' 00:00:00';
        };

        // Helper: checkbox → '1'|'0'
        $chk = function (string $key): string {
            return isset($_POST[$key]) && $_POST[$key] ? '1' : '0';
        };

        $p = [
            ':cod'       => $codigo,
            ':cuit'      => trim($_POST['cuit']        ?? ''),
            ':legajo'    => (int)($_POST['legajo']      ?? 0) ?: null,
            ':categ'     => strtoupper(trim($_POST['categ']       ?? '')),
            ':subcateg'  => trim($_POST['subcateg']    ?? ''),
            ':convenio'  => trim($_POST['convenio']    ?? ''),
            ':matriprov' => trim($_POST['matriprov']   ?? ''),
            ':matrimed'  => trim($_POST['matrimed']    ?? ''),
            ':nombre'    => strtoupper(trim($_POST['nombre']      ?? '')),
            ':nomfantas' => trim($_POST['nomfantas']   ?? ''),
            ':direcc'    => trim($_POST['direcc']      ?? ''),
            ':localidad' => trim($_POST['localidad']   ?? ''),
            ':zona'      => trim($_POST['zona']        ?? ''),
            ':telcons'   => trim($_POST['telcons']     ?? ''),
            ':celular'   => trim($_POST['celular']     ?? ''),
            ':fax'       => trim($_POST['fax']         ?? ''),
            ':contacto'  => trim($_POST['contacto']    ?? ''),
            ':empresa'   => strtoupper(trim($_POST['empresa']     ?? '')),
            ':banco'     => trim($_POST['banco']       ?? ''),
            ':sucursal'  => trim($_POST['sucursal']    ?? ''),
            ':cbu'       => trim($_POST['cbu']         ?? ''),
            ':grupoweb'  => strtoupper(trim($_POST['grupoweb']    ?? '')),
            ':horario1'  => trim($_POST['horario1']    ?? ''),
            ':mail'      => trim($_POST['mail']        ?? ''),
            ':mailvenc'  => trim($_POST['mail_venc']   ?? ''),
            ':maildeb'   => trim($_POST['mail_deb']    ?? ''),
            ':mailpago'  => trim($_POST['mail_pago']   ?? ''),
            ':mailauto'  => trim($_POST['mail_auto']   ?? ''),
            ':mailcontra'=> trim($_POST['mailcontra']  ?? ''),
            ':sss'       => $chk('sss'),
            ':sssvenc'   => $fecha($_POST['sssvenc']   ?? ''),
            ':seguro'    => $chk('seguro'),
            ':segurovenc'=> $fecha($_POST['segurovenc']?? ''),
            ':issuspend' => $chk('issuspend'),
            ':exclucart' => $chk('exclucart'),
            ':exclucall' => $chk('exclucall'),
            ':isoncologo'=> $chk('isoncologo'),
            ':conflicto' => $chk('conflictivo'),
            ':motivobj'  => trim($_POST['motivobj']    ?? ''),
            ':fechaalta' => $fecha($_POST['fechaalta'] ?? ''),
            ':fechabaja' => $fecha($_POST['fechabaja'] ?? ''),
            ':fuser'     => substr($usr, 0, 5),
        ];

        $accion = trim($_POST['accion'] ?? 'modificar');

        // Para nuevo: pre-calcular matricula, legajo, idweb
        $newMatricula = '';
        $newIdweb     = 0;
        if ($accion === 'nuevo') {
            try {
                $db = $this->db();
                $r = $db->query("SELECT MAX(TRIM(matricula)) AS mx FROM ebamp WHERE TRIM(matricula) LIKE 'A0%'")->fetch(PDO::FETCH_ASSOC);
                $n = intval(substr(trim($r['mx'] ?? 'A0000000'), 1)) + 1;
                $newMatricula = 'A' . str_pad($n, 7, '0', STR_PAD_LEFT);
                // Si el usuario especificó un codigo manual, lo respetamos
                if ($codigo === '') {
                    $codigo = $newMatricula;
                    $p[':cod'] = $codigo;
                }
                $rw = $db->query("SELECT MAX(IDWEB) AS mx FROM ebamp")->fetch(PDO::FETCH_ASSOC);
                $newIdweb = (int)($rw['mx'] ?? 0) + 1;
                $rl = $db->query("SELECT MAX(LEGAJO) AS mx FROM ebamp")->fetch(PDO::FETCH_ASSOC);
                if (!$p[':legajo']) { $p[':legajo'] = (int)($rl['mx'] ?? 0) + 1; }
            } catch (PDOException $e) {
                $this->jsonError('Error al calcular autonumérico: ' . $e->getMessage());
            }
        }
        $p[':idweb'] = $newIdweb ?: (int)($_POST['idweb'] ?? 0);

        try {
            $db      = $db ?? $this->db();
            $allParams = $p;

            if ($accion === 'nuevo') {
                // ── INSERT ────────────────────────────────────────────────
                if ($codigo === '') {
                    $stmtMax = $db->query("SELECT MAX(CAST(codigo AS UNSIGNED)) + 1 FROM ebamp");
                    $codigo  = (string)($stmtMax->fetchColumn() ?: '1');
                    $allParams[':cod'] = $codigo;
                }
                $stmtChk = $db->prepare("SELECT COUNT(*) FROM ebamp WHERE TRIM(codigo)=:c");
                $stmtChk->execute([':c' => $codigo]);
                if ((int)$stmtChk->fetchColumn() > 0) {
                    $this->jsonError("El código «{$codigo}» ya existe.");
                }

                $sql = "INSERT INTO ebamp
                            (CODIGO, MATRICULA, CUIT, LEGAJO,
                             CATEG, SUBCATEG, CONVENIO, MATRIPROV, MATRIMED,
                             NOMBRE, NOMFANTAS, DIRECC, LOCALIDAD, ZONA,
                             TELCONS, CELULAR, FAX, CONTACTO,
                             EMPRESA, BANCO, CBU, GRUPOWEB, HORARIO1,
                             MAIL, MAIL_VENC, MAIL_DEB, MAIL_PAGO, MAIL_AUTO, MAILCONTRA,
                             SSS, SSSVENC, SEGURO, SEGUROVENC,
                             ISSUSPEND, EXCLUCART, EXCLUCALL, ISONCOLOGO, CONFLICTO,
                             MOTIVOBJ, FECHAALTA, FECHABAJA,
                             SAB, NOMBREEMP, TIENESUC, TIENESUCPR,
                             SUCEXCLU, PRACEXCLU, TRABAJAOS, TIENEPREST,
                             IDWEB, IDRED, BAJA, CARGAUSR)
                        VALUES
                            (:cod, :mat_new, :cuit, :legajo,
                             :categ, :subcateg, :convenio, :matriprov, :matrimed,
                             :nombre, :nomfantas, :direcc, :localidad, :zona,
                             :telcons, :celular, :fax, :contacto,
                             :empresa, :banco, :cbu, :grupoweb, :horario1,
                             :mail, :mailvenc, :maildeb, :mailpago, :mailauto, :mailcontra,
                             :sss, :sssvenc, :seguro, :segurovenc,
                             :issuspend, :exclucart, :exclucall, :isoncologo, :conflicto,
                             :motivobj, :fechaalta, :fechabaja,
                             'ARREGLADO', 'Comedica', '0', '0',
                             '0', '0', '0', '0',
                             :idweb, :idred_new, '0', :fuser)";
                $insertParams = $allParams;
                $insertParams[':mat_new']  = $codigo;   // MATRICULA = mismo que CODIGO en alta
                $insertParams[':idred_new']= $allParams[':idweb'];  // IDRED = mismo que IDWEB
                $db->prepare($sql)->execute($insertParams);

                echo json_encode(['ok' => true, 'msg' => 'Prestador creado correctamente.', 'codigo' => $codigo]);

            } else {
                // ── UPDATE ────────────────────────────────────────────────
                $sql = "UPDATE ebamp SET
                            CUIT       = :cuit,      LEGAJO     = :legajo,
                            CATEG      = :categ,     SUBCATEG   = :subcateg,
                            CONVENIO   = :convenio,  MATRIPROV  = :matriprov,
                            MATRIMED   = :matrimed,  NOMBRE     = :nombre,
                            NOMFANTAS  = :nomfantas, DIRECC     = :direcc,
                            LOCALIDAD  = :localidad, ZONA       = :zona,
                            TELCONS    = :telcons,   CELULAR    = :celular,
                            FAX        = :fax,       CONTACTO   = :contacto,
                            EMPRESA    = :empresa,   BANCO      = :banco,
                            SUCURSAL   = :sucursal,  CBU        = :cbu,
                            GRUPOWEB   = :grupoweb,  HORARIO1   = :horario1,
                            MAIL       = :mail,      MAIL_VENC  = :mailvenc,
                            MAIL_DEB   = :maildeb,   MAIL_PAGO  = :mailpago,
                            MAIL_AUTO  = :mailauto,  MAILCONTRA = :mailcontra,
                            SSS        = :sss,       SSSVENC    = :sssvenc,
                            SEGURO     = :seguro,    SEGUROVENC = :segurovenc,
                            ISSUSPEND  = :issuspend, EXCLUCART  = :exclucart,
                            EXCLUCALL  = :exclucall, ISONCOLOGO = :isoncologo,
                            CONFLICTO  = :conflicto, MOTIVOBJ   = :motivobj,
                            FECHAALTA  = :fechaalta, FECHABAJA  = :fechabaja,
                            FMODIF     = NOW(),      FUSER      = :fuser
                        WHERE TRIM(CODIGO) = :cod";
                // Para UPDATE: quitar :idweb que no está en el SQL
                $updateParams = $allParams;
                unset($updateParams[':idweb']);
                $db->prepare($sql)->execute($updateParams);
                echo json_encode(['ok' => true, 'msg' => 'Prestador actualizado correctamente.']);
            }

            // ── Sincronizar sucprest ──────────────────────────────────────
            $this->sincronizarSucursal($db, $allParams, $accion);

        } catch (PDOException $e) {
            $this->jsonError('Error al guardar prestador: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * Crea sucursal en sucprest si el prestador no tiene ninguna.
     * Para prestador nuevo: siempre inserta.
     * Para modificar: sólo si aún no tiene ninguna.
     */
    // ══════════════════════════════════════════════════════════════════════
    // SUCURSALES (sucprest)
    // ══════════════════════════════════════════════════════════════════════

    /** GET ?action=suc_listar&codigo=XX&busqueda=&pagina=1&por_pagina=25 */
    public function sucListar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo   = trim($_GET['codigo']   ?? '');
        $busqueda = trim($_GET['busqueda'] ?? '');
        $pagina   = max(1, (int)($_GET['pagina']    ?? 1));
        $porPag   = max(1, min(200, (int)($_GET['por_pagina'] ?? 25)));

        if ($codigo === '') { $this->jsonError('Código requerido.'); }

        try {
            $db     = $this->db();
            $conds  = ['TRIM(suprestado) = :cod'];
            $params = [':cod' => $codigo];

            if ($busqueda !== '') {
                $b = '%' . $busqueda . '%';
                $conds[] = "(sucodigo LIKE :b1 OR sunombre LIKE :b2 OR sudir LIKE :b3
                             OR sulocalida LIKE :b4 OR suzona LIKE :b5 OR sutelef LIKE :b6
                             OR suemail LIKE :b7 OR sucontacto LIKE :b8)";
                $params += [':b1'=>$b,':b2'=>$b,':b3'=>$b,':b4'=>$b,
                            ':b5'=>$b,':b6'=>$b,':b7'=>$b,':b8'=>$b];
            }
            $where = ' WHERE ' . implode(' AND ', $conds);

            $total   = (int)$db->prepare("SELECT COUNT(*) FROM sucprest{$where}")
                                ->execute($params) ? $db->prepare("SELECT COUNT(*) FROM sucprest{$where}")->execute($params) : 0;
            $stmtCnt = $db->prepare("SELECT COUNT(*) FROM sucprest{$where}");
            $stmtCnt->execute($params);
            $total   = (int)$stmtCnt->fetchColumn();
            $paginas = max(1, (int)ceil($total / $porPag));
            $offset  = ($pagina - 1) * $porPag;

            $stmtD = $db->prepare(
                "SELECT TRIM(sucodigo) AS sucodigo, TRIM(sunombre) AS sunombre,
                        TRIM(sudir)    AS sudir,    TRIM(sulocalida) AS sulocalida,
                        TRIM(suzona)   AS suzona,   TRIM(sutelef)    AS sutelef,
                        TRIM(sucontacto) AS sucontacto, TRIM(suemail) AS suemail,
                        TRIM(suhorario)  AS suhorario,  TRIM(sudirmap)  AS sudirmap,
                        sudesde, TRIM(user) AS user
                 FROM sucprest{$where}
                 ORDER BY sucodigo ASC
                 LIMIT :lim OFFSET :off"
            );
            foreach ($params as $k => $v) $stmtD->bindValue($k, $v);
            $stmtD->bindValue(':lim', $porPag, PDO::PARAM_INT);
            $stmtD->bindValue(':off', $offset,  PDO::PARAM_INT);
            $stmtD->execute();

            $rows = array_map(function ($r) {
                return array_map(function ($v) {
                    return is_string($v) ? mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1') : $v;
                }, $r);
            }, $stmtD->fetchAll(PDO::FETCH_ASSOC));

            echo json_encode(['ok'=>true,'total'=>$total,'paginas'=>$paginas,
                              'pagina'=>$pagina,'datos'=>$rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError('Error al listar sucursales: ' . $e->getMessage());
        }
        exit;
    }

    /** GET ?action=suc_defaults&codigo=XX  — genera el próximo sucodigo disponible (A-Z) */
    public function sucDefaults(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo === '') { $this->jsonError('Código requerido.'); }

        try {
            $db = $this->db();
            // VFP: si len=8 usar primeros 7 chars, sino usar tal cual
            $ttpresta = strlen($codigo) === 8 ? substr($codigo, 0, 7) : $codigo;
            $newSuc   = '';
            foreach (range('A', 'Z') as $letter) {
                $candidate = $ttpresta . $letter;
                $chk = $db->prepare("SELECT COUNT(*) FROM sucprest WHERE TRIM(sucodigo)=:sc");
                $chk->execute([':sc' => $candidate]);
                if ((int)$chk->fetchColumn() === 0) { $newSuc = $candidate; break; }
            }
            if ($newSuc === '') { $this->jsonError('Se agotaron las letras A-Z para nuevas sucursales.'); }
            echo json_encode(['ok'=>true,'sucodigo'=>$newSuc]);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** POST ?action=suc_guardar */
    public function sucGuardar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? ''))
            { $this->jsonError('CSRF inválido.'); }

        $accion     = trim($_POST['accion']    ?? 'nuevo');
        $suprestado = trim($_POST['suprestado']?? '');
        $sucodigo   = trim($_POST['sucodigo']  ?? '');
        $usr        = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        if ($suprestado === '') { $this->jsonError('Código de prestador requerido.'); }
        if ($sucodigo   === '') { $this->jsonError('Código de sucursal requerido.'); }

        $p = [
            ':suprestado' => $suprestado,
            ':sucodigo'   => $sucodigo,
            ':sunombre'   => trim($_POST['sunombre']   ?? ''),
            ':sudir'      => trim($_POST['sudir']      ?? ''),
            ':sulocalida' => trim($_POST['sulocalida'] ?? ''),
            ':suzona'     => trim($_POST['suzona']     ?? ''),
            ':sutelef'    => trim($_POST['sutelef']    ?? ''),
            ':sucontacto' => trim($_POST['sucontacto'] ?? ''),
            ':suemail'    => trim($_POST['suemail']    ?? ''),
            ':suhorario'  => trim($_POST['suhorario']  ?? ''),
            ':sudirmap'   => trim($_POST['sudirmap']   ?? ''),
            ':user'       => $usr,
        ];

        try {
            $db = $this->db();
            if ($accion === 'nuevo') {
                $db->prepare(
                    "INSERT INTO sucprest
                        (suprestado, sucodigo, sunombre, sudir, sulocalida, suzona,
                         sutelef, sucontacto, suemail, suhorario, sudirmap, sudesde, user, fechaup)
                     VALUES
                        (:suprestado, :sucodigo, :sunombre, :sudir, :sulocalida, :suzona,
                         :sutelef, :sucontacto, :suemail, :suhorario, :sudirmap, CURDATE(), :user, CURDATE())"
                )->execute($p);
                // Marcar que tiene sucursal
                $db->prepare("UPDATE ebamp SET tienesuc='1' WHERE TRIM(CODIGO)=:c")
                   ->execute([':c' => $suprestado]);
                echo json_encode(['ok'=>true,'msg'=>'Sucursal creada correctamente.','sucodigo'=>$sucodigo]);
            } else {
                $db->prepare(
                    "UPDATE sucprest SET
                        sunombre=:sunombre, sudir=:sudir, sulocalida=:sulocalida, suzona=:suzona,
                        sutelef=:sutelef, sucontacto=:sucontacto, suemail=:suemail,
                        suhorario=:suhorario, sudirmap=:sudirmap, user=:user, fechaup=CURDATE()
                     WHERE TRIM(suprestado)=:suprestado AND TRIM(sucodigo)=:sucodigo"
                )->execute($p);
                echo json_encode(['ok'=>true,'msg'=>'Sucursal actualizada correctamente.']);
            }
        } catch (PDOException $e) {
            $this->jsonError('Error al guardar sucursal: ' . $e->getMessage());
        }
        exit;
    }

    /** POST ?action=suc_borrar */
    public function sucBorrar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? ''))
            { $this->jsonError('CSRF inválido.'); }

        $suprestado = trim($_POST['suprestado'] ?? '');
        $sucodigo   = trim($_POST['sucodigo']   ?? '');
        if (!$suprestado || !$sucodigo) { $this->jsonError('Parámetros incompletos.'); }

        try {
            $db = $this->db();
            $db->prepare(
                "DELETE FROM sucprest WHERE TRIM(suprestado)=:sp AND TRIM(sucodigo)=:sc"
            )->execute([':sp'=>$suprestado, ':sc'=>$sucodigo]);

            // Si no quedan sucursales, marcar tienesuc='0'
            $cnt = $db->prepare("SELECT COUNT(*) FROM sucprest WHERE TRIM(suprestado)=:sp");
            $cnt->execute([':sp' => $suprestado]);
            if ((int)$cnt->fetchColumn() === 0) {
                $db->prepare("UPDATE ebamp SET tienesuc='0' WHERE TRIM(CODIGO)=:c")
                   ->execute([':c' => $suprestado]);
            }
            echo json_encode(['ok'=>true,'msg'=>'Sucursal eliminada.']);
        } catch (PDOException $e) {
            $this->jsonError('Error al borrar: ' . $e->getMessage());
        }
        exit;
    }

    /** GET ?action=suc_exclu_listar&codigo=XX&sucodigo=YY
     *  Lista las OS activas del prestador con flag de si está excluida en esa sucursal */
    public function sucExcluListar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo   = trim($_GET['codigo']   ?? '');
        $sucodigo = trim($_GET['sucodigo'] ?? '');
        if (!$codigo || !$sucodigo) { $this->jsonError('Parámetros requeridos.'); }

        try {
            $db        = $this->db();
            $matricula = $this->resolverMatricula($db, $codigo);

            $stmt = $db->prepare(
                "SELECT DISTINCT
                     TRIM(om.OBRASOC) AS cosoc,
                     COALESCE(os.TADESCRIP, om.NOMOBRA, om.OBRASOC) AS nombre,
                     CASE WHEN ex.ACTIVO = 1 THEN 1 ELSE 0 END AS excluida
                 FROM obramed om
                 LEFT JOIN obrasoc  os ON TRIM(os.TACODIGO)  = TRIM(om.OBRASOC)
                 LEFT JOIN excluos  ex ON TRIM(ex.OBRASOC)   = TRIM(om.OBRASOC)
                                      AND TRIM(ex.CODIGO)    = :cod
                                      AND TRIM(ex.SUCURSAL)  = :suc
                 WHERE TRIM(om.MEDICO) = :mat
                   AND (om.FECHABAJA IS NULL OR om.FECHABAJA = '0000-00-00 00:00:00'
                        OR om.FECHABAJA > CURDATE())
                 ORDER BY nombre ASC"
            );
            $stmt->execute([':mat'=>$matricula, ':cod'=>$codigo, ':suc'=>$sucodigo]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            array_walk_recursive($rows, function (&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
            });
            echo json_encode(['ok'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** POST ?action=suc_exclu_toggle */
    public function sucExcluToggle(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? ''))
            { $this->jsonError('CSRF inválido.'); }

        $codigo   = trim($_POST['codigo']   ?? '');
        $sucodigo = trim($_POST['sucodigo'] ?? '');
        $cosoc    = trim($_POST['cosoc']    ?? '');
        $nomOS    = trim($_POST['nombre']   ?? $cosoc);
        if (!$codigo || !$sucodigo || !$cosoc) { $this->jsonError('Parámetros requeridos.'); }

        try {
            $db = $this->db();
            $chk = $db->prepare(
                "SELECT id, ACTIVO FROM excluos
                 WHERE TRIM(OBRASOC)=:os AND TRIM(CODIGO)=:cod AND TRIM(SUCURSAL)=:suc LIMIT 1"
            );
            $chk->execute([':os'=>$cosoc, ':cod'=>$codigo, ':suc'=>$sucodigo]);
            $row = $chk->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $newActivo = $row['ACTIVO'] == 1 ? 0 : 1;
                $db->prepare("UPDATE excluos SET ACTIVO=:a WHERE id=:id")
                   ->execute([':a'=>$newActivo, ':id'=>$row['id']]);
                $excluida = (bool)$newActivo;
            } else {
                // Obtener datos de la sucursal para SUDIR
                $stmtSuc = $db->prepare("SELECT sudir FROM sucprest WHERE TRIM(sucodigo)=:sc LIMIT 1");
                $stmtSuc->execute([':sc' => $sucodigo]);
                $sudir = (string)($stmtSuc->fetchColumn() ?: '');
                $db->prepare(
                    "INSERT INTO excluos (OBRASOC, CODIGO, SUCURSAL, ACTIVO, NOMBRE, SUDIR)
                     VALUES (:os, :cod, :suc, 1, :nom, :sdir)"
                )->execute([':os'=>$cosoc, ':cod'=>$codigo, ':suc'=>$sucodigo,
                            ':nom'=>$nomOS, ':sdir'=>$sudir]);
                $excluida = true;
            }
            echo json_encode(['ok'=>true,'excluida'=>$excluida]);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** GET ?action=suc_prac_listar&codigo=XX&sucodigo=YY&busqueda=&pagina=1 */
    public function sucPracListar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo   = trim($_GET['codigo']   ?? '');
        $sucodigo = trim($_GET['sucodigo'] ?? '');
        $busqueda = trim($_GET['busqueda'] ?? '');
        $pagina   = max(1, (int)($_GET['pagina']    ?? 1));
        $porPag   = max(1, min(200, (int)($_GET['por_pagina'] ?? 25)));

        if (!$codigo || !$sucodigo) { $this->jsonError('Parámetros requeridos.'); }

        try {
            $db     = $this->db();
            $conds  = ['TRIM(PEPRESTADO)=:cod', 'TRIM(PECODIGO)=:suc'];
            $params = [':cod'=>$codigo, ':suc'=>$sucodigo];

            if ($busqueda !== '') {
                $b = '%' . $busqueda . '%';
                $conds[] = "(PEPRACTICA LIKE :b1 OR PENOMBPRAC LIKE :b2 OR PEGRUPO LIKE :b3)";
                $params += [':b1'=>$b, ':b2'=>$b, ':b3'=>$b];
            }
            $where = ' WHERE ' . implode(' AND ', $conds);

            $stmtCnt = $db->prepare("SELECT COUNT(*) FROM praespsu{$where}");
            $stmtCnt->execute($params);
            $total   = (int)$stmtCnt->fetchColumn();
            $paginas = max(1, (int)ceil($total / $porPag));
            $offset  = ($pagina - 1) * $porPag;

            $stmtD = $db->prepare(
                "SELECT TRIM(PEPRACTICA)  AS practica,
                        TRIM(PENOMBPRAC)  AS nombre,
                        TRIM(PEGRUPO)     AS grupo,
                        TRIM(PETIPO)      AS tipo,
                        PEIMPORTE         AS importe,
                        PEPORCE           AS porce,
                        PEFECHA           AS fecha
                 FROM praespsu{$where}
                 ORDER BY PEPRACTICA ASC
                 LIMIT :lim OFFSET :off"
            );
            foreach ($params as $k => $v) $stmtD->bindValue($k, $v);
            $stmtD->bindValue(':lim', $porPag, PDO::PARAM_INT);
            $stmtD->bindValue(':off', $offset,  PDO::PARAM_INT);
            $stmtD->execute();
            $rows = $stmtD->fetchAll(PDO::FETCH_ASSOC);
            array_walk_recursive($rows, function (&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
            });
            echo json_encode(['ok'=>true,'total'=>$total,'paginas'=>$paginas,
                              'pagina'=>$pagina,'datos'=>$rows], JSON_UNESCAPED_UNICODE);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    private function sincronizarSucursal(PDO $db, array $p, string $accion): void
    {
        try {
            $codigo = $p[':cod'];
            if ($accion !== 'nuevo') {
                $cnt = $db->prepare("SELECT COUNT(*) FROM sucprest WHERE TRIM(suprestado)=:c");
                $cnt->execute([':c' => $codigo]);
                if ((int)$cnt->fetchColumn() > 0) return; // ya tiene sucursal
            }
            $usr    = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));
            $dirmap = trim(($p[':direcc'] ?? '') . ', '
                         . ($p[':localidad'] ?? '') . ', '
                         . ($p[':zona'] ?? '') . ' Argentina');
            $db->prepare(
                "INSERT INTO sucprest
                    (suprestado, sucodigo, sudir, sulocalida, suzona,
                     sutelef, sunombre, sucontacto, suemail,
                     suhorario, sudesde, sudirmap, user, fechaup)
                 VALUES
                    (:cod, :cod2, :dir, :loc, :zona,
                     :tel, :nom, :cont, :mail,
                     :hor, :desde, :map, :usr, CURDATE())"
            )->execute([
                ':cod'   => $codigo,  ':cod2'  => $codigo,
                ':dir'   => $p[':direcc']    ?? '',
                ':loc'   => $p[':localidad'] ?? '',
                ':zona'  => $p[':zona']      ?? '',
                ':tel'   => $p[':telcons']   ?? '',
                ':nom'   => $p[':nombre']    ?? '',
                ':cont'  => $p[':contacto']  ?? '',
                ':mail'  => $p[':mailauto']  ?? '',
                ':hor'   => $p[':horario1']  ?? '',
                ':desde' => $p[':fechaalta'] ? substr($p[':fechaalta'], 0, 10) : date('Y-m-d'),
                ':map'   => $dirmap,
                ':usr'   => $usr,
            ]);
        } catch (PDOException $e) {
            error_log('sincronizarSucursal: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // CATÁLOGOS Y DEFAULTS PARA EL FORMULARIO
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?action=prestador_catalogo
     * Devuelve todos los combos del formulario en una sola llamada.
     */
    public function prestadorCatalogo(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = $this->db();

            // Categorías (deduplicated, non-empty)
            $cats = $db->query(
                "SELECT DISTINCT clave, descripcion FROM v_categorias
                 WHERE clave IS NOT NULL AND TRIM(clave)<>''
                 ORDER BY clave"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Localidades con su provincia (zona)
            $locs = $db->query(
                "SELECT DISTINCT TRIM(TACODIGO) AS TACODIGO,
                        TRIM(TADESCRIP) AS TADESCRIP,
                        TRIM(TAPROVIN)  AS TAPROVIN
                 FROM v_localidad
                 WHERE TRIM(TACODIGO)<>'' AND TRIM(TADESCRIP)<>''
                 ORDER BY TADESCRIP"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Zonas
            $zonas = $db->query(
                "SELECT DISTINCT TRIM(TACODIGO) AS TACODIGO,
                        TRIM(TADESCRIP) AS TADESCRIP
                 FROM v_zonas
                 WHERE TRIM(TACODIGO)<>'' AND TRIM(TADESCRIP)<>''
                 ORDER BY TADESCRIP"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Grupo web: ebamp.GRUPOWEB guarda la DESCRIPCION directamente (no la clave 'GRW')
            // Unimos v_grupoweb con los valores reales de ebamp para cubrir datos legacy
            $grupoweb = $db->query(
                "SELECT DISTINCT v AS clave, v AS descripcion FROM (
                    SELECT TRIM(descripcion) AS v FROM v_grupoweb
                    WHERE TRIM(descripcion) != ''
                    UNION
                    SELECT TRIM(GRUPOWEB) AS v FROM ebamp
                    WHERE TRIM(GRUPOWEB) != ''
                 ) AS combined
                 ORDER BY v"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Sub-categorías: ebamp.SUBCATEG guarda la DESCRIPCION (TADESCRIP), no el TACODIGO
            // Unimos v_grsub.TADESCRIP con los valores reales de ebamp (hay legacy sin prefijo)
            $grsub = $db->query(
                "SELECT DISTINCT v AS TACODIGO, v AS TADESCRIP FROM (
                    SELECT TRIM(TADESCRIP) AS v FROM v_grsub
                    WHERE TRIM(TADESCRIP) != ''
                    UNION
                    SELECT TRIM(SUBCATEG) AS v FROM ebamp
                    WHERE TRIM(SUBCATEG) != ''
                 ) AS combined
                 ORDER BY v"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Bancos
            $bancos = $db->query(
                "SELECT clave, descripcion FROM v_bancos
                 WHERE clave IS NOT NULL AND TRIM(clave)<>''
                 ORDER BY descripcion"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Empresas
            $empresas = $db->query(
                "SELECT clave, descripcion FROM v_empresas
                 WHERE clave IS NOT NULL AND TRIM(clave)<>''
                 ORDER BY descripcion"
            )->fetchAll(PDO::FETCH_ASSOC);

            // Forzar UTF-8 en todos
            $clean = function (array &$arr) {
                array_walk_recursive($arr, function (&$v) {
                    if (is_string($v)) {
                        $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8, ISO-8859-1');
                    }
                });
            };
            $clean($cats);  $clean($locs);  $clean($zonas);
            $clean($grupoweb); $clean($grsub); $clean($bancos); $clean($empresas);

            echo json_encode([
                'ok'       => true,
                'categorias'  => $cats,
                'localidades' => $locs,
                'zonas'       => $zonas,
                'grupoweb'    => $grupoweb,
                'grsub'       => $grsub,
                'bancos'      => $bancos,
                'empresas'    => $empresas,
            ], JSON_UNESCAPED_UNICODE);

        } catch (PDOException $e) {
            $this->jsonError('Error al cargar catálogos: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * GET ?action=prestador_defaults
     * Retorna valores auto-calculados para un nuevo prestador.
     */
    public function prestadorDefaults(): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $db = $this->db();

            // Matricula: MAX(matricula LIKE 'A0%') + 1 → 'A' + str_pad(n, 7, '0')
            $rowMat  = $db->query(
                "SELECT MAX(TRIM(matricula)) AS mx FROM ebamp WHERE TRIM(matricula) LIKE 'A0%'"
            )->fetch(PDO::FETCH_ASSOC);
            $maxMat  = trim($rowMat['mx'] ?? 'A0000000');
            $numMat  = intval(substr($maxMat, 1)) + 1;
            $newMat  = 'A' . str_pad($numMat, 7, '0', STR_PAD_LEFT);

            // Legajo: MAX(LEGAJO) + 1
            $rowLeg  = $db->query("SELECT MAX(LEGAJO) AS mx FROM ebamp")->fetch(PDO::FETCH_ASSOC);
            $newLeg  = (int)($rowLeg['mx'] ?? 0) + 1;

            // IDWEB: MAX(IDWEB) + 1
            $rowWeb  = $db->query("SELECT MAX(IDWEB) AS mx FROM ebamp")->fetch(PDO::FETCH_ASSOC);
            $newWeb  = (int)($rowWeb['mx'] ?? 0) + 1;

            // F.Baja = hoy + 36500 días
            $fechaAlta = date('Y-m-d');
            $fechaBaja = date('Y-m-d', strtotime($fechaAlta . ' +36500 days'));

            echo json_encode([
                'ok'        => true,
                'matricula' => $newMat,
                'legajo'    => $newLeg,
                'idweb'     => $newWeb,
                'fechaalta' => $fechaAlta,
                'fechabaja' => $fechaBaja,
            ]);
        } catch (PDOException $e) {
            $this->jsonError('Error al calcular defaults: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    // OBSERVACIONES
    // ══════════════════════════════════════════════════════════════════════

    /** GET ?action=obs_get&codigo=XXX */
    public function obsGet(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        $codigo = trim($_GET['codigo'] ?? '');
        if (!$codigo) { echo json_encode(['ok' => true, 'memo' => '', 'fecha' => '']); exit; }
        try {
            $row = $this->db()->prepare(
                "SELECT memo, usuario, DATE_FORMAT(fechaupdate,'%d/%m/%Y %H:%i') AS fup
                 FROM prestadores_observaciones WHERE codigo=:c LIMIT 1"
            );
            $row->execute([':c' => $codigo]);
            $d = $row->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'memo' => $d['memo'] ?? '', 'fecha' => $d['fup'] ?? '']);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** POST ?action=obs_save */
    public function obsSave(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { $this->jsonError('CSRF inválido.'); }
        $codigo = trim($_POST['codigo'] ?? '');
        $memo   = trim($_POST['memo']   ?? '');
        if (!$codigo) { $this->jsonError('Código requerido.'); }
        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 20));
        try {
            $db = $this->db();
            $db->prepare(
                "INSERT INTO prestadores_observaciones (codigo, memo, usuario)
                 VALUES (:c, :m, :u)
                 ON DUPLICATE KEY UPDATE memo=:m2, usuario=:u2"
            )->execute([':c'=>$codigo, ':m'=>$memo, ':u'=>$usr, ':m2'=>$memo, ':u2'=>$usr]);
            echo json_encode(['ok' => true, 'msg' => 'Observaciones guardadas.']);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    // INFO LIQUIDACIONES
    // ══════════════════════════════════════════════════════════════════════

    /** GET ?action=infoliq_get&codigo=XXX */
    public function infoliqGet(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        $codigo = trim($_GET['codigo'] ?? '');
        if (!$codigo) { echo json_encode(['ok' => true, 'memo' => '', 'fecha' => '']); exit; }
        try {
            $row = $this->db()->prepare(
                "SELECT memo, usuario, DATE_FORMAT(fechaupdate,'%d/%m/%Y %H:%i') AS fup
                 FROM prestadores_info_liquidaciones WHERE codigo=:c LIMIT 1"
            );
            $row->execute([':c' => $codigo]);
            $d = $row->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'memo' => $d['memo'] ?? '', 'fecha' => $d['fup'] ?? '']);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    /** POST ?action=infoliq_save */
    public function infoliqSave(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) { $this->jsonError('CSRF inválido.'); }
        $codigo = trim($_POST['codigo'] ?? '');
        $memo   = trim($_POST['memo']   ?? '');
        if (!$codigo) { $this->jsonError('Código requerido.'); }
        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 20));
        try {
            $db = $this->db();
            $db->prepare(
                "INSERT INTO prestadores_info_liquidaciones (codigo, memo, usuario)
                 VALUES (:c, :m, :u)
                 ON DUPLICATE KEY UPDATE memo=:m2, usuario=:u2"
            )->execute([':c'=>$codigo, ':m'=>$memo, ':u'=>$usr, ':m2'=>$memo, ':u2'=>$usr]);
            echo json_encode(['ok' => true, 'msg' => 'Info liquidaciones guardada.']);
        } catch (PDOException $e) {
            $this->jsonError($e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    // ADJUNTOS (adjctto)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET ?route=prestadores&action=adj_listar&codigo=XXX
     */
    public function adjListar(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        $codigo = trim($_GET['codigo'] ?? '');
        if ($codigo === '') { echo json_encode(['ok' => true, 'data' => []]); exit; }

        try {
            $db   = $this->db();
            $stmt = $db->prepare(
                "SELECT id, COFECHA, COPRESTADO, CONOMPREST, CORUTA,
                        COUSUARIO, COOBRASOC, COTAMANO, user, fechaupdate
                 FROM adjctto
                 WHERE TRIM(COPRESTADO) = :cod
                 ORDER BY COFECHA DESC, id DESC"
            );
            $stmt->execute([':cod' => $codigo]);
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            $this->jsonError('Error al listar adjuntos: ' . $e->getMessage());
        }
        exit;
    }

    /**
     * POST ?route=prestadores&action=adj_subir
     * Sube un archivo a /contratos/ y registra en adjctto.
     */
    public function adjSubir(): void
    {
        $this->requireAuth();
        $this->requirePermiso('MNU_ARC_PRESTADORES');
        header('Content-Type: application/json; charset=utf-8');

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $this->jsonError('Token de seguridad inválido.');
        }

        $codigo   = trim($_POST['codigo']           ?? '');
        $nombre   = trim($_POST['nombre_prestador'] ?? '');

        if ($codigo === '') { $this->jsonError('Código de prestador requerido.'); }
        if (empty($_FILES['archivo'])) { $this->jsonError('No se recibió ningún archivo.'); }

        $file = $_FILES['archivo'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Error al recibir el archivo (código ' . $file['error'] . ').');
        }

        $maxBytes = 10 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $this->jsonError('El archivo supera el límite de 10 MB.');
        }

        // Extensiones permitidas
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','msg','txt'];
        if (!in_array($ext, $allowed, true)) {
            $this->jsonError('Tipo de archivo no permitido.');
        }

        $carpeta = __DIR__ . '/../contratos/';
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $nombreOriginal = preg_replace('/[^a-zA-Z0-9_.\-]/', '_', $file['name']);
        $nombreGuardado = $codigo . '_' . date('YmdHis') . '_' . $nombreOriginal;
        $rutaFisica     = $carpeta . $nombreGuardado;
        $rutaBD         = 'Z:\\MODULOS\\' . $nombreGuardado;

        if (!move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            $this->jsonError('No se pudo guardar el archivo en el servidor.');
        }

        $usr = strtoupper(substr($_SESSION['user']['username'] ?? 'SIS', 0, 10));

        try {
            $db   = $this->db();
            $stmt = $db->prepare(
                "INSERT INTO adjctto
                    (COFECHA, COPRESTADO, CONOMPREST, CORUTA, COUSUARIO, COTAMANO, user)
                 VALUES
                    (CURDATE(), :cod, :nom, :ruta, :usr, :tam, :usr2)"
            );
            $stmt->execute([
                ':cod'  => $codigo,
                ':nom'  => $nombre,
                ':ruta' => $rutaBD,
                ':usr'  => $usr,
                ':tam'  => $file['size'],
                ':usr2' => $usr,
            ]);
            echo json_encode(['ok' => true, 'msg' => 'Archivo subido correctamente.']);
        } catch (PDOException $e) {
            // Archivo ya subido — devolver error de BD
            @unlink($rutaFisica);
            $this->jsonError('Error al registrar el adjunto: ' . $e->getMessage());
        }
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Resuelve la MATRICULA de ebamp a partir del CODIGO.
     * obramed.MEDICO almacena la MATRICULA, NO el CODIGO.
     * Si no se encuentra (prestador antiguo sin CODIGO registrado), retorna el
     * mismo $codigo como fallback para no romper la consulta.
     */
    private function resolverMatricula(PDO $db, string $codigo): string
    {
        $stmt = $db->prepare(
            "SELECT TRIM(MATRICULA) FROM ebamp WHERE TRIM(CODIGO) = :c LIMIT 1"
        );
        $stmt->execute([':c' => $codigo]);
        $mat = (string)$stmt->fetchColumn();
        return ($mat !== '' && $mat !== false) ? $mat : $codigo;
    }

    private function registrarNovedad(PDO $db, string $codigo, string $cosoc, string $operacion): void
    {
        $usuario = $_SESSION['user']['username'] ?? 'sistema';
        $now     = date('Y-m-d H:i:s');
        try {
            $db->prepare(
                "INSERT INTO novedade (operacion, fecha, usuario, codigo, cosoc)
                 VALUES (:op, :fecha, :usr, :cod, :cos)"
            )->execute([
                ':op'   => $operacion, ':fecha' => $now,
                ':usr'  => $usuario,   ':cod'   => $codigo, ':cos' => $cosoc,
            ]);
        } catch (PDOException $e) {
            error_log("registrarNovedad: {$e->getMessage()}");
        }
    }

    /**
     * Actualiza ebamp.trabajaos a '1'/'0' según si el prestador tiene OS activas.
     * $matricula = valor de ebamp.MATRICULA (usado para consultar obramed.MEDICO).
     * Si no se pasa, se resuelve internamente desde $codigo.
     */
    private function actualizarTrabajosOS(PDO $db, string $codigo, string $matricula = ''): void
    {
        try {
            if ($matricula === '') {
                $matricula = $this->resolverMatricula($db, $codigo);
            }
            $stmtC = $db->prepare(
                "SELECT COUNT(*) FROM obramed
                 WHERE TRIM(MEDICO) = :mat AND FECHABAJA >= CURDATE()"
            );
            $stmtC->execute([':mat' => $matricula]);
            $val = (int)$stmtC->fetchColumn() > 0 ? '1' : '0';
            $db->prepare("UPDATE ebamp SET trabajaos = :val WHERE TRIM(CODIGO) = :cod")
               ->execute([':val' => $val, ':cod' => $codigo]);
        } catch (PDOException $e) {
            error_log("actualizarTrabajosOS: {$e->getMessage()}");
        }
    }

    private function db(): PDO
    {
        require_once __DIR__ . '/../config/database.php';
        return getDBConnection();
    }

    private function jsonError(string $msg): void
    {
        echo json_encode(['ok' => false, 'error' => $msg]);
        exit;
    }

    // ── Guards ────────────────────────────────────────────────────────────

    private function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Sesión expirada. Recargue la página.']);
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
            if (isset($p['CLAVE']) && $p['CLAVE'] === $clave) return;
        }
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Sin permiso para esta operación.']);
            exit;
        }
        $_SESSION['flash_warning'] = 'No tiene permiso para acceder al módulo solicitado.';
        header('Location: ' . $this->baseUrl() . '?route=dashboard');
        exit;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function baseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = dirname($_SERVER['SCRIPT_NAME']);
        $script = rtrim($script, '/');
        return "{$scheme}://{$host}{$script}/index.php";
    }
}
