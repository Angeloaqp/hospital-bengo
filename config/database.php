<?php
// ================================================
// Hospital Geral do Bengo
// Configuração da ligação à base de dados MySQL
// XAMPP 8.2.12 | PHP 8.2 | MySQL 8.x
// ================================================

define('DB_HOST',    'localhost');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_NAME',    'hospital_bengo');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?PDO $instancia = null;

    public static function ligar(): PDO {
        if (self::$instancia === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            try {
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'erro'     => true,
                    'mensagem' => 'Erro de ligação à base de dados.',
                    'detalhe'  => $e->getMessage()
                ]));
            }
        }
        return self::$instancia;
    }

    // Previne clonagem e deserialização (Singleton)
    private function __clone() {}
    public function __wakeup() {}
}
