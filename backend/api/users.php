<?php
// users.php: Handles user-related actions (register, login, fetch users)

require_once '../db.php';
require_once '../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $action = isset($input['action']) ? $input['action'] : '';
        $pdo = getDbConnection();
        if ($action === 'register') {
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            if (!$username || !$password) {
                http_response_code(400);
                echo json_encode(['error' => 'Username and password required']);
                exit;
            }
            // Check if username exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Username already exists']);
                exit;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $id = $pdo->lastInsertId();
            echo json_encode(['id' => $id, 'username' => $username]);
        } elseif ($action === 'login') {
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            if (!$username || !$password) {
                http_response_code(400);
                echo json_encode(['error' => 'Username and password required']);
                exit;
            }
            $stmt = $pdo->prepare('SELECT id, password FROM users WHERE username = ?');
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password'])) {
                echo json_encode(['id' => $user['id'], 'username' => $username]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'GET':
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT id, username FROM users ORDER BY username');
        $users = $stmt->fetchAll();
        echo json_encode($users);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
}
