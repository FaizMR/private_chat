<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivateMessage extends Model
{
    protected $table = 'private_messages';
    protected $fillable = [
        'user_id',
        'receiver_id',
        'message',
    ];

    public function user(): BelongsTo
    {
        // 'user_id' adalah foreign key di tabel private_messages
        return $this->belongsTo(User::class, 'user_id');
    }

    public function receiver(): BelongsTo
    {
        // 'receiver_id' adalah foreign key di tabel private_messages
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
