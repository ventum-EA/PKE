<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\UserNotificationEvent;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendRequestNotification;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FriendController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/friends — list accepted friends + pending requests.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Friends I accepted or who accepted me
        $accepted = Friendship::where('status', 'accepted')
            ->where(fn($q) => $q->where('user_id', $userId)->orWhere('friend_id', $userId))
            ->with(['user:id,name,elo_rating', 'friend:id,name,elo_rating'])
            ->get()
            ->map(function ($f) use ($userId) {
                $friend = $f->user_id === $userId ? $f->friend : $f->user;
                return [
                    'id'         => $friend->id,
                    'name'       => $friend->name,
                    'elo_rating' => (int) $friend->elo_rating,
                    'online'     => $this->isOnline($friend->id),
                ];
            });

        // Pending requests I received
        $incoming = Friendship::where('friend_id', $userId)
            ->where('status', 'pending')
            ->with('user:id,name,elo_rating')
            ->get()
            ->map(fn($f) => [
                'id'         => $f->user->id,
                'name'       => $f->user->name,
                'elo_rating' => (int) $f->user->elo_rating,
                'request_id' => $f->id,
            ]);

        // Pending requests I sent
        $outgoing = Friendship::where('user_id', $userId)
            ->where('status', 'pending')
            ->with('friend:id,name,elo_rating')
            ->get()
            ->map(fn($f) => [
                'id'         => $f->friend->id,
                'name'       => $f->friend->name,
                'elo_rating' => (int) $f->friend->elo_rating,
                'request_id' => $f->id,
            ]);

        return $this->success('Friends loaded.', [
            'friends'  => $accepted->values(),
            'incoming' => $incoming->values(),
            'outgoing' => $outgoing->values(),
        ]);
    }

    /**
     * POST /api/friends/add — send a friend request by username.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate(['name' => 'required|string|max:255']);

        $userId = $request->user()->id;
        $friend = User::where('name', $request->input('name'))->first();

        if (!$friend) return $this->error('User not found.', Response::HTTP_NOT_FOUND);
        if ($friend->id === $userId) return $this->error('Cannot add yourself.', Response::HTTP_BAD_REQUEST);

        // Check if already friends or pending
        $existing = Friendship::where(function ($q) use ($userId, $friend) {
            $q->where(['user_id' => $userId, 'friend_id' => $friend->id])
              ->orWhere(['user_id' => $friend->id, 'friend_id' => $userId]);
        })->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return $this->error('Already friends.', Response::HTTP_CONFLICT);
            }
            return $this->error('Request already pending.', Response::HTTP_CONFLICT);
        }

        Friendship::create([
            'user_id'   => $userId,
            'friend_id' => $friend->id,
            'status'    => 'pending',
        ]);

        // Send real-time notification via WebSocket
        broadcast(new UserNotificationEvent(
            $friend->id,
            'friend_request',
            "{$request->user()->name} sent you a friend request",
            ['from_id' => $userId, 'from_name' => $request->user()->name],
        ));

        // Send email notification if enabled
        if ($friend->email_friend_requests ?? true) {
            $friend->notify(new FriendRequestNotification(
                $request->user()->name,
                (int) $request->user()->elo_rating,
            ));
        }

        return $this->success('Friend request sent.', [
            'friend' => ['id' => $friend->id, 'name' => $friend->name],
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/friends/{id}/accept — accept a friend request.
     */
    public function accept(int $requestId, Request $request): JsonResponse
    {
        $friendship = Friendship::where('id', $requestId)
            ->where('friend_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendship->update(['status' => 'accepted']);

        return $this->success('Friend request accepted.');
    }

    /**
     * DELETE /api/friends/{id} — reject request or remove friend.
     */
    public function destroy(int $friendUserId, Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $deleted = Friendship::where(function ($q) use ($userId, $friendUserId) {
            $q->where(['user_id' => $userId, 'friend_id' => $friendUserId])
              ->orWhere(['user_id' => $friendUserId, 'friend_id' => $userId]);
        })->delete();

        return $this->success($deleted ? 'Friend removed.' : 'Not found.');
    }

    /**
     * Simple online check: user has an active session within the last 5 minutes.
     */
    private function isOnline(int $userId): bool
    {
        return (bool) \DB::table('sessions')
            ->where('user_id', $userId)
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->exists();
    }
}
