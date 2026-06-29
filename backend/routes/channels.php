<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin.orders', function ($user) {
    return $user->vaitro !== 'user';
});

Broadcast::channel('admin.chat', function ($user) {
    return $user->vaitro !== 'user';
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);
    return $user->vaitro !== 'user' || ($conversation && (int) $user->id === (int) $conversation->id_khachhang);
});
