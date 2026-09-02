<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $readByUserId;

    public function broadcastAs()
    {
        return 'message.read';
    }

    public function __construct(int $conversationId, int $readByUserId)
    {
        $this->conversationId = $conversationId;
        $this->readByUserId = $readByUserId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->conversationId),
            new PrivateChannel('admin.chat'),
        ];
    }
}
