<?php
namespace App\Service;

use App\Model\Message;
use App\Database\Database;
use PDO;

/**
 * Class MessageService
 * Handles business logic for messages.
 */
class MessageService
{
    private PDO $pdo;

    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * Send a message
     * @param int $from_id
     * @param int $to_id
     * @param string $message
     * @return bool
     */
    public function send(int $from_id, int $to_id, string $message): bool
    {
        $stmt = $this->pdo->prepare('INSERT INTO messages (from_id, to_id, message) VALUES (?, ?, ?)');
        return $stmt->execute([$from_id, $to_id, $message]);
    }

    /**
     * Fetch chat history between two users
     * @param int $from_id
     * @param int $to_id
     * @return Message[]
     */
    public function fetchHistory(int $from_id, int $to_id): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?) ORDER BY created_at ASC');
        $stmt->execute([$from_id, $to_id, $to_id, $from_id]);
        $messages = [];
        foreach ($stmt->fetchAll() as $row) {
            $messages[] = new Message((int)$row['id'], (int)$row['from_id'], (int)$row['to_id'], $row['message'], $row['created_at']);
        }
        return $messages;
    }
}
