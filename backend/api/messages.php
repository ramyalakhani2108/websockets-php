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
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

// messages.php: Handles message actions (send, fetch history)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Model/Message.php';
require_once __DIR__ . '/../src/Service/MessageService.php';
require_once __DIR__ . '/../src/Controller/MessageController.php';

use App\Database\Database;
use App\Service\MessageService;
use App\Controller\MessageController;

header('Content-Type: application/json');

// Dependency injection
$db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
$messageService = new MessageService($db);
$controller = new MessageController($messageService);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        $controller->send($input);
        break;
    case 'GET':
        $from_id = isset($_GET['from_id']) ? (int)$_GET['from_id'] : null;
        $to_id = isset($_GET['to_id']) ? (int)$_GET['to_id'] : null;
        $controller->fetchHistory($from_id, $to_id);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
}
