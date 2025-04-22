<?php
namespace App\Service;

use App\Model\Group;
use App\Model\GroupMember;
use App\Model\GroupMessage;
use PDO;

class GroupService {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function createGroup($name, $created_by) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('INSERT INTO groups (name, created_by) VALUES (?, ?)');
        $stmt->execute([$name, $created_by]);
        return $pdo->lastInsertId();
    }
    public function addMember($group_id, $user_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->execute([$group_id, $user_id]);
    }
    public function removeMember($group_id, $user_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$group_id, $user_id]);
    }
    public function getUserGroups($user_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('SELECT g.* FROM groups g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getGroupMembers($group_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('SELECT u.* FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?');
        $stmt->execute([$group_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function sendGroupMessage($group_id, $from_id, $message) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('INSERT INTO group_messages (group_id, from_id, message) VALUES (?, ?, ?)');
        $stmt->execute([$group_id, $from_id, $message]);
    }
    public function getGroupMessages($group_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare('SELECT gm.*, u.username FROM group_messages gm JOIN users u ON gm.from_id = u.id WHERE gm.group_id = ? ORDER BY gm.time ASC');
        $stmt->execute([$group_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
