<?php
/**
 * models/User.php
 * Manejo de autenticación contra la tabla `users`.
 *
 * Columnas esperadas: USER_ID, USERNAME, PASSWORD, id_rol, FIRST_NAME, LAST_NAME
 */

require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getDBConnection();
    }

    /**
     * Autentica un usuario por USERNAME y PASSWORD.
     *
     * Soporta dos estrategias de password almacenada en BD:
     *  1. Hash moderno PHP → password_verify()
     *  2. Hash antiguo MySQL PASSWORD() → comparación directa con SELECT PASSWORD()
     *
     * @return array|null  Fila del usuario si las credenciales son válidas; null en caso contrario.
     */
    public function authenticate(string $username, string $password): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT USER_ID, USERNAME, PASSWORD, id_rol, FIRST_NAME, LAST_NAME
               FROM users
              WHERE USERNAME = :username
              LIMIT 1"
        );
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        $storedHash = $user['PASSWORD'];

        // ── Estrategia 1: hash moderno de PHP ($2y$, $argon2i$, etc.) ─────────
        if (substr($storedHash, 0, 1) === '$') {
            if (password_verify($password, $storedHash)) {
                unset($user['PASSWORD']); // No exponer el hash en sesión
                return $user;
            }
            return null;
        }

        // ── Estrategia 2: MySQL PASSWORD() → hash hexadecimal de 41 chars ─────
        if (strlen($storedHash) === 41 && $storedHash[0] === '*') {
            $stmt2 = $this->db->prepare("SELECT PASSWORD(:pass) AS mysql_hash");
            $stmt2->execute([':pass' => $password]);
            $row = $stmt2->fetch();
            if ($row && strtoupper($row['mysql_hash']) === strtoupper($storedHash)) {
                unset($user['PASSWORD']);
                return $user;
            }
            return null;
        }

        // ── Estrategia 3: texto plano (entornos legacy) ───────────────────────
        if ($storedHash === $password) {
            unset($user['PASSWORD']);
            return $user;
        }

        // ── Estrategia 4: MD5 ─────────────────────────────────────────────────
        if (strlen($storedHash) === 32 && strtolower($storedHash) === md5($password)) {
            unset($user['PASSWORD']);
            return $user;
        }

        return null;
    }

    /**
     * Devuelve un usuario por su USER_ID.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT USER_ID, USERNAME, id_rol, FIRST_NAME, LAST_NAME
               FROM users
              WHERE USER_ID = :id
              LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
