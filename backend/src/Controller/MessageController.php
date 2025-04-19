<?php
namespace App\Controller;

use App\Service\MessageService;
use App\Model\Message;

/**
 * Class MessageController
 * Handles HTTP logic for message endpoints.
 */
class MessageController
{
    private MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Send message endpoint
     * @param array $input
     */
    public function send(array $input): void
    {
        $from_id = $input['from_id'] ?? null;
        $to_id = $input['to_id'] ?? null;
        $message = trim($input['message'] ?? '');
        if (!$from_id || !$to_id || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'from_id, to_id, and message required']);
            return;
        }
        $success = $this->messageService->send((int)$from_id, (int)$to_id, $message);
        echo json_encode(['success' => $success]);
    }

    /**
     * Fetch chat history endpoint
     * @param int|null $from_id
     * @param int|null $to_id
     */
    public function fetchHistory(?int $from_id, ?int $to_id): void
    {
        if (!$from_id || !$to_id) {
            http_response_code(400);
            echo json_encode(['error' => 'from_id and to_id required']);
            return;
        }
        $messages = $this->messageService->fetchHistory($from_id, $to_id);
        $result = array_map(function(Message $msg) {
            return [
                'id' => $msg->id,
                'from_id' => $msg->from_id,
                'to_id' => $msg->to_id,
                'message' => $msg->message,
                'created_at' => $msg->created_at,
                'time' => date('H:i', strtotime($msg->created_at)),
            ];
        }, $messages);
        echo json_encode($result);
    }
}
