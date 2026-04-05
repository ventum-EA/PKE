<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\OpeningController;
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

// Public browsing — no auth required
Route::get('/openings', [OpeningController::class, 'index'])->name('openings.index');
Route::get('/openings/{opening}', [OpeningController::class, 'show'])->name('openings.show');
Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
Route::get('/lessons/{lesson}', [LessonController::class, 'show'])->name('lessons.show');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/user', [UserController::class, 'me'])->name('users.me');

    // 2FA management
    Route::post('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
    Route::post('/2fa/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('2fa.recovery');

    Route::put('/user/settings', [UserController::class, 'updateSettings'])->name('users.settings');
    Route::put('/user/profile', [UserController::class, 'updateProfile'])->name('users.profile');
    Route::put('/user/password', [PasswordController::class, 'update'])->name('users.password');
    Route::delete('/user/me', [UserController::class, 'destroySelf'])->name('users.destroySelf');

    Route::middleware(['can:manage users'])->group(function () {
        Route::get('/users', [UserController::class, 'retrieve'])->name('users.retrieve');
        Route::get('/user/{id}', [UserController::class, 'getOne'])->name('users.getOne');
        Route::post('/user/create', [UserController::class, 'store'])->name('users.store');
        Route::put('/user/modify', [UserController::class, 'modify'])->name('users.modify');
        Route::delete('/user/{id}', [UserController::class, 'delete'])->name('users.delete');

        Route::get('/audit-logs', function (\Illuminate\Http\Request $request) {
            $logs = \App\Models\AuditLog::with('user:id,name')
                ->orderByDesc('created_at')
                ->paginate($request->integer('perPage', 50));

            return response()->json(['audit_logs' => $logs]);
        })->name('audit.index');
    });

    Route::get('/games', [GameController::class, 'retrieve'])->name('games.retrieve');
    Route::post('/game/create', [GameController::class, 'store'])->name('games.store');
    Route::get('/game/{id}', [GameController::class, 'getOne'])->name('games.getOne');
    Route::put('/game/modify', [GameController::class, 'modify'])->name('games.modify');
    Route::delete('/game/{id}', [GameController::class, 'delete'])->name('games.delete');
    Route::post('/game/{id}/analyze', [GameController::class, 'analyze'])->name('games.analyze');
    Route::get('/game/{id}/moves', [GameController::class, 'getMoves'])->name('games.moves');
    Route::post('/game/{id}/moves', [GameController::class, 'saveMoves'])->name('games.saveMoves');
    Route::post('/game/{id}/share', [GameController::class, 'share'])->name('games.share');
    Route::get('/game/{id}/download', [GameController::class, 'download'])->name('games.download');
    Route::get('/games/stats', [GameController::class, 'stats'])->name('games.stats');

    Route::post('/training/generate/{gameId}', [TrainingController::class, 'generate'])->name('training.generate');
    Route::post('/training/openings', [TrainingController::class, 'generateOpeningTraining'])->name('training.openings');
    Route::post('/training/submit/{sessionId}', [TrainingController::class, 'submit'])->name('training.submit');
    Route::get('/training/progress', [TrainingController::class, 'progress'])->name('training.progress');

    // Progress tracking
    Route::post('/openings/{opening}/progress', [OpeningController::class, 'trackProgress'])->name('openings.progress');
    Route::post('/lessons/{lesson}/progress', [LessonController::class, 'trackProgress'])->name('lessons.progress');
});
