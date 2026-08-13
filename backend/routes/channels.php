<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    $userId = $user->id ?? $user->id_khachhang ?? $user->id_user ?? $user->getKey();
    return (int) $userId === (int) $id;
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
