<?php
// groups.php: Group chat API endpoint
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Model/Group.php';
require_once __DIR__ . '/../src/Model/GroupMember.php';
require_once __DIR__ . '/../src/Model/GroupMessage.php';
require_once __DIR__ . '/../src/Service/GroupService.php';
require_once __DIR__ . '/../src/Controller/GroupController.php';

use App\Database\Database;
use App\Service\GroupService;
use App\Controller\GroupController;

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS);
$groupService = new GroupService($db);
$controller = new GroupController($groupService);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        $action = $input['action'] ?? '';
        if ($action === 'create') {
            $controller->create($input);
        } elseif ($action === 'add_member') {
            $controller->addMember($input);
        } elseif ($action === 'remove_member') {
            $controller->removeMember($input);
        } elseif ($action === 'send_message') {
            $controller->sendMessage($input);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
        break;
    case 'GET':
        if (isset($_GET['user_id'])) {
            $controller->userGroups((int)$_GET['user_id']);
        } elseif (isset($_GET['group_id']) && isset($_GET['members'])) {
            $controller->groupMembers((int)$_GET['group_id']);
        } elseif (isset($_GET['group_id'])) {
            $controller->getMessages((int)$_GET['group_id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
}
