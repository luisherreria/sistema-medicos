<?php
/**
 * models/ObraSocialModel.php
 * Catálogo de Obras Sociales — cartilla.obrasoc
 *
 * Vigencia (norma de bajas):
 *   Activa  = TAFECHAFIN IS NULL
 *          OR TAFECHAFIN = '0000-00-00 00:00:00'
 *          OR TAFECHAFIN >= CURDATE()
 *   Inactiva = fecha fin informada, distinta de cero y anterior a hoy.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class ObraSocialModel
{
    /** @var PDO */
    private $db;

    /**
     * Filtro obligatorio cuando no se piden las dadas de baja.
     * Debe permanecer como literal SQL (no como parámetro).
     */
    private const SQL_VIGENTE =
        "(TAFECHAFIN IS NULL OR TAFECHAFIN = '0000-00-00 00:00:00' OR TAFECHAFIN >= CURDATE())";

    /** Bandera calculada: 1 si la obra social está dada de baja. */
    private const SQL_ES_INACTIVA =
        "(TAFECHAFIN IS NOT NULL AND TAFECHAFIN != '0000-00-00 00:00:00' AND TAFECHAFIN < CURDATE())";

    /** Largos reales de cartilla.obrasoc (char/varchar). */
    private const LARGOS = [
        'TACODIGO'    => 8,
        'TADESCRIP'   => 40,
        'TAMESAOP'    => 2,
        'TADIR'       => 40,
        'TATELEF'     => 30,
        'TACP'        => 10,
        'TACONTACTO'  => 30,
        'TACUIT'      => 13,
        'TACONDIVA'   => 15,
        'TAPADRON'    => 80,
        'TAEMAIL'     => 150,
        'TAMAILCAR'   => 150,
        'DIRCOSEG'    => 100,
        'LINKCOSEG'   => 150,
        'DIRNORMA'    => 150,
        'GRUPO_OS'    => 2,
        'USUARIO'     => 5,
    ];

    /** Marcas lógicas VFP: T / F. */
    private const MARCAS = [
        'XPREST', 'TAEXCCAR', 'MAILCOSEG', 'MAILNORMA', 'ISVERPLAN', 'CONVADM',
    ];

    /** Importes decimal(10,2). */
    private const IMPORTES = [
        'TACOSEGCON', 'TACOSEGPRA', 'TACOSEGDET', 'TAVALCAPIT', 'TAREFACT',
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Listado de obras sociales.
     *
     * Por defecto ($mostrarInactivas = false) excluye las dadas de baja.
     * Con $mostrarInactivas = true devuelve el universo completo.
     *
     * @param bool   $mostrarInactivas
     * @param string $busqueda Código, nombre o teléfono
     * @return array<int, array<string, mixed>>
     */
    public function getObrasSociales($mostrarInactivas = false, $busqueda = '')
    {
        $mostrarInactivas = (bool) $mostrarInactivas;
        $busqueda         = trim((string) $busqueda);

        $where  = [];
        $params = [];

        if (!$mostrarInactivas) {
            $where[] = self::SQL_VIGENTE;
        }

        if ($busqueda !== '') {
            $where[] = "(TACODIGO LIKE :q_codigo OR TADESCRIP LIKE :q_nombre OR TATELEF LIKE :q_tel)";
            $like = '%' . $this->escaparLike($busqueda) . '%';
            $params[':q_codigo'] = $like;
            $params[':q_nombre'] = $like;
            $params[':q_tel']    = $like;
        }

        $sql = "SELECT
                    TRIM(TACODIGO)   AS TACODIGO,
                    TRIM(TADESCRIP)  AS TADESCRIP,
                    TACANTAFIL,
                    TRIM(TATELEF)    AS TATELEF,
                    TAFECHAFIN,
                    " . self::SQL_ES_INACTIVA . " AS ES_INACTIVA
                FROM cartilla.obrasoc";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY TACODIGO ASC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, PDO::PARAM_STR);
        }
        $stmt->execute();

        $filas = $stmt->fetchAll();
        foreach ($filas as &$fila) {
            $fila['ES_INACTIVA'] = ((int) $fila['ES_INACTIVA'] === 1);
            $fila['TACANTAFIL']  = $this->normalizarAfiliadosSalida($fila['TACANTAFIL']);
            $fila['TAFECHAFIN']  = $fila['TAFECHAFIN'] !== null ? (string) $fila['TAFECHAFIN'] : null;
        }
        unset($fila);

        return $filas;
    }

    /**
     * Ficha completa para el formulario. Alias de getObraSocialByCodigo().
     *
     * @param string $codigo
     * @return array<string, mixed>|null
     */
    public function obtener($codigo)
    {
        return $this->getObraSocialByCodigo($codigo);
    }

    /**
     * Recupera todos los atributos editables de cartilla.obrasoc.
     *
     * Nombres de controles VFP → columna:
     *   MesaOp MESAOP          → TAMESAOP
     *   Dirección TADIRECC     → TADIR
     *   C. Postal CPOSTAL      → TACP
     *   I.V.A. TAIVA           → TACONDIVA
     *   Coseguros              → TACOSEGCON, TACOSEGPRA, TACOSEGDET
     *   Padrón PADRON          → TAPADRON
     *   Valor cápita CAPITA    → TAVALCAPIT
     *   % refact. REFACT       → TAREFACT
     *   Afiliados              → TACANTAFIL
     *   Vigencia FECHAINI      → TAFECHAINI / TAFECHAFIN
     *   Mail EMAIL / EMAILCAR  → TAEMAIL / TAMAILCAR
     *   DIRCOSEG1 / EHIPER / DIRNORMA1 → DIRCOSEG / LINKCOSEG / DIRNORMA
     *   PORPREST, EXCLUCART, INFORMA_COS_MAIL, INFORMA_NORMA_MAIL,
     *   VERIF_PLAN, CONVENIO_ADMIN → XPREST, TAEXCCAR, MAILCOSEG, MAILNORMA, ISVERPLAN, CONVADM
     *   Grupo OS               → GRUPO_OS (char 2: SI / NO)
     *
     * CODADH (afiliado adherente) no es columna de cartilla.obrasoc.
     *
     * @param string $codigo
     * @return array<string, mixed>|null
     */
    public function getObraSocialByCodigo($codigo)
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === '') {
            return null;
        }

        $sql = "SELECT
                    TRIM(TACODIGO)    AS TACODIGO,
                    TRIM(TADESCRIP)   AS TADESCRIP,
                    TRIM(TAMESAOP)    AS TAMESAOP,
                    TRIM(TADIR)       AS TADIR,
                    TRIM(TATELEF)     AS TATELEF,
                    TRIM(TACP)        AS TACP,
                    TRIM(TACONTACTO)  AS TACONTACTO,
                    TRIM(TACUIT)      AS TACUIT,
                    TRIM(TACONDIVA)   AS TACONDIVA,
                    TACOSEGCON,
                    TACOSEGPRA,
                    TACOSEGDET,
                    TRIM(TAPADRON)    AS TAPADRON,
                    TAVALCAPIT,
                    TAREFACT,
                    TACANTAFIL,
                    TAFECHAINI,
                    TAFECHAFIN,
                    TRIM(TAEMAIL)     AS TAEMAIL,
                    TRIM(TAMAILCAR)   AS TAMAILCAR,
                    TRIM(DIRCOSEG)    AS DIRCOSEG,
                    TRIM(LINKCOSEG)   AS LINKCOSEG,
                    TRIM(DIRNORMA)    AS DIRNORMA,
                    TRIM(XPREST)      AS XPREST,
                    TRIM(TAEXCCAR)    AS TAEXCCAR,
                    TRIM(MAILCOSEG)   AS MAILCOSEG,
                    TRIM(MAILNORMA)   AS MAILNORMA,
                    TRIM(ISVERPLAN)   AS ISVERPLAN,
                    TRIM(CONVADM)     AS CONVADM,
                    TRIM(GRUPO_OS)    AS GRUPO_OS,
                    " . self::SQL_ES_INACTIVA . " AS ES_INACTIVA
                FROM cartilla.obrasoc
                WHERE TRIM(TACODIGO) = :codigo
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':codigo' => $codigo]);
        $fila = $stmt->fetch();
        if (!$fila) {
            return null;
        }

        return $this->formatearFicha($fila);
    }

    /**
     * Alta de una obra social.
     *
     * @param array<string, mixed> $datos
     * @param string               $usuario
     * @return string Código normalizado
     */
    public function insertar(array $datos, $usuario)
    {
        $codigo = $this->normalizarCodigo(isset($datos['TACODIGO']) ? $datos['TACODIGO'] : '');
        if ($codigo === '') {
            throw new InvalidArgumentException('El código es obligatorio.');
        }
        if ($this->existe($codigo)) {
            throw new InvalidArgumentException('Ya existe una obra social con el código ' . $codigo . '.');
        }

        $campos = $this->camposGenerales($datos);
        if ($campos['TADESCRIP'] === '') {
            throw new InvalidArgumentException('El nombre / razón social es obligatorio.');
        }

        if ($campos['TAFECHAFIN'] === null) {
            $campos['TAFECHAFIN'] = '2099-12-31 00:00:00';
        }

        $columnas = array_keys($campos);
        $sql = "INSERT INTO cartilla.obrasoc (
                    TATIPO, TACODIGO, " . implode(', ', $columnas) . ", FCARGA, USUARIO
                ) VALUES (
                    'OS', :TACODIGO, :" . implode(', :', $columnas) . ", NOW(), :USUARIO
                )";

        $params = [':TACODIGO' => $codigo];
        foreach ($campos as $col => $valor) {
            $params[':' . $col] = $valor;
        }
        $params[':USUARIO'] = $this->texto($usuario, self::LARGOS['USUARIO']);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $codigo;
    }

    /**
     * Alta o edición según $datos['modo'] (alta|edicion).
     *
     * @param array<string, mixed> $datos
     * @param string               $usuario
     * @return string Código normalizado
     */
    public function guardarObraSocial(array $datos, $usuario)
    {
        $modo = strtolower(trim((string) (isset($datos['modo']) ? $datos['modo'] : 'alta')));
        $codigo = $this->normalizarCodigo(isset($datos['TACODIGO']) ? $datos['TACODIGO'] : '');

        if ($modo === 'edicion') {
            $this->actualizar($codigo, $datos, $usuario);
            return $codigo;
        }

        return $this->insertar($datos, $usuario);
    }

    /**
     * Edición de datos generales. El código no se modifica.
     *
     * @param string               $codigo
     * @param array<string, mixed> $datos
     * @param string               $usuario
     */
    public function actualizar($codigo, array $datos, $usuario)
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === '' || !$this->existe($codigo)) {
            throw new InvalidArgumentException('No se encontró la obra social a editar.');
        }

        $campos = $this->camposGenerales($datos);
        if ($campos['TADESCRIP'] === '') {
            throw new InvalidArgumentException('El nombre / razón social es obligatorio.');
        }

        $set = [];
        $params = [];
        foreach ($campos as $col => $valor) {
            $set[] = $col . ' = :' . $col;
            $params[':' . $col] = $valor;
        }
        $set[] = 'FCARGA = NOW()';
        $set[] = 'USUARIO = :USUARIO';
        $params[':USUARIO'] = $this->texto($usuario, self::LARGOS['USUARIO']);
        $params[':codigo'] = $codigo;

        $sql = "UPDATE cartilla.obrasoc
                   SET " . implode(",\n                       ", $set) . "
                 WHERE TRIM(TACODIGO) = :codigo";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Borra la obra social como el sistema VFP:
     * foto del registro, baja de asignaciones en cartilla.obramed,
     * baja de prestaciones en cartilla.prestac, baja de cartilla.obrasoc
     * y auditoría en vfpmedicos.auditoria_log.
     * No existe la tabla obramedP.
     *
     * @param string $codigo
     * @param string $usuario
     * @return array<string, mixed>
     */
    public function eliminarObraSocial($codigo, $usuario = '')
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === '') {
            return ['ok' => false, 'error' => 'Código de obra social no especificado.'];
        }

        $usuario = trim((string) $usuario);
        if ($usuario === '' && isset($_SESSION['user']['username'])) {
            $usuario = trim((string) $_SESSION['user']['username']);
        }
        if ($usuario === '') {
            $usuario = 'SISTEMA';
        }
        $usuario = $this->texto($usuario, 255);

        try {
            $this->db->beginTransaction();

            $stmtSel = $this->db->prepare(
                'SELECT * FROM cartilla.obrasoc WHERE TRIM(TACODIGO) = :cod LIMIT 1'
            );
            $stmtSel->execute([':cod' => $codigo]);
            $mORIG = $stmtSel->fetch(PDO::FETCH_ASSOC);

            if (!$mORIG) {
                $this->db->rollBack();
                return ['ok' => false, 'error' => 'La obra social no existe o ya fue eliminada.'];
            }

            $stmtObramed = $this->db->prepare(
                'DELETE FROM cartilla.obramed WHERE TRIM(OBRASOC) = :cod'
            );
            $stmtObramed->execute([':cod' => $codigo]);
            $borradosObramed = $stmtObramed->rowCount();

            $stmtPrestac = $this->db->prepare(
                'DELETE FROM cartilla.prestac WHERE TRIM(OBRASOC) = :cod'
            );
            $stmtPrestac->execute([':cod' => $codigo]);
            $borradosPrestac = $stmtPrestac->rowCount();

            $stmtDelOS = $this->db->prepare(
                'DELETE FROM cartilla.obrasoc WHERE TRIM(TACODIGO) = :cod'
            );
            $stmtDelOS->execute([':cod' => $codigo]);
            if ($stmtDelOS->rowCount() < 1) {
                throw new RuntimeException('No se pudo borrar el registro de la obra social.');
            }

            $detalle = 'OBRASOC | codigo=' . $codigo
                . ' | B | ' . trim((string) (isset($mORIG['TADESCRIP']) ? $mORIG['TADESCRIP'] : ''))
                . ' | obramed=' . $borradosObramed
                . ' prestac=' . $borradosPrestac
                . ' | Datos: ' . json_encode($this->fotoAuditoria($mORIG), JSON_UNESCAPED_UNICODE);

            $stmtAud = $this->db->prepare(
                "INSERT INTO vfpmedicos.auditoria_log
                    (codigo_prestador, usuario_email, fecha_hora, modulo, accion_detalle)
                 VALUES (:cod, :usr, NOW(), 'OBRASOC_BORRAR', :det)"
            );
            $stmtAud->execute([
                ':cod' => $this->texto($codigo, 50),
                ':usr' => $usuario,
                ':det' => $this->texto($detalle, 255),
            ]);

            $this->db->commit();

            return [
                'ok'                 => true,
                'msg'                => 'Obra social eliminada correctamente junto con sus asignaciones.'
                    . ' Prestadores: ' . $borradosObramed
                    . '. Prestaciones: ' . $borradosPrestac . '.',
                'obramed_afectados'  => $borradosObramed,
                'prestac_afectados'  => $borradosPrestac,
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('ObraSocialModel::eliminarObraSocial — ' . $e->getMessage());
            return ['ok' => false, 'error' => 'No se pudo eliminar la obra social.'];
        }
    }

    /**
     * Foto compacta del registro (equivalente a SCATTER) para la auditoría.
     * accion_detalle admite 255 caracteres.
     *
     * @param array<string, mixed> $fila
     * @return array<string, string>
     */
    private function fotoAuditoria(array $fila)
    {
        $foto = [];
        foreach ($fila as $campo => $valor) {
            if ($valor === null) {
                continue;
            }
            $texto = trim((string) $valor);
            if ($texto === '' || $texto === '0.00' || $texto === '0000-00-00 00:00:00') {
                continue;
            }
            $foto[$campo] = $texto;
        }
        return $foto;
    }

    /**
     * Cierre de período: graba TAFECHAFIN.
     * Si $codigo viene vacío y $aplicarTodas es true, cierra solo las vigentes.
     * No pisa obras que ya estaban dadas de baja.
     *
     * @param string $fecha   Y-m-d
     * @param string $codigo
     * @param bool   $aplicarTodas
     * @param string $usuario
     * @return int Filas actualizadas
     */
    public function cerrarPeriodo($fecha, $codigo = '', $aplicarTodas = false, $usuario = '')
    {
        $fechaSql = $this->fechaSql($fecha, true);
        $codigo   = $this->normalizarCodigo($codigo);
        $usuario  = $this->texto($usuario, self::LARGOS['USUARIO']);

        if ($codigo !== '') {
            if (!$this->existe($codigo)) {
                throw new InvalidArgumentException('No se encontró la obra social ' . $codigo . '.');
            }
            $sql = "UPDATE cartilla.obrasoc
                       SET TAFECHAFIN = :fecha,
                           FCARGA     = NOW(),
                           USUARIO    = :usuario
                     WHERE TRIM(TACODIGO) = :codigo";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':fecha'   => $fechaSql,
                ':usuario' => $usuario,
                ':codigo'  => $codigo,
            ]);
            return $stmt->rowCount();
        }

        if (!$aplicarTodas) {
            throw new InvalidArgumentException('Indique el código o confirme el cierre de todas las obras vigentes.');
        }

        $sql = "UPDATE cartilla.obrasoc
                   SET TAFECHAFIN = :fecha,
                       FCARGA     = NOW(),
                       USUARIO    = :usuario
                 WHERE " . self::SQL_VIGENTE;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':fecha'   => $fechaSql,
            ':usuario' => $usuario,
        ]);

        return $stmt->rowCount();
    }

    /**
     * Permiso activo del usuario en vfpmedicos.users_permissions.
     *
     * @param int    $userId
     * @param string $clave
     * @return bool
     */
    public function tienePermiso($userId, $clave)
    {
        $userId = (int) $userId;
        $clave  = trim((string) $clave);
        if ($userId <= 0 || $clave === '') {
            return false;
        }

        $sql = "SELECT COUNT(*)
                FROM vfpmedicos.users_permissions up
                INNER JOIN vfpmedicos.permisos p ON up.PERMISSION_ID = p.CLAVE
                WHERE up.USER_ID = :user_id
                  AND p.CLAVE    = :clave
                  AND up.ADDED   = 1
                  AND (up.REMOVED = 0 OR up.REMOVED IS NULL)";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':clave'   => $clave,
            ]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log('ObraSocialModel::tienePermiso() — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $codigo
     * @return bool
     */
    public function existe($codigo)
    {
        $codigo = $this->normalizarCodigo($codigo);
        if ($codigo === '') {
            return false;
        }
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM cartilla.obrasoc WHERE TRIM(TACODIGO) = :codigo'
        );
        $stmt->execute([':codigo' => $codigo]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    private function camposGenerales(array $datos)
    {
        $campos = [
            'TADESCRIP'  => $this->texto(isset($datos['TADESCRIP']) ? $datos['TADESCRIP'] : '', self::LARGOS['TADESCRIP']),
            'TAMESAOP'   => $this->texto(isset($datos['TAMESAOP']) ? $datos['TAMESAOP'] : '', self::LARGOS['TAMESAOP']),
            'TADIR'      => $this->texto(isset($datos['TADIR']) ? $datos['TADIR'] : '', self::LARGOS['TADIR']),
            'TATELEF'    => $this->texto(isset($datos['TATELEF']) ? $datos['TATELEF'] : '', self::LARGOS['TATELEF']),
            'TACP'       => $this->texto(isset($datos['TACP']) ? $datos['TACP'] : '', self::LARGOS['TACP']),
            'TACONTACTO' => $this->texto(isset($datos['TACONTACTO']) ? $datos['TACONTACTO'] : '', self::LARGOS['TACONTACTO']),
            'TACUIT'     => $this->texto(isset($datos['TACUIT']) ? $datos['TACUIT'] : '', self::LARGOS['TACUIT']),
            'TACONDIVA'  => $this->texto(isset($datos['TACONDIVA']) ? $datos['TACONDIVA'] : '', self::LARGOS['TACONDIVA']),
            'TAPADRON'   => $this->texto(isset($datos['TAPADRON']) ? $datos['TAPADRON'] : '', self::LARGOS['TAPADRON']),
            'TACANTAFIL' => $this->afiliados(isset($datos['TACANTAFIL']) ? $datos['TACANTAFIL'] : null),
            'TAFECHAINI' => $this->fechaSql(isset($datos['TAFECHAINI']) ? $datos['TAFECHAINI'] : '', false),
            'TAFECHAFIN' => $this->fechaSql(isset($datos['TAFECHAFIN']) ? $datos['TAFECHAFIN'] : '', false),
            'TAEMAIL'    => $this->texto(isset($datos['TAEMAIL']) ? $datos['TAEMAIL'] : '', self::LARGOS['TAEMAIL']),
            'TAMAILCAR'  => $this->texto(isset($datos['TAMAILCAR']) ? $datos['TAMAILCAR'] : '', self::LARGOS['TAMAILCAR']),
            'DIRCOSEG'   => $this->texto(isset($datos['DIRCOSEG']) ? $datos['DIRCOSEG'] : '', self::LARGOS['DIRCOSEG']),
            'LINKCOSEG'  => $this->texto(isset($datos['LINKCOSEG']) ? $datos['LINKCOSEG'] : '', self::LARGOS['LINKCOSEG']),
            'DIRNORMA'   => $this->texto(isset($datos['DIRNORMA']) ? $datos['DIRNORMA'] : '', self::LARGOS['DIRNORMA']),
            'GRUPO_OS'   => $this->grupoOs(isset($datos['GRUPO_OS']) ? $datos['GRUPO_OS'] : ''),
        ];

        foreach (self::IMPORTES as $col) {
            $campos[$col] = $this->importe(isset($datos[$col]) ? $datos[$col] : null, $col);
        }
        foreach (self::MARCAS as $col) {
            $campos[$col] = $this->marca(isset($datos[$col]) ? $datos[$col] : '');
        }

        return $campos;
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function formatearFicha(array $fila)
    {
        $fila['ES_INACTIVA'] = ((int) $fila['ES_INACTIVA'] === 1);
        $fila['TACANTAFIL']  = $this->normalizarAfiliadosSalida($fila['TACANTAFIL']);
        $fila['TAFECHAINI']  = $this->fechaInput($fila['TAFECHAINI']);
        $fila['TAFECHAFIN']  = $this->fechaInput($fila['TAFECHAFIN']);

        foreach (self::IMPORTES as $col) {
            $fila[$col] = $this->importeSalida($fila[$col]);
        }
        foreach (self::MARCAS as $col) {
            $fila[$col] = strtoupper(trim((string) $fila[$col])) === 'T' ? 'T' : 'F';
        }

        return $fila;
    }

    /**
     * GRUPO_OS es char(2). Los valores en uso son SI y NO.
     *
     * @param mixed $valor
     * @return string
     */
    private function grupoOs($valor)
    {
        $v = strtoupper(trim((string) $valor));
        if ($v === '') {
            return '';
        }
        if ($v === 'TGF') {
            throw new InvalidArgumentException(
                'Grupo OS «TGF» no entra en GRUPO_OS (2 caracteres). Use SI o NO.'
            );
        }
        if (strlen($v) > self::LARGOS['GRUPO_OS']) {
            throw new InvalidArgumentException('Grupo OS admite hasta 2 caracteres (SI o NO).');
        }
        return $v;
    }

    /**
     * Marca lógica del sistema anterior: T o F.
     *
     * @param mixed $valor
     * @return string
     */
    private function marca($valor)
    {
        $v = strtoupper(trim((string) $valor));
        return in_array($v, ['T', '1', 'ON', 'SI', 'S', 'TRUE', 'YES'], true) ? 'T' : 'F';
    }

    /**
     * @param mixed  $valor
     * @param string $campo
     * @return string
     */
    private function importe($valor, $campo)
    {
        if ($valor === null) {
            return '0.00';
        }
        $v = trim((string) $valor);
        $v = str_replace(['$', ' '], '', $v);
        if ($v === '') {
            return '0.00';
        }
        if (strpos($v, ',') !== false && strpos($v, '.') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        } elseif (strpos($v, ',') !== false) {
            $v = str_replace(',', '.', $v);
        }
        if (!is_numeric($v)) {
            throw new InvalidArgumentException('El valor de ' . $campo . ' no es numérico.');
        }
        $n = round((float) $v, 2);
        if ($n < 0 || $n > 99999999.99) {
            throw new InvalidArgumentException('El valor de ' . $campo . ' está fuera de rango.');
        }
        return number_format($n, 2, '.', '');
    }

    /**
     * @param mixed $valor
     * @return string
     */
    private function importeSalida($valor)
    {
        if ($valor === null || $valor === '') {
            return '0.00';
        }
        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * @param mixed $valor
     * @return int|null
     */
    private function afiliados($valor)
    {
        if ($valor === null) {
            return null;
        }
        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }
        $v = str_replace(['.', ' '], '', $v);
        $v = str_replace(',', '.', $v);
        if (!is_numeric($v)) {
            throw new InvalidArgumentException('La cantidad de afiliados no es numérica.');
        }
        return (int) round((float) $v);
    }

    /**
     * @param mixed $valor
     * @return int|null
     */
    private function normalizarAfiliadosSalida($valor)
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        return (int) round((float) $valor);
    }

    /**
     * @param string $codigo
     * @return string
     */
    private function normalizarCodigo($codigo)
    {
        $codigo = strtoupper(trim((string) $codigo));
        if (function_exists('mb_substr')) {
            $codigo = mb_substr($codigo, 0, self::LARGOS['TACODIGO'], 'UTF-8');
        } else {
            $codigo = substr($codigo, 0, self::LARGOS['TACODIGO']);
        }
        return $codigo;
    }

    /**
     * @param mixed $valor
     * @param int   $max
     * @return string
     */
    private function texto($valor, $max)
    {
        $v = trim((string) $valor);
        if (function_exists('mb_substr')) {
            return mb_substr($v, 0, $max, 'UTF-8');
        }
        return substr($v, 0, $max);
    }

    /**
     * @param mixed $valor
     * @param bool  $obligatoria
     * @return string|null
     */
    private function fechaSql($valor, $obligatoria)
    {
        $v = trim((string) $valor);
        if ($v === '' || strpos($v, '0000-00-00') === 0) {
            if ($obligatoria) {
                throw new InvalidArgumentException('La fecha de cierre es obligatoria.');
            }
            return null;
        }
        $v = substr($v, 0, 10);
        $dt = DateTime::createFromFormat('Y-m-d', $v);
        if (!$dt || $dt->format('Y-m-d') !== $v) {
            throw new InvalidArgumentException('La fecha indicada no es válida.');
        }
        return $v . ' 00:00:00';
    }

    /**
     * @param mixed $valor
     * @return string
     */
    private function fechaInput($valor)
    {
        if ($valor === null || $valor === '') {
            return '';
        }
        $v = substr((string) $valor, 0, 10);
        if ($v === '0000-00-00') {
            return '';
        }
        return $v;
    }

    /**
     * @param string $valor
     * @return string
     */
    private function escaparLike($valor)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
    }
}
