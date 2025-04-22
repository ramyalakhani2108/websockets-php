<?php
namespace App\Service;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Class ChatService
 * Handles chat logic for the WebSocket server.
 */
class ChatService
{
    /**
     * Handle login event
     * @param TcpConnection $connection
     * @param array $msg
     */
    public function handleLogin(TcpConnection $connection, array $msg): void
    {
        if (empty($msg['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'error' => 'Missing user_id.'
            ]));
            return;
        }
        $connection->user_id = $msg['user_id'];
        $connection->send(json_encode([
            'type' => 'login',
            'status' => 'ok'
        ]));
    }

    /**
     * Handle chat event
     * @param TcpConnection $connection
     * @param array $msg
     * @param Worker $ws_worker
     */
    public function handleChat(TcpConnection $connection, array $msg, Worker $ws_worker): void
    {
        if (empty($connection->user_id) || empty($msg['to_id']) || !isset($msg['message'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'error' => 'Invalid chat payload.'
            ]));
            return;
        }
        $payload = [
            'type' => 'chat',
            'to_id' => $msg['to_id'],
            'message' => $msg['message'],
            'time' => $msg['time'] ?? date('H:i')
        ];
        foreach ($ws_worker->connections as $client) {
            if (isset($client->user_id) && ($client->user_id == $msg['to_id'] || $client->user_id == $connection->user_id)) {
                $client->send(json_encode($payload));
            }
        }
    }

    /**
     * Handle group chat event
     * @param TcpConnection $connection
     * @param array $msg
     * @param Worker $ws_worker
     */
    public function handleGroupChat(TcpConnection $connection, array $msg, Worker $ws_worker): void
    {
        if (empty($connection->user_id) || empty($msg['group_id']) || !isset($msg['message']) || empty($msg['from_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'error' => 'Invalid group chat payload.'
            ]));
            return;
        }
        $payload = [
            'type' => 'group_chat',
            'group_id' => $msg['group_id'],
            'from_id' => $msg['from_id'],
            'message' => $msg['message'],
            'time' => $msg['time'] ?? date('H:i')
        ];
        // Save to DB
        $this->saveGroupMessage($msg['group_id'], $connection->user_id, $msg['message']);
        // Fetch group members
        $members = $this->getGroupMembers($msg['group_id']);
        $memberIds = array_column($members, 'user_id');
        // Debug: Log all member IDs and all connected user_ids
        file_put_contents('/tmp/ws_debug.log', "[BROADCAST] group_id={$msg['group_id']} memberIds=" . json_encode($memberIds) . "\n", FILE_APPEND);
        foreach ($ws_worker->connections as $client) {
            file_put_contents('/tmp/ws_debug.log', "[CLIENT] user_id=" . ($client->user_id ?? 'null') . "\n", FILE_APPEND);
            if (isset($client->user_id) && in_array($client->user_id, $memberIds)) {
                $client->send(json_encode($payload));
            }
        }
    }

    private function saveGroupMessage($group_id, $from_id, $message) {
        $pdo = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
        $stmt = $pdo->prepare('INSERT INTO group_messages (group_id, from_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$group_id, $from_id, $message]);
    }
    private function getGroupMembers($group_id) {
        $pdo = new \PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
        $stmt = $pdo->prepare('SELECT user_id FROM group_members WHERE group_id = ?');
        $stmt->execute([$group_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
