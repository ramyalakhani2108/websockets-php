<?php
namespace App\Model;

/**
 * Class User
 * Represents a user entity.
 */
class User
{
    public int $id;
    public string $username;
    public ?string $password;

    public function __construct(int $id, string $username, ?string $password = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->password = $password;
    }
}
