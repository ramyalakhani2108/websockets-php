<?php
namespace App\Service;

use App\Model\User;
use App\Database\Database;
use PDO;

/**
 * Class UserService
 * Handles business logic for users.
 */
class UserService
{
    private PDO $pdo;

    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * Register a new user
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public function register(string $username, string $password): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return null; // Username exists
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        $stmt->execute([$username, $hash]);
        $id = (int)$this->pdo->lastInsertId();
        return new User($id, $username);
    }

    /**
     * Login user
     * @param string $username
     * @param string $password
     * @return User|null
     */
    public function login(string $username, string $password): ?User
    {
        $stmt = $this->pdo->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            return new User((int)$user['id'], $username);
        }
        return null;
    }

    /**
     * Fetch all users
     * @return User[]
     */
    public function fetchAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, username FROM users ORDER BY username');
        $users = [];
        foreach ($stmt->fetchAll() as $row) {
            $users[] = new User((int)$row['id'], $row['username']);
        }
        return $users;
    }
}
