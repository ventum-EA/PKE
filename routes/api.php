<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\DailyPuzzleController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameImportController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MultiplayerController;
use App\Http\Controllers\OpeningController;
use App\Http\Controllers\PuzzleBankController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('login');

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware(['guest', 'throttle:3,10'])->name('register');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:3,10'])->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:5,10'])->name('password.update');

Route::get('/shared/{token}', [GameController::class, 'getShared'])->name('games.shared');

Route::get('/openings', [OpeningController::class, 'index'])->name('openings.index');
Route::get('/openings/{opening}', [OpeningController::class, 'show'])->name('openings.show');
Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/user', [UserController::class, 'me'])->name('users.me');

    Route::post('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery');

    Route::put('/user/settings', [UserController::class, 'updateSettings'])->name('users.settings');
    Route::put('/user/profile', [UserController::class, 'updateProfile'])->name('users.profile');
    Route::put('/user/password', [PasswordController::class, 'update'])->name('users.password');
    Route::delete('/user/me', [UserController::class, 'destroySelf'])->name('users.destroySelf');

    // Admin-only routes
    Route::middleware(['can:manage users'])->group(function () {
        Route::get('/users', [UserController::class, 'retrieve'])->name('users.retrieve');
        Route::get('/user/{id}', [UserController::class, 'getOne'])->name('users.getOne');
        Route::post('/user/create', [UserController::class, 'store'])->name('users.store');
        Route::put('/user/modify', [UserController::class, 'modify'])->name('users.modify');
        Route::delete('/user/{id}', [UserController::class, 'delete'])->name('users.delete');

        // Admin-specific operations
        Route::get('/admin/stats', [AdminController::class, 'stats'])->name('admin.stats');
        Route::get('/admin/games', [AdminController::class, 'allGames'])->name('admin.games');
        Route::put('/admin/user/{id}/role', [AdminController::class, 'updateRole'])->name('admin.updateRole');
        Route::post('/admin/user/{id}/reset-elo', [AdminController::class, 'resetElo'])->name('admin.resetElo');
        Route::delete('/admin/game/{id}', [AdminController::class, 'deleteGame'])->name('admin.deleteGame');

        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit.index');
    });

    Route::get('/games', [GameController::class, 'retrieve'])->name('games.retrieve');
    Route::post('/game/create', [GameController::class, 'store'])->name('games.store');
    Route::get('/game/{id}', [GameController::class, 'getOne'])->name('games.getOne');
    Route::put('/game/modify', [GameController::class, 'modify'])->name('games.modify');
    Route::delete('/game/{id}', [GameController::class, 'delete'])->name('games.delete');
    Route::post('/game/{id}/analyze', [GameController::class, 'analyze'])
        ->middleware('throttle:10,1')->name('games.analyze');
    Route::get('/game/{id}/moves', [GameController::class, 'getMoves'])->name('games.moves');
    Route::post('/game/{id}/moves', [GameController::class, 'saveMoves'])->name('games.saveMoves');
    Route::post('/game/{id}/share', [GameController::class, 'share'])->name('games.share');
    Route::get('/game/{id}/download', [GameController::class, 'download'])->name('games.download');
    Route::get('/games/stats', [GameController::class, 'stats'])->name('games.stats');

    Route::post('/training/generate/{gameId}', [TrainingController::class, 'generate'])
        ->middleware('throttle:20,1')->name('training.generate');
    Route::post('/training/openings', [TrainingController::class, 'generateOpeningTraining'])
        ->middleware('throttle:20,1')->name('training.openings');
    Route::post('/training/submit/{sessionId}', [TrainingController::class, 'submit'])->name('training.submit');
    Route::post('/training/complete', [TrainingController::class, 'complete'])->name('training.complete');
    Route::get('/training/progress', [TrainingController::class, 'progress'])->name('training.progress');
    Route::get('/training/progress-report', [TrainingController::class, 'progressReport'])->name('training.progressReport');

    // Personalized recommendations (§2.2.6)
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');

    Route::get('/elo/history', [GameController::class, 'eloHistory'])->name('elo.history');

    Route::post('/openings/{opening}/progress', [OpeningController::class, 'trackProgress'])->name('openings.progress');
    Route::post('/lessons/{lesson}/progress', [LessonController::class, 'trackProgress'])->name('lessons.progress');

    // Achievements
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::post('/achievements/check', [AchievementController::class, 'check'])->name('achievements.check');

    // Daily Puzzle
    Route::get('/daily-puzzle', [DailyPuzzleController::class, 'today'])->name('daily.today');
    Route::post('/daily-puzzle/submit', [DailyPuzzleController::class, 'submit'])->name('daily.submit');
    Route::get('/daily-puzzle/history', [DailyPuzzleController::class, 'history'])->name('daily.history');

    // Game Annotations
    Route::get('/game/{id}/annotations', [AnnotationController::class, 'index'])->name('annotations.index');
    Route::post('/game/{id}/annotations', [AnnotationController::class, 'store'])->name('annotations.store');
    Route::delete('/game/{id}/annotations/{moveIndex}', [AnnotationController::class, 'destroy'])->name('annotations.destroy');

    // Multiplayer
    Route::post('/multiplayer/create', [MultiplayerController::class, 'create'])->name('multiplayer.create');
    Route::post('/multiplayer/join/{token}', [MultiplayerController::class, 'join'])->name('multiplayer.join');
    Route::get('/multiplayer/history', [MultiplayerController::class, 'history'])->name('multiplayer.history');
    Route::post('/multiplayer/queue/join', [MultiplayerController::class, 'joinQueue'])->name('multiplayer.queue.join');
    Route::post('/multiplayer/queue/leave', [MultiplayerController::class, 'leaveQueue'])->name('multiplayer.queue.leave');
    Route::get('/multiplayer/queue/poll', [MultiplayerController::class, 'pollQueue'])
        ->middleware('throttle:45,1')->name('multiplayer.queue.poll');
    Route::get('/multiplayer/{id}', [MultiplayerController::class, 'show'])
        ->middleware('throttle:60,1')->name('multiplayer.show');
    Route::post('/multiplayer/{id}/move', [MultiplayerController::class, 'move'])
        ->middleware('throttle:30,1')->name('multiplayer.move');
    Route::post('/multiplayer/{id}/resign', [MultiplayerController::class, 'resign'])->name('multiplayer.resign');
    Route::post('/multiplayer/{id}/draw', [MultiplayerController::class, 'draw'])->name('multiplayer.draw');
    Route::post('/multiplayer/{id}/timeout', [MultiplayerController::class, 'timeout'])->name('multiplayer.timeout');

    // Friends
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/add', [FriendController::class, 'add'])->name('friends.add');
    Route::post('/friends/{id}/accept', [FriendController::class, 'accept'])->name('friends.accept');
    Route::delete('/friends/{id}', [FriendController::class, 'destroy'])->name('friends.destroy');

    // Game Import (Lichess / Chess.com)
    Route::post('/games/import', [GameImportController::class, 'import'])
        ->middleware('throttle:5,1')->name('games.import');

    // Pattern Detection
    Route::get('/patterns', [GameImportController::class, 'getPatterns'])->name('patterns.index');
    Route::post('/patterns/detect', [GameImportController::class, 'detectPatterns'])->name('patterns.detect');

    // Puzzle Bank
    Route::get('/puzzle-bank/next', [PuzzleBankController::class, 'next'])->name('puzzle-bank.next');
    Route::post('/puzzle-bank/{id}/submit', [PuzzleBankController::class, 'submit'])->name('puzzle-bank.submit');
    Route::get('/puzzle-bank/stats', [PuzzleBankController::class, 'stats'])->name('puzzle-bank.stats');
    Route::get('/puzzle-bank/themes', [PuzzleBankController::class, 'themes'])->name('puzzle-bank.themes');
});
