<?php

namespace App\Events;

use App\Models\PrivateMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrivateMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PrivateMessage $message
    ) {
        $this->message->loadMissing('user');
    }

    public function broadcastWith(): array
    {
        $user = $this->message->user;

        return [

            // =========================
            // DATA PESAN ASLI
            // Untuk halaman/room chat
            // =========================
            'message' => [
                'id' => $this->message->id,

                'user_id' => $this->message->user_id,

                'receiver_id' => $this->message->receiver_id,

                'message' => $this->message->message,

                'created_at' => $this->message->created_at,

                'updated_at' => $this->message->updated_at,
            ],

            // =========================
            // DATA CONVERSATION
            // Untuk sidebar/list chat
            // =========================
            'conversation' => [
                'id' => $user->id,

                'name' => $user->name,

                'avatar' => $user->avatar_url
                    ?? 'https://ui-avatars.com/api/?name='
                    . urlencode($user->name),

                'lastMessage' => $this->message->message,

                'time' => $this->message->created_at
                    ?->diffForHumans() ?? '',

                'online' => $user->last_seen_at
                    ?->gt(now()->subMinutes(5)) ?? false,
            ],
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("chat.{$this->message->receiver_id}"),
        ];
    }
}
