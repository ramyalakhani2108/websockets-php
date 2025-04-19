<?php
namespace App\Model;

/**
 * Class Message
 * Represents a message entity.
 */
class Message
{
    public int $id;
    public int $from_id;
    public int $to_id;
    public string $message;
    public string $created_at;

    public function __construct(int $id, int $from_id, int $to_id, string $message, string $created_at)
    {
        $this->id = $id;
        $this->from_id = $from_id;
        $this->to_id = $to_id;
        $this->message = $message;
        $this->created_at = $created_at;
    }
}
