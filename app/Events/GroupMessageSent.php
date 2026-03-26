<?php
namespace App\Events;

use App\Models\GroupMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GroupMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public GroupMessage $message;

    public function __construct(GroupMessage $message)
    {
        $this->message = $message->load('user');
    }

    public function broadcastOn(): array
    {
        return [new PresenceChannel('group.' . $this->message->group_id)];
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->message->id,
            'content'    => $this->message->content,
            'image_url'  => $this->message->image_url,
            'type'       => $this->message->type,
            'created_at' => $this->message->created_at->diffForHumans(),
            'user' => [
                'id'         => $this->message->user->id,
                'name'       => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar
                    ? Storage::url($this->message->user->avatar)
                    : null,
            ],
        ];
    }
}