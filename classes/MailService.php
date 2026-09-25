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
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    '',
                    '',
                    'ERROR',
                    'Plantilla inexistente o inactiva.'
                );
                return false;
            }

            $smtp = $this->obtenerConfigSmtp();
            if ($smtp === null) {
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    '',
                    '',
                    'ERROR',
                    'Configuración SMTP no encontrada (t_config_smtp ID=1).'
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
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    $destinatariosLog,
                    $asuntoLog,
                    'ERROR',
                    'La plantilla no tiene destinatarios.'
                );
                return false;
            }

            if (!$this->cargarPhpMailer()) {
                $this->registrarLog(
                    $modulo_origen,
                    $codigo_plantilla,
                    $destinatariosLog,
                    $asuntoLog,
                    'ERROR',
                    'PHPMailer no está disponible.'
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
     * @return array<string, mixed>|null
     */
    private function obtenerPlantillaActiva(string $codigo)
    {
        if ($codigo === '') {
            return null;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT codigo, asunto, cuerpo, destinatarios, cc, cco, activo
                 FROM t_plantillas_emails
                 WHERE codigo = :codigo
                 LIMIT 1'
            );
            $stmt->execute([':codigo' => $codigo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            $activo = $row['activo'] ?? 0;
            if ((int) $activo !== 1 && strtoupper((string) $activo) !== 'S') {
                return null;
            }
            return $row;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{host:string,puerto:int,usuario:string,password:string,remitente_email:string,remitente_nombre:string}|null
     */
    private function obtenerConfigSmtp()
    {
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
            return null;
        }
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
