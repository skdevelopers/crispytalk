<?php

use App\Models\User;
use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('chat.{chatId}', function (User $user, int $chatId) {
    return Chat::where('id', $chatId)
        ->whereHas('users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->exists();
});
