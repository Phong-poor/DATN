<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('admin.chat', function ($user) {
    return $user->role === 'admin';
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $user->role === 'admin' || ($conversation && (int) $user->id === (int) $conversation->user_id);
});
