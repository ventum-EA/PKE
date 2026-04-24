<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\GameRepositoryInterface;
use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\Game;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ApiResponse;

    // Default ELO loaded from config; kept as fallback constant for backward compatibility
    private const DEFAULT_ELO = 1200;

    public function __construct(
        protected UserRepository $userRepo,
        protected GameRepositoryInterface $gameRepo,
    ) {}

    /**
     * Platform-wide statistics for the admin dashboard.
     */
    public function stats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalGames = Game::count();
        $analyzedGames = Game::where('is_analyzed', true)->count();
        $totalAdmins = User::role(UserRole::ADMIN->value)->count();

        $recentUsers = User::where('created_at', '>=', now()->subDays(30))->count();
        $recentGames = Game::where('created_at', '>=', now()->subDays(30))->count();

        return $this->success('Admin stats loaded.', [
            'total_users' => $totalUsers,
            'total_games' => $totalGames,
            'analyzed_games' => $analyzedGames,
            'total_admins' => $totalAdmins,
            'recent_users' => $recentUsers,
            'recent_games' => $recentGames,
        ]);
    }

    /**
     * Change a user's role (admin <-> user).
     */
    public function updateRole(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', UserRole::values()),
        ]);

        $user = $this->userRepo->findById($id);
        $oldRole = $user->getRoleNames()->first() ?? 'user';
        $newRole = $request->input('role');

        // Prevent demoting the last admin
        if ($oldRole === UserRole::ADMIN->value && $newRole === UserRole::USER->value) {
            $adminCount = User::role(UserRole::ADMIN->value)->count();  // Spatie scope
            if ($adminCount <= 1) {
                return $this->error('Cannot remove the last admin.', 422);
            }
        }

        // Prevent self-demotion
        if ($user->id === $request->user()->id && $newRole !== $oldRole) {
            return $this->error('Cannot change your own role.', 422);
        }

        $user->syncRoles([$newRole]);

        AuditLog::record('admin.role_change', $user, [
            'old_role' => $oldRole,
            'new_role' => $newRole,
        ]);

        return $this->success('Role updated.', [
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Reset a user's ELO rating to default.
     */
    public function resetElo(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepo->findById($id);
        $oldElo = $user->elo_rating;

        $this->userRepo->update($user, ['elo_rating' => self::DEFAULT_ELO]);

        AuditLog::record('admin.elo_reset', $user, [
            'old_elo' => $oldElo,
            'new_elo' => self::DEFAULT_ELO,
        ]);

        return $this->success('ELO reset to ' . self::DEFAULT_ELO . '.', [
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * List all games across all users (admin only).
     */
    public function allGames(Request $request): JsonResponse
    {
        $perPage = $request->integer('perPage', 200);
        $games = Game::with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate(min($perPage, 500));

        return $this->success('All games loaded.', [
            'games' => $games,
        ]);
    }

    /**
     * Admin deletes any user's game.
     */
    public function deleteGame(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepo->findById($id);

        AuditLog::record('admin.game_delete', $game, [
            'owner_id' => $game->getUserId(),
            'opening' => $game->getOpeningName(),
            'result' => $game->getResult(),
        ]);

        $this->gameRepo->delete($game);

        return $this->success('Game deleted.', ['id' => $id]);
    }

    /**
     * Paginated list of audit log entries.
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $logs = AuditLog::with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($request->integer('perPage', 50));

        return $this->success('Audit logs', ['audit_logs' => $logs]);
    }
}
