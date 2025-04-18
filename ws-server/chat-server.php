<?php
use Workerman\Worker;
require_once __DIR__ . '/../vendor/autoload.php';

$ws_worker = new Worker("websocket://0.0.0.0:2346");
$ws_worker->count = 4;

// Log new connections
$ws_worker->onConnect = function($connection) {
    echo "[WS] New connection from: " . $connection->getRemoteIp() . "\n";
};

// Log closed connections
$ws_worker->onClose = function($connection) {
    echo "[WS] Connection closed: " . (isset($connection->user_id) ? $connection->user_id : 'unknown') . "\n";
};

$ws_worker->onMessage = function($connection, $data) use ($ws_worker) {
    echo "[WS] Received from " . $connection->getRemoteIp() . ": $data\n";
    $msg = json_decode($data, true);
    if (!$msg) {
        echo "[WS] Invalid JSON received\n";
        return;
    }
    if (isset($msg['type']) && $msg['type'] === 'login') {
        // Client sends: {type: 'login', user_id: ...}
        $connection->user_id = $msg['user_id'];
        echo "[WS] Login from user_id: {$msg['user_id']}\n";
        return;
    }
    if (isset($msg['type']) && $msg['type'] === 'chat') {
        echo "[WS] Chat from {$msg['from_id']} to {$msg['to_id']} | Message: {$msg['message']}\n";
        // Send to recipient (if online)
        $sent = 0;
        foreach ($ws_worker->connections as $client) {
            if (isset($client->user_id) && ($client->user_id == $msg['to_id'] || $client->user_id == $msg['from_id'])) {
                $client->send(json_encode([
                    'type' => 'chat',
                    'from_id' => $msg['from_id'],
                    'to_id' => $msg['to_id'],
                    'message' => $msg['message'],
                    'time' => $msg['time']
                ]));
                echo "[WS] Sent to client user_id: {$client->user_id}\n";
                $sent++;
            }
        }
        if ($sent === 0) {
            echo "[WS] No recipient found for user_id: {$msg['to_id']}\n";
        }
    }
};

Worker::runAll();
