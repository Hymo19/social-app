<?php
namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupCallSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $groupId,
        public int    $fromUserId,
        public string $fromUserName,
        public string $signal,
        public mixed  $data = null,
        public ?int   $toUserId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('group.' . $this->groupId)];
    }

    public function broadcastWith(): array
    {
        return [
            'from_user_id'   => $this->fromUserId,
            'from_user_name' => $this->fromUserName,
            'signal'         => $this->signal,
            'data'           => $this->data,
            'to_user_id'     => $this->toUserId,
        ];
    }
}