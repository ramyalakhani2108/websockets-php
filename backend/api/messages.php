<?php
// messages.php: Handles message actions (send, fetch history)

require_once '../db.php';
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $from_id = $input['from_id'] ?? null;
        $to_id = $input['to_id'] ?? null;
        $message = trim($input['message'] ?? '');
        if (!$from_id || !$to_id || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'from_id, to_id, and message required']);
            exit;
        }
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('INSERT INTO messages (from_id, to_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$from_id, $to_id, $message]);
        echo json_encode(['success' => true]);
        break;
    case 'GET':
        $from_id = $_GET['from_id'] ?? null;
        $to_id = $_GET['to_id'] ?? null;
        if (!$from_id || !$to_id) {
            http_response_code(400);
            echo json_encode(['error' => 'from_id and to_id required']);
            exit;
        }
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT * FROM messages WHERE (from_id = ? AND to_id = ?) OR (from_id = ? AND to_id = ?) ORDER BY created_at ASC');
        $stmt->execute([$from_id, $to_id, $to_id, $from_id]);
        $messages = $stmt->fetchAll();
        // Format time
        foreach ($messages as &$msg) {
            $msg['time'] = date('H:i', strtotime($msg['created_at']));
        }
        echo json_encode($messages);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
}
