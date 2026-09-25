<?php
/**
 * classes/MailService.php
 * Envío centralizado de correos por plantilla + log en t_log_emails.
 * No lanza excepciones ni interrumpe al caller.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

class MailService
{
    /** @var PDO */
    private $db;

    /** @var string */
    private $ultimoErrorDb = '';

    public function __construct(?PDO $db = null)
    {
        $this->db = $db instanceof PDO ? $db : Database::getConnection();
    }

    /**
     * Envía un mail a partir de una plantilla activa y registra el resultado.
     *
     * @param string               $codigo_plantilla  Ej. ALERTA_FACTURA_ALTA
     * @param array<string,string> $datos_reemplazo   Ej. ['{tope}' => '1000000']
     * @param string               $modulo_origen     Módulo que dispara el envío
     */
    public function enviarCorreoTemplate($codigo_plantilla, $datos_reemplazo, $modulo_origen): bool
    {
        $codigo_plantilla = trim((string) $codigo_plantilla);
        $modulo_origen    = trim((string) $modulo_origen);
        $datos_reemplazo  = is_array($datos_reemplazo) ? $datos_reemplazo : [];

        $destinatariosLog = '';
        $asuntoLog        = '';

        try {
            $plantilla = $this->obtenerPlantillaActiva($codigo_plantilla);
            if ($plantilla === null) {
                $msg = 'MAIL ERROR: No se encontró la plantilla ' . $codigo_plantilla
                    . ' o falló la SQL. Error DB: ' . $this->errorDb();
                $this->trazar($msg);
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    '',
                    '',
                    'ERROR',
                    $msg
                );
                return false;
            }

            $smtp = $this->obtenerConfigSmtp();
            if ($smtp === null) {
                $msg = 'MAIL ERROR: No se encontró t_config_smtp ID=1 o falló la SQL. Error DB: ' . $this->errorDb();
                $this->trazar($msg);
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    '',
                    '',
                    'ERROR',
                    $msg
                );
                return false;
            }

            $asunto = $this->aplicarReemplazos((string) ($plantilla['asunto'] ?? ''), $datos_reemplazo);
            $cuerpo = $this->aplicarReemplazos((string) ($plantilla['cuerpo'] ?? ''), $datos_reemplazo);
            $asuntoLog = $asunto;

            $to  = $this->separarEmails($plantilla['destinatarios'] ?? '');
            $cc  = $this->separarEmails($plantilla['cc'] ?? '');
            $cco = $this->separarEmails($plantilla['cco'] ?? '');
            $destinatariosLog = implode(', ', array_merge($to, $cc, $cco));

            if ($to === []) {
                $msg = 'MAIL ERROR: La plantilla ' . $codigo_plantilla
                    . ' no tiene destinatarios válidos. Crudo: '
                    . (string) ($plantilla['destinatarios'] ?? '');
                $this->trazar($msg);
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    $destinatariosLog,
                    $asuntoLog,
                    'ERROR',
                    $msg
                );
                return false;
            }

            if (!$this->cargarPhpMailer()) {
                $msg = 'MAIL ERROR: PHPMailer no está disponible (lib/PHPMailer o vendor).';
                $this->trazar($msg);
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    $destinatariosLog,
                    $asuntoLog,
                    'ERROR',
                    $msg
                );
                return false;
            }

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isSMTP();
            $mail->Host       = $smtp['host'];
            $mail->Port       = $smtp['puerto'];
            $mail->SMTPAuth   = ($smtp['usuario'] !== '');
            $mail->Username   = $smtp['usuario'];
            $mail->Password   = $smtp['password'];
            $mail->SMTPSecure = $this->seguridadPorPuerto($smtp['puerto']);

            $de = $smtp['remitente_email'] !== '' ? $smtp['remitente_email'] : $smtp['usuario'];
            $mail->setFrom($de, $smtp['remitente_nombre']);

            foreach ($to as $email) {
                $mail->addAddress($email);
            }
            foreach ($cc as $email) {
                $mail->addCC($email);
            }
            foreach ($cco as $email) {
                $mail->addBCC($email);
            }

            $mail->isHTML($this->pareceHtml($cuerpo));
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpo;
            if ($mail->ContentType === 'text/html') {
                $mail->AltBody = trim(html_entity_decode(strip_tags($cuerpo), ENT_QUOTES, 'UTF-8'));
            }

            $mail->send();

            $this->registrarLog(
                $modulo_origen,
                $codigo_plantilla,
                $destinatariosLog,
                $asuntoLog,
                'ENVIADO',
                ''
            );
            return true;
        } catch (\Throwable $e) {
            $msg = 'MAIL ERROR: excepción en enviarCorreoTemplate(' . $codigo_plantilla . '): '
                . $e->getMessage();
            $this->trazar($msg);
            $this->registrarLog(
                $modulo_origen,
                $codigo_plantilla,
                $destinatariosLog,
                $asuntoLog,
                'ERROR',
                $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Busca por código con SELECT * (sin filtrar activo/estado en SQL).
     * Si existe columna activo/estado y está apagada, se descarta con log.
     *
     * @return array<string, mixed>|null
     */
    private function obtenerPlantillaActiva(string $codigo)
    {
        $this->ultimoErrorDb = '';
        if ($codigo === '') {
            $this->ultimoErrorDb = 'código de plantilla vacío';
            return null;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT * FROM t_plantillas_emails WHERE TRIM(codigo) = :codigo LIMIT 1'
            );
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->ultimoErrorDb = 'la consulta no devolvió filas para codigo=' . $codigo;
                return null;
            }

            $plantilla = $this->normalizarPlantilla($row);
            if ($this->plantillaMarcadaInactiva($row)) {
                $flag = $this->campo($row, array('activo', 'estado', 'activa'), '');
                $this->ultimoErrorDb = 'plantilla encontrada pero inactiva (activo/estado=' . $flag . ')';
                $this->trazar('MAIL ERROR: ' . $this->ultimoErrorDb . ' codigo=' . $codigo);
                return null;
            }
            return $plantilla;
        } catch (\Throwable $e) {
            $this->ultimoErrorDb = $this->errorDb($e);
            $this->trazar('MAIL ERROR: SQL t_plantillas_emails falló. Error DB: ' . $this->ultimoErrorDb);
            return null;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizarPlantilla(array $row)
    {
        return array(
            'codigo'        => $this->campo($row, array('codigo', 'codigo_plantilla')),
            'asunto'        => $this->campo($row, array('asunto', 'subject')),
            'cuerpo'        => $this->campo($row, array('cuerpo', 'cuerpo_html', 'mensaje', 'body')),
            'destinatarios' => $this->campo($row, array('destinatarios', 'destinatario', 'para', 'email_to')),
            'cc'            => $this->campo($row, array('cc', 'copia')),
            'cco'           => $this->campo($row, array('cco', 'bcc', 'cco_bcc')),
        );
    }

    /**
     * Solo si la tabla trae activo/estado. Si no existe la columna, se considera usable.
     *
     * @param array<string, mixed> $row
     */
    private function plantillaMarcadaInactiva(array $row): bool
    {
        if (!array_key_exists('activo', $row) && !array_key_exists('estado', $row) && !array_key_exists('activa', $row)) {
            return false;
        }
        $flag = $this->campo($row, array('activo', 'estado', 'activa'), '');
        if ($flag === '') {
            return false;
        }
        $up = strtoupper($flag);
        return ($up === 'N' || $up === 'NO' || $up === '0' || $up === 'INACTIVO' || $up === 'FALSE');
    }

    /**
     * @return array{host:string,puerto:int,usuario:string,password:string,remitente_email:string,remitente_nombre:string}|null
     */
    private function obtenerConfigSmtp()
    {
        $this->ultimoErrorDb = '';
        try {
            $stmt = $this->db->prepare(
                'SELECT host, puerto, usuario, `password`, remitente_email, remitente_nombre
                 FROM t_config_smtp
                 WHERE id = 1
                 LIMIT 1'
            );
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->ultimoErrorDb = 't_config_smtp no tiene fila id=1';
                return null;
            }
            return [
                'host'             => trim((string) ($row['host'] ?? '')),
                'puerto'           => (int) ($row['puerto'] ?? 587),
                'usuario'          => trim((string) ($row['usuario'] ?? '')),
                'password'         => (string) ($row['password'] ?? ''),
                'remitente_email'  => trim((string) ($row['remitente_email'] ?? '')),
                'remitente_nombre' => trim((string) ($row['remitente_nombre'] ?? 'COMEDICA')),
            ];
        } catch (\Throwable $e) {
            $this->ultimoErrorDb = $this->errorDb($e);
            $this->trazar('MAIL ERROR: SQL t_config_smtp falló. Error DB: ' . $this->ultimoErrorDb);
            return null;
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param string[]             $claves
     */
    private function campo(array $row, array $claves, string $default = ''): string
    {
        foreach ($claves as $clave) {
            if (array_key_exists($clave, $row) && $row[$clave] !== null && $row[$clave] !== '') {
                return trim((string) $row[$clave]);
            }
        }
        return $default;
    }

    private function errorDb(?\Throwable $e = null): string
    {
        $partes = array();
        if ($this->ultimoErrorDb !== '') {
            $partes[] = $this->ultimoErrorDb;
        }
        if ($e instanceof \Throwable) {
            $partes[] = $e->getMessage();
        }
        $info = $this->db->errorInfo();
        if (!empty($info[2])) {
            $partes[] = $info[2];
        } elseif (!empty($info[0]) && $info[0] !== '00000') {
            $partes[] = 'SQLSTATE ' . $info[0];
        }
        return $partes ? implode(' | ', $partes) : 'sin detalle';
    }

    private function trazar(string $msg): void
    {
        error_log($msg);
        @file_put_contents(dirname(__DIR__) . '/debug_mail.txt', $msg . "\n", FILE_APPEND);
    }

    /**
     * t_config_smtp no tiene campo seguridad: 465 = SSL implícito, 587 = STARTTLS.
     */
    private function seguridadPorPuerto(int $puerto): string
    {
        if ($puerto === 465) {
            return 'ssl';
        }
        return 'tls';
    }

    /**
     * @param array<string, string> $datos
     */
    private function aplicarReemplazos(string $texto, array $datos): string
    {
        foreach ($datos as $comodín => $valor) {
            $texto = str_replace((string) $comodín, (string) $valor, $texto);
        }
        return $texto;
    }

    /**
     * @return string[]
     */
    private function separarEmails($lista): array
    {
        $lista = trim((string) $lista);
        if ($lista === '') {
            return [];
        }
        $partes = preg_split('/[,;]+/', $lista) ?: [];
        $out = [];
        foreach ($partes as $email) {
            $email = trim($email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $email;
            }
        }
        return array_values(array_unique($out));
    }

    private function pareceHtml(string $cuerpo): bool
    {
        return (bool) preg_match('/<[a-z][\s\S]*>/i', $cuerpo);
    }

    private function cargarPhpMailer(): bool
    {
        if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            return true;
        }
        $root = dirname(__DIR__);
        $autoload = $root . '/vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
        }
        $src = $root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . 'src';
        if (is_file($src . DIRECTORY_SEPARATOR . 'PHPMailer.php')) {
            require_once $src . DIRECTORY_SEPARATOR . 'Exception.php';
            require_once $src . DIRECTORY_SEPARATOR . 'PHPMailer.php';
            require_once $src . DIRECTORY_SEPARATOR . 'SMTP.php';
        }
        return class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
    }

    private function registrarLog(
        string $modulo,
        string $codigoPlantilla,
        string $destinatarios,
        string $asunto,
        string $estado,
        string $mensajeServidor
    ): void {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO t_log_emails
                    (fecha, modulo, codigo_plantilla, destinatarios, asunto, estado, mensaje_servidor)
                 VALUES
                    (:fecha, :modulo, :codigo_plantilla, :destinatarios, :asunto, :estado, :mensaje_servidor)'
            );
            $stmt->execute([
                ':fecha'            => date('Y-m-d H:i:s'),
                ':modulo'           => substr($modulo, 0, 80),
                ':codigo_plantilla' => substr($codigoPlantilla, 0, 80),
                ':destinatarios'    => $destinatarios,
                ':asunto'           => $asunto,
                ':estado'           => $estado === 'ENVIADO' ? 'ENVIADO' : 'ERROR',
                ':mensaje_servidor' => $mensajeServidor,
            ]);
        } catch (\Throwable $e) {
            error_log('MailService::registrarLog — ' . $e->getMessage());
        }
    }
}
