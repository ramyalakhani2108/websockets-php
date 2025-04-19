<?php
// CORS headers
// Dynamic CORS for dev
// Universal CORS for dev (echo back Origin if present, allow credentials)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// users.php: Handles user-related actions (register, login, fetch users)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Model/User.php';
require_once __DIR__ . '/../src/Service/UserService.php';
require_once __DIR__ . '/../src/Controller/UserController.php';

use App\Database\Database;
use App\Service\UserService;
use App\Controller\UserController;

header('Content-Type: application/json');

// Dependency injection
$db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
$userService = new UserService($db);
$controller = new UserController($userService);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        $action = $input['action'] ?? '';
        if ($action === 'register') {
            $controller->register($input);
        } elseif ($action === 'login') {
            $controller->login($input);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'GET':
        $controller->fetchUsers();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
}
