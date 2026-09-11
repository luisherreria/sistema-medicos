<?php
/**
 * models/Prestador.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Capa de acceso a datos del padrón de prestadores (tabla `ebamp`).
 *
 * IMPORTANTE — Columnas reales de `ebamp` (todas en minúsculas):
 *   id, espec, codigo, categ, matricula, nombre, direcc, telcons, localidad, zona,
 *   telpart, celular, fax, lun, mar, mie, jue, vie, sab, mail, mail_pago, mail_venc,
 *   mail_deb, mail_auto, nuevos, espe, zonan, osba, osptv, gpm, selecc, tipo, codesp,
 *   tipomed, activo, recomenda, localida2, localida3, zona2, zona3, direcc2, direcc3,
 *   telcons2, telcons3, empresa, banco, sucursal, nombreemp, nombreban, nombresuc,
 *   horario1, horario2, horario3, cbu, labo, imag, contacto, nomalterna, tipodoc,
 *   nrodoc, sss, sssvenc, habilitac, seguro, segurovenc, matriculad, titulo, contrato,
 *   contrafec, clausula, trabajaos, tieneprest, baja, fbaja, cuit, cadena, chqord,
 *   seleccion, codtango, nroprov, conflicto, monotrib, fechaalta, fechabaja, condiva,
 *   agrupacion, delegacion, copia, retirado, buenperfil, especializ, exclucart, exclucall,
 *   importe, categoriza, prestador, faltacbu, faltarec, faltaotro, faltaotro2, anomdesde,
 *   xprest, infoliq, observa, nomfantas, certif, aviso, fechaavi, legajo, etitulo,
 *   emaxafil, elatitud, elongitu, ecountaf, dcontrato, confact, idbd, codreem, fmodif,
 *   fuser, fhora, id_prest, pracdefa, isauto, cargausr, subcateg, idweb, grupoweb,
 *   contvto, contliq, contcon, contaut, telvto, telliq, telcon, telaut, mailcontra,
 *   idred, fechaaum, aumento, isoncologo, exclstvta, valereci, matrimed, convenio,
 *   tienesucpr, tienesuc, sucexclu, pracexclu, haceinter, mail_fact, matriprov,
 *   telctra, telfact, contctra, contfact, motivobj, issuspend
 *
 * PDO::FETCH_ASSOC devuelve claves con el mismo case que el motor, por eso
 * todas las referencias a columnas y a $row['campo'] usan minúsculas.
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/../config/database.php';

class Prestador
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  LISTADO PRINCIPAL — con filtros y paginación
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Devuelve prestadores paginados para la grilla principal.
     *
     * @param array $filtros {
     *   'ocultar_inactivos' => bool,   // true = ocultar dados de baja
     *   'categ'             => string, // '' = todas las categorías
     *   'busqueda'          => string, // full-text en nombre/codigo/cuit/nomfantas
     *   'pagina'            => int,    // 1-based
     *   'por_pagina'        => int,
     * }
     * @return array { 'total', 'paginas', 'pagina', 'datos' }
     */
    public function listar(array $filtros = []): array
    {
        $ocultar   = $filtros['ocultar_inactivos'] ?? true;
        $categ     = trim($filtros['categ']     ?? '');
        $busqueda  = trim($filtros['busqueda']  ?? '');
        $pagina    = max(1, (int)($filtros['pagina']    ?? 1));
        $porPagina = max(1, min(500, (int)($filtros['por_pagina'] ?? 100)));

        [$where, $params] = $this->buildWhere($ocultar, $categ, $busqueda);

        // ── Total para paginación ─────────────────────────────────────────
        $stmtCnt = $this->db->prepare("SELECT COUNT(*) FROM ebamp{$where}");
        $stmtCnt->execute($params);
        $total   = (int)$stmtCnt->fetchColumn();
        $paginas = $total > 0 ? (int)ceil($total / $porPagina) : 1;

        // ── Datos paginados ────────────────────────────────────────────────
        // Sólo columnas que existen en la tabla real (todas en minúsculas).
        $offset  = ($pagina - 1) * $porPagina;
        $sqlData = "SELECT
                        TRIM(codigo)    AS codigo,
                        TRIM(matricula) AS matricula,
                        TRIM(nombre)    AS nombre,
                        TRIM(nomfantas) AS nomfantas,
                        TRIM(direcc)    AS direcc,
                        TRIM(zona)      AS zona,
                        TRIM(localidad) AS localidad,
                        TRIM(telcons)   AS telcons,
                        TRIM(celular)   AS celular,
                        TRIM(contacto)  AS contacto,
                        TRIM(grupoweb)  AS grupoweb,
                        TRIM(categ)     AS categ,
                        fechabaja,
                        fbaja,
                        baja,
                        TRIM(exclucart)  AS exclucart,
                        TRIM(tienesuc)   AS tienesuc,
                        TRIM(tienesucpr) AS tienesucpr,
                        TRIM(trabajaos)  AS trabajaos,
                        TRIM(tieneprest) AS tieneprest,
                        TRIM(issuspend)  AS issuspend
                    FROM ebamp
                    {$where}
                    ORDER BY nombre ASC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sqlData);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return [
            'total'   => $total,
            'paginas' => $paginas,
            'pagina'  => $pagina,
            'datos'   => $stmt->fetchAll(),
        ];
    }

    /**
     * Devuelve un único prestador por su `codigo`.
     */
    public function porCodigo(string $codigo): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM ebamp WHERE codigo = :codigo LIMIT 1"
        );
        $stmt->execute([':codigo' => $codigo]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  CATEGORÍAS — para el filtro del dropdown
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Lista de categorías desde v_categorias (clave + descripcion).
     * Se incluye ADEF para que el filtro permita buscarlo explícitamente.
     * @return array[]  [ ['clave' => 'CP', 'descripcion' => '…'], … ]
     */
    public function categorias(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT
                TRIM(clave)       AS clave,
                TRIM(descripcion) AS descripcion
             FROM v_categorias
             WHERE clave IS NOT NULL AND TRIM(clave) != ''
             ORDER BY clave ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPER PRIVADO — cláusula WHERE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Construye la cláusula WHERE y el array de parámetros PDO.
     *
     * Regla "Ocultar inactivos":
     *   Un prestador está activo si fechabaja IS NULL, es '0000-00-00'
     *   o es una fecha futura. Se usa la columna `fechabaja` (preferida)
     *   sin referenciar `baja` para evitar conflictos de tipo.
     */
    private function buildWhere(bool $ocultar, string $categ, string $busqueda): array
    {
        $conds  = [];
        $params = [];

        if ($ocultar) {
            $conds[] = "(fechabaja IS NULL"
                     . " OR fechabaja = '0000-00-00'"
                     . " OR fechabaja = '0000-00-00 00:00:00'"
                     . " OR fechabaja > CURDATE())";
        }

        if ($categ !== '' && strtoupper($categ) !== 'TODOS') {
            // Filtro explícito por categoría (no TODOS)
            $conds[]          = "TRIM(categ) = :categ";
            $params[':categ'] = $categ;
        } elseif ($categ === '') {
            // Sin filtro: ocultar ADEF por defecto
            $conds[] = "TRIM(categ) != 'ADEF'";
        }
        // categ='TODOS' → sin condición → muestra todo incluyendo ADEF

        if ($busqueda !== '') {
            $val     = '%' . $busqueda . '%';
            $conds[] = "(nombre    LIKE :bus1"
                     . " OR codigo    LIKE :bus2"
                     . " OR cuit      LIKE :bus3"
                     . " OR matricula LIKE :bus4"
                     . " OR nomfantas LIKE :bus5)";
            $params[':bus1'] = $val;
            $params[':bus2'] = $val;
            $params[':bus3'] = $val;
            $params[':bus4'] = $val;
            $params[':bus5'] = $val;
        }

        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  HELPERS DE PRESENTACIÓN (estáticos)
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Clase Bootstrap para colorear la fila según el estado del prestador.
     *
     * Prioridad:
     *   1. Dado de baja (`fechabaja` pasada) → table-warning (amarillo)
     *   2. Sin OS (`trabajaos = '0'`) o sin sucursal (`tienesuc = '0'`) → table-danger (rojo)
     *
     * NOTA: PDO devuelve las claves en minúsculas tal como están en la BD,
     *       por eso usamos $row['fechabaja'], $row['trabajaos'], etc.
     */
    public static function claseFilaTabla(array $row): string
    {
        // ── Dado de baja: fechabaja pasada ────────────────────────────────
        $fb = trim($row['fechabaja'] ?? '');
        $esInactivo = ($fb !== '' && $fb !== '0000-00-00' && $fb !== '0000-00-00 00:00:00');
        if ($esInactivo) {
            $ts = strtotime($fb);
            if ($ts !== false && $ts < time()) {
                return 'tr-baja';
            }
        }

        // ── Flags de estado (prioridad según gravedad) ────────────────────
        $tienesuc   = trim($row['tienesuc']   ?? '1');
        $trabajaos  = trim($row['trabajaos']  ?? '1');
        $tieneprest = trim($row['tieneprest'] ?? '1');
        $tienesucpr = trim($row['tienesucpr'] ?? '1');

        // Orden: sin sucursal > sin OS > sin prestaciones > sin práctica en suc
        if ($tienesuc   === '0') return 'tr-sin-suc';
        if ($trabajaos  === '0') return 'tr-sin-os';
        if ($tieneprest === '0') return 'tr-sin-prest';
        if ($tienesucpr === '0') return 'tr-sin-suc-prac';

        return '';
    }

    /**
     * Badge Bootstrap para la categoría del prestador.
     */
    public static function badgeCategoria(string $categ): string
    {
        static $mapa = [
            'CP'          => ['success',   'Clínica Privada'],
            'SAN'         => ['info',      'Sanatorio'],
            'FARM'        => ['warning',   'Farmacia'],
            'LAB'         => ['secondary', 'Laboratorio'],
            'CLI'         => ['primary',   'Clínica'],
            'ODO'         => ['danger',    'Odontología'],
            'POLICONS'    => ['dark',      'Policonsultorio'],
            'SALME'       => ['success',   'Salud Mental'],
            'INT DOMICILI'=> ['info',      'Int. Domiciliaria'],
            'AMBULANCIA'  => ['danger',    'Ambulancia'],
        ];

        $key = strtoupper(trim($categ));
        [$color, $label] = $mapa[$key] ?? ['secondary', $categ];

        return '<span class="badge bg-' . $color . ' bg-opacity-75" style="font-size:0.68rem;">'
             . htmlspecialchars($label) . '</span>';
    }
}
