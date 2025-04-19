<?php
namespace App\Controller;

use App\Service\UserService;
use App\Model\User;

/**
 * Class UserController
 * Handles HTTP logic for user endpoints.
 */
class UserController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Register endpoint
     * @param array $input
     */
    public function register(array $input): void
    {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
            return;
        }
        $user = $this->userService->register($username, $password);
        if (!$user) {
            http_response_code(409);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        echo json_encode(['id' => $user->id, 'username' => $user->username]);
    }

    /**
     * Login endpoint
     * @param array $input
     */
    public function login(array $input): void
    {
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if (!$username || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and password required']);
            return;
        }
        $user = $this->userService->login($username, $password);
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
            return;
        }
        echo json_encode(['id' => $user->id, 'username' => $user->username]);
    }

    /**
     * Fetch all users endpoint
     */
    public function fetchUsers(): void
    {
        $users = $this->userService->fetchAll();
        $result = array_map(fn(User $u) => ['id' => $u->id, 'username' => $u->username], $users);
        echo json_encode($result);
    }
}
