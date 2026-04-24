<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\OnlineGame;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channels require authentication.
| Presence channels track who is connected.
|
*/

// Private channel: game moves and events
// Only players in the game can listen
Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    $game = OnlineGame::find($gameId);
    if (!$game) return false;
    return $game->white_id === $user->id || $game->black_id === $user->id;
});

// Private channel: user-specific notifications
// Only the user themselves can listen
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Presence channel: online users
// Used for friend online status tracking
Broadcast::channel('online-users', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'elo_rating' => $user->elo_rating,
    ];
});
