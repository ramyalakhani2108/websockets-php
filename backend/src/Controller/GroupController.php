<?php
namespace App\Controller;

use App\Service\GroupService;

class GroupController {
    private $groupService;
    public function __construct($groupService) { $this->groupService = $groupService; }
    public function create($input) {
        $name = $input['name'] ?? '';
        $created_by = $input['created_by'] ?? 0;
        if (!$name || !$created_by) { http_response_code(400); echo json_encode(['error' => 'Missing fields']); return; }
        $group_id = $this->groupService->createGroup($name, $created_by);
        $this->groupService->addMember($group_id, $created_by);
        echo json_encode(['group_id' => $group_id]);
    }
    public function addMember($input) {
        $group_id = $input['group_id'] ?? 0;
        $user_id = $input['user_id'] ?? 0;
        if (!$group_id || !$user_id) { http_response_code(400); echo json_encode(['error' => 'Missing fields']); return; }
        $this->groupService->addMember($group_id, $user_id);
        echo json_encode(['status' => 'ok']);
    }
    public function removeMember($input) {
        $group_id = $input['group_id'] ?? 0;
        $user_id = $input['user_id'] ?? 0;
        if (!$group_id || !$user_id) { http_response_code(400); echo json_encode(['error' => 'Missing fields']); return; }
        $this->groupService->removeMember($group_id, $user_id);
        echo json_encode(['status' => 'ok']);
    }
    public function userGroups($user_id) {
        if (!$user_id) { http_response_code(400); echo json_encode(['error' => 'Missing user_id']); return; }
        $groups = $this->groupService->getUserGroups($user_id);
        echo json_encode($groups);
    }
    public function groupMembers($group_id) {
        if (!$group_id) { http_response_code(400); echo json_encode(['error' => 'Missing group_id']); return; }
        $members = $this->groupService->getGroupMembers($group_id);
        echo json_encode($members);
    }
    public function sendMessage($input) {
        $group_id = $input['group_id'] ?? 0;
        $from_id = $input['from_id'] ?? 0;
        $message = $input['message'] ?? '';
        if (!$group_id || !$from_id || !$message) { http_response_code(400); echo json_encode(['error' => 'Missing fields']); return; }
        $this->groupService->sendGroupMessage($group_id, $from_id, $message);
        echo json_encode(['status' => 'ok']);
    }
    public function getMessages($group_id) {
        if (!$group_id) { http_response_code(400); echo json_encode(['error' => 'Missing group_id']); return; }
        $messages = $this->groupService->getGroupMessages($group_id);
        echo json_encode($messages);
    }
}
