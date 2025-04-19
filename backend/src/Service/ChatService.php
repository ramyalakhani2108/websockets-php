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
}
