<?php
namespace App\Database;

use PDO;
use PDOException;

/**
 * Class Database
 * Handles PDO connection using dependency injection.
 */
class Database
{
    private string $host;
    private string $db;
    private string $user;
    private string $pass;
    private ?PDO $pdo = null;

    public function __construct(string $host, string $db, string $user, string $pass)
    {
        $this->host = $host;
        $this->db = $db;
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
            try {
                $this->pdo = new PDO($dsn, $this->user, $this->pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed']));
            }
        }
        return $this->pdo;
    }
}
