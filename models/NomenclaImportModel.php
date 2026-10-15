<?php
/**
 * models/NomenclaImportModel.php
 * Importación de prácticas al nomenclador y, si corresponde, al prestador y sus sucursales.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class NomenclaImportModel
{
    /** @var PDO */
    private $db;

    /** @var array<string, array<string, bool>> */
    private $columnas = array();

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * @return array{codigo:string,nombre:string}|null
     */
    public function validarPrestador($cod)
    {
        $cod = trim((string) $cod);
        if ($cod === '') {
            return null;
        }

        $ors = array('TRIM(CODIGO) = :cod', 'TRIM(MATRICULA) = :mat');
        $params = array(':cod' => $cod, ':mat' => $cod);
        foreach (array('codpresta', 'codprest') as $col) {
            if ($this->tieneColumna('ebamp', $col)) {
                $ph = ':' . $col;
                $ors[] = 'TRIM(`' . $col . '`) = ' . $ph;
                $params[$ph] = $cod;
            }
        }

        $sql = "SELECT TRIM(CODIGO) AS codigo, TRIM(NOMBRE) AS nombre
                FROM ebamp
                WHERE " . implode(' OR ', $ors) . "
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return array(
            'codigo' => (string) $row['codigo'],
            'nombre' => (string) $row['nombre'],
        );
    }

    /**
     * Vigencia inicial: último PEDESDE del prestador, o la fecha de hoy.
     */
    public function getFechaDesdeReciente($cod)
    {
        $sql = "SELECT MAX(pedesde) FROM pracespe WHERE TRIM(pecodigo) = :cod";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array(':cod' => trim((string) $cod)));
        $fecha = $stmt->fetchColumn();
        if (!$fecha) {
            $fecha = $this->db->query('SELECT CURDATE()')->fetchColumn();
        }
        return substr((string) $fecha, 0, 10);
    }

    /**
     * @param array<string, mixed> $fila
     * @return array{accion:string,codigo:string}
     */
    public function procesarNomencla(array $fila, $usuario)
    {
        $codigo = $this->codigo($fila['codigo'] ?? '');
        $nombre = $this->texto($fila['nombre'] ?? '', 100);
        if ($codigo === '' || $nombre === '') {
            throw new InvalidArgumentException('Cada práctica necesita código y nombre.');
        }

        $existente = $this->buscarNomencla($codigo, $nombre);
        if ($existente !== null) {
            return array('accion' => 'existe', 'codigo' => $existente);
        }

        $stmt = $this->db->prepare(
            "INSERT INTO nomencla
                (tacodigo, tadescrip, taimporte, tagrupo, valnbu, subgrupo, capitulo,
                 nomenclada, complejida, grupocar, gruposup, tatipo, user, fechaup, timeup, feccreate)
             VALUES
                (:cod, :nom, :imp, :grp, :nbu, :sub, :cap,
                 :nomcl, :comp, :gcar, :gsup, 'PRA', :usr, NOW(), :hora, NOW())"
        );
        $stmt->execute(array(
            ':cod'   => $codigo,
            ':nom'   => $nombre,
            ':imp'   => $this->decimal($fila['importe'] ?? 0),
            ':grp'   => $this->texto($fila['grupo'] ?? '', 8),
            ':nbu'   => $this->decimal($fila['valnbu'] ?? 0),
            ':sub'   => $this->texto($fila['subgrupo'] ?? '', 8),
            ':cap'   => $this->texto($fila['capitulo'] ?? '', 2),
            ':nomcl' => $this->texto($fila['nomenclada'] ?? '', 2),
            ':comp'  => $this->texto($fila['complejida'] ?? '', 5),
            ':gcar'  => $this->texto($fila['grupocar'] ?? '', 8),
            ':gsup'  => $this->texto($fila['gruposup'] ?? '', 9),
            ':usr'   => substr(trim((string) $usuario), 0, 10),
            ':hora'  => date('H:i:s'),
        ));

        return array('accion' => 'insert', 'codigo' => $codigo);
    }

    /**
     * Inserta en pracespe solo si el prestador todavía no tiene esa práctica.
     *
     * @param array<string, mixed> $fila
     */
    public function procesarPracespe(array $fila, $prestador, $usuario, $desde)
    {
        $prestador = $this->texto($prestador, 8);
        $codigo = $this->codigo($fila['codigo'] ?? '');
        if ($prestador === '' || $codigo === '') {
            return 'omitido';
        }
        if ($this->existePracespe($prestador, $codigo)) {
            return 'existe';
        }

        $datos = array(
            'pecodigo'    => $prestador,
            'pepractica'  => $codigo,
            'penombprac'  => $this->texto($fila['nombre'] ?? '', 80),
            'peimporte'   => $this->decimal($fila['importe'] ?? 0),
            'pegrupo'     => $this->texto($fila['grupo'] ?? '', 8),
            'pedesde'     => $desde,
            'pehasta'     => '2098-12-31',
            'pefecha'     => date('Y-m-d H:i:s'),
            'peusuario'   => substr(trim((string) $usuario), 0, 4),
            'fecmodif'    => date('Y-m-d H:i:s'),
            'peuser'      => substr(trim((string) $usuario), 0, 10),
            'pefechamod'  => date('Y-m-d H:i:s'),
            'peusumod'    => substr(trim((string) $usuario), 0, 10),
        );
        $this->insertar('pracespe', $datos);
        return 'insert';
    }

    /**
     * @return array<int, array{codigo:string,nombre:string,direccion:string}>
     */
    public function listarSucursales($prestador)
    {
        $stmt = $this->db->prepare(
            "SELECT TRIM(sucodigo) AS codigo,
                    TRIM(sunombre) AS nombre,
                    TRIM(sudir) AS direccion
             FROM sucprest
             WHERE TRIM(suprestado) = :cod
             ORDER BY sucodigo"
        );
        $stmt->execute(array(':cod' => trim((string) $prestador)));
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            if (trim((string) $row['codigo']) === '') {
                continue;
            }
            $out[] = array(
                'codigo'    => (string) $row['codigo'],
                'nombre'    => (string) $row['nombre'],
                'direccion' => (string) $row['direccion'],
            );
        }
        return $out;
    }

    /**
     * @param string[] $sucursales
     * @param array<int, array<string, mixed>> $practicas
     */
    public function procesarSucursales($prestador, array $sucursales, array $practicas, $usuario, $desde)
    {
        $insertadas = 0;
        $prestador = $this->texto($prestador, 8);
        foreach ($sucursales as $sucursal) {
            $sucursal = trim((string) $sucursal);
            if ($sucursal === '' || !$this->sucursalDelPrestador($prestador, $sucursal)) {
                continue;
            }
            foreach ($practicas as $fila) {
                $codigo = $this->codigo($fila['codigo'] ?? '');
                if ($codigo === '' || $this->existePraespsu($prestador, $sucursal, $codigo)) {
                    continue;
                }
                $this->insertar('praespsu', array(
                    'peprestado' => $prestador,
                    'pecodigo'   => $this->texto($sucursal, 11),
                    'pepractica' => $codigo,
                    'penombprac' => $this->texto($fila['nombre'] ?? '', 60),
                    'peimporte'  => $this->decimal($fila['importe'] ?? 0),
                    'pegrupo'    => $this->texto($fila['grupo'] ?? '', 8),
                    'pedesde'    => $desde,
                    'pehasta'    => '2098-12-31',
                    'pefecha'    => date('Y-m-d H:i:s'),
                    'peusuario'  => substr(trim((string) $usuario), 0, 4),
                ));
                $insertadas++;
            }
        }
        return $insertadas;
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<string, mixed>
     */
    public function procesarImportacion(array $filas, $prestadorForm, $cargarPrestador, $usuario, $userId)
    {
        $nomenclaIns = 0;
        $pracespeIns = 0;
        $errores = array();
        $practicasPrestador = array();
        $prestadorUsado = '';

        $this->db->beginTransaction();
        try {
            foreach ($filas as $i => $fila) {
                try {
                    $res = $this->procesarNomencla($fila, $usuario);
                    if ($res['accion'] === 'insert') {
                        $nomenclaIns++;
                    }
                    $fila['codigo'] = $res['codigo'];
                } catch (InvalidArgumentException $e) {
                    $errores[] = 'Fila ' . ($i + 2) . ': ' . $e->getMessage();
                    continue;
                }

                if (!$cargarPrestador) {
                    continue;
                }

                $prestador = trim((string) ($fila['prestador'] ?? ''));
                if ($prestador === '') {
                    $prestador = trim((string) $prestadorForm);
                }
                if ($prestador === '') {
                    $errores[] = 'Fila ' . ($i + 2) . ': falta el código de prestador.';
                    continue;
                }
                $encontrado = $this->validarPrestador($prestador);
                if ($encontrado === null) {
                    $errores[] = 'Fila ' . ($i + 2) . ': el prestador ' . $prestador . ' no existe.';
                    continue;
                }
                $prestador = $encontrado['codigo'];

                $desde = $this->getFechaDesdeReciente($prestador);
                $accionPe = $this->procesarPracespe($fila, $prestador, $usuario, $desde);
                if ($accionPe === 'insert') {
                    $pracespeIns++;
                }
                if ($prestadorUsado === '') {
                    $prestadorUsado = $prestador;
                } elseif ($prestadorUsado !== $prestador) {
                    $prestadorUsado = '*';
                }
                $practicasPrestador[$prestador][] = $fila;
            }

            $sucursalesRequeridas = false;
            $sucursalesLista = array();
            $pendiente = null;

            if ($cargarPrestador && $prestadorUsado !== '' && $prestadorUsado !== '*') {
                $sucursales = $this->listarSucursales($prestadorUsado);
                $practicas = isset($practicasPrestador[$prestadorUsado]) ? $practicasPrestador[$prestadorUsado] : array();
                if (count($sucursales) === 1) {
                    $this->procesarSucursales(
                        $prestadorUsado,
                        array($sucursales[0]['codigo']),
                        $practicas,
                        $usuario,
                        $this->getFechaDesdeReciente($prestadorUsado)
                    );
                } elseif (count($sucursales) > 1) {
                    $sucursalesRequeridas = true;
                    $sucursalesLista = $sucursales;
                    $pendiente = array(
                        'prestador'  => $prestadorUsado,
                        'practicas'  => $practicas,
                        'usuario'    => $usuario,
                        'user_id'    => (int) $userId,
                    );
                }
            }

            $this->registrarAuditoria(
                (int) $userId,
                (string) $usuario,
                'IMPORT_NOMENCLA',
                'Importacion nomencla=' . $nomenclaIns . ' pracespe=' . $pracespeIns
            );
            $this->db->commit();

            return array(
                'ok' => true,
                'nomencla_insertados' => $nomenclaIns,
                'pracespe_insertados' => $pracespeIns,
                'sucursales_requeridas' => $sucursalesRequeridas,
                'sucursales_lista' => $sucursalesLista,
                'pendiente' => $pendiente,
                'errores' => array_slice($errores, 0, 20),
                'msg' => 'Proceso de importación completado.',
            );
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param string[] $sucursales
     * @param array<int, array<string, mixed>> $practicas
     */
    public function grabarSucursalesElegidas($prestador, array $sucursales, array $practicas, $usuario, $userId)
    {
        $this->db->beginTransaction();
        try {
            $n = $this->procesarSucursales(
                $prestador,
                $sucursales,
                $practicas,
                $usuario,
                $this->getFechaDesdeReciente($prestador)
            );
            $this->registrarAuditoria(
                (int) $userId,
                (string) $usuario,
                'IMPORT_SUCURSAL',
                'Sucursales grabadas prestador=' . $prestador . ' filas=' . $n
            );
            $this->db->commit();
            return $n;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function registrarOmisionSucursales($prestador, $usuario, $userId)
    {
        $detalle = 'No se grabaron practicas en sucursales. Prestador=' . trim((string) $prestador)
            . '. Observacion pendiente de reporte SMTP.';
        $this->registrarAuditoria((int) $userId, (string) $usuario, 'IMPORT_SMTP_PENDIENTE', $detalle);
    }

    /**
     * @return string|null código existente
     */
    private function buscarNomencla($codigo, $nombre)
    {
        $stmt = $this->db->prepare(
            "SELECT TRIM(tacodigo) AS codigo
             FROM nomencla
             WHERE TRIM(tacodigo) = :cod
                OR UPPER(TRIM(tadescrip)) = UPPER(:nom)
             ORDER BY CASE WHEN TRIM(tacodigo) = :cod2 THEN 0 ELSE 1 END
             LIMIT 1"
        );
        $stmt->execute(array(
            ':cod'  => $codigo,
            ':nom'  => $nombre,
            ':cod2' => $codigo,
        ));
        $cod = $stmt->fetchColumn();
        return $cod ? (string) $cod : null;
    }

    private function existePracespe($prestador, $practica)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM pracespe
             WHERE TRIM(pecodigo) = :cod AND TRIM(pepractica) = :prac"
        );
        $stmt->execute(array(':cod' => $prestador, ':prac' => $practica));
        return (int) $stmt->fetchColumn() > 0;
    }

    private function existePraespsu($prestador, $sucursal, $practica)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM praespsu
             WHERE TRIM(peprestado) = :pre
               AND TRIM(pecodigo) = :suc
               AND TRIM(pepractica) = :prac"
        );
        $stmt->execute(array(':pre' => $prestador, ':suc' => $sucursal, ':prac' => $practica));
        return (int) $stmt->fetchColumn() > 0;
    }

    private function sucursalDelPrestador($prestador, $sucursal)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM sucprest
             WHERE TRIM(suprestado) = :pre AND TRIM(sucodigo) = :suc"
        );
        $stmt->execute(array(':pre' => $prestador, ':suc' => $sucursal));
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function insertar($tabla, array $datos)
    {
        $cols = array();
        $vals = array();
        $params = array();
        foreach ($datos as $col => $valor) {
            if (!$this->tieneColumna($tabla, $col)) {
                continue;
            }
            $cols[] = $col;
            $vals[] = ':' . $col;
            $params[':' . $col] = $valor;
        }
        if (!$cols) {
            throw new RuntimeException('La tabla ' . $tabla . ' no tiene columnas compatibles.');
        }
        $sql = 'INSERT INTO ' . $tabla . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ')';
        $this->db->prepare($sql)->execute($params);
    }

    private function tieneColumna($tabla, $columna)
    {
        $tabla = strtolower((string) $tabla);
        if (!isset($this->columnas[$tabla])) {
            $this->columnas[$tabla] = array();
            $stmt = $this->db->query('SHOW COLUMNS FROM `' . $tabla . '`');
            foreach ($stmt as $row) {
                $nombre = strtolower((string) ($row['Field'] ?? ''));
                if ($nombre !== '') {
                    $this->columnas[$tabla][$nombre] = true;
                }
            }
        }
        return isset($this->columnas[$tabla][strtolower((string) $columna)]);
    }

    private function registrarAuditoria($userId, $usuario, $modulo, $detalle)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO auditoria_log
                (codigo_prestador, usuario_email, fecha_hora, modulo, accion_detalle)
             VALUES (:cod, :usr, NOW(), :mod, :det)"
        );
        $stmt->execute(array(
            ':cod' => '',
            ':usr' => substr($usuario, 0, 255),
            ':mod' => substr($modulo, 0, 100),
            ':det' => substr($detalle, 0, 255),
        ));
    }

    private function codigo($valor)
    {
        $codigo = strtoupper(trim((string) $valor));
        $codigo = preg_replace('/\s+/', '', $codigo);
        if (!is_string($codigo)) {
            $codigo = '';
        }
        if (strlen($codigo) > 6) {
            throw new InvalidArgumentException('El código ' . $codigo . ' supera 6 caracteres.');
        }
        return $codigo;
    }

    private function texto($valor, $max)
    {
        $texto = trim((string) $valor);
        if (function_exists('mb_strtoupper')) {
            $texto = mb_strtoupper($texto, 'UTF-8');
        } else {
            $texto = strtoupper($texto);
        }
        if (strlen($texto) > $max) {
            $texto = substr($texto, 0, $max);
        }
        return $texto;
    }

    private function decimal($valor)
    {
        $texto = str_replace(',', '.', trim((string) $valor));
        if ($texto === '' || !is_numeric($texto)) {
            return '0.00';
        }
        return number_format((float) $texto, 2, '.', '');
    }
}
