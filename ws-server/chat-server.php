<?php
use Workerman\Worker;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../backend/src/Service/ChatService.php';

use App\Service\ChatService;
use Workerman\Connection\TcpConnection;

/**
 * Class ChatServer
 * OOP WebSocket server with dependency-injected ChatService.
 */
class ChatServer
{
    private Worker $ws_worker;
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
        $this->ws_worker = new Worker("websocket://0.0.0.0:2346");
        $this->ws_worker->count = 4;
        $this->ws_worker->onConnect = [$this, 'onConnect'];
        $this->ws_worker->onClose = [$this, 'onClose'];
        $this->ws_worker->onMessage = function($connection, $data) {
            $this->onMessage($connection, $data);
        };
    }

    /**
     * @param TcpConnection $connection
     */
    public function onConnect($connection): void
    {
        // (Logging removed)
    }

    /**
     * @param TcpConnection $connection
     */
    public function onClose($connection): void
    {
        // No logging in production
        // Clean up if needed (e.g., remove user from memory, notify others)
    }

    /**
     * @param TcpConnection $connection
     * @param string $data
     */
    public function onMessage($connection, $data): void
    {
        file_put_contents('/tmp/ws_debug.log', "[IN] " . date('H:i:s') . " " . ($connection->user_id ?? '-') . ": $data\n", FILE_APPEND);
        $msg = json_decode($data, true);
        if (!is_array($msg)) {
            $connection->send(json_encode([
                'type' => 'error',
                'error' => 'Malformed JSON.'
            ]));
            return;
        }
        if (!isset($msg['type'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'error' => 'Missing message type.'
            ]));
            return;
        }
        switch ($msg['type']) {
            case 'login':
                $this->chatService->handleLogin($connection, $msg);
                break;
            case 'chat':
                $this->chatService->handleChat($connection, $msg, $this->ws_worker);
                break;
            default:
                $connection->send(json_encode([
                    'type' => 'error',
                    'error' => 'Unknown message type.'
                ]));
                break;
        }
    }

    public function run(): void
    {
        Worker::runAll();
    }
}

// Dependency injection
$chatService = new ChatService();
$server = new ChatServer($chatService);
$server->run();