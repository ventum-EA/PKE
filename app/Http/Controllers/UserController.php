<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\UserData;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    private const KEY_USER = 'user';
    private const KEY_USERS = 'users';
    private const KEY_PAYLOAD = 'payload';
    private const KEY_MESSAGE = 'message';

    public function __construct(
        protected UserService $userService,
        protected UserRepository $userRepo
    ) {}

    public function store(Request $request, UserData $userData): JsonResponse
    {
        $user = $this->userService->createUser($userData);

        return response()->json([
            self::KEY_MESSAGE => 'Lietotājs izveidots veiksmīgi!',
            self::KEY_PAYLOAD => [self::KEY_USER => new UserResource($user[self::KEY_USER])]
        ], Response::HTTP_OK);
    }

    public function modify(Request $request, UserData $userData): JsonResponse
    {
        $user = $this->userService->updateExistingUser($userData);

        return response()->json([
            self::KEY_MESSAGE => 'Lietotājs atjaunināts veiksmīgi',
            self::KEY_PAYLOAD => ['id' => $user->getId()]
        ], Response::HTTP_OK);
    }

    public function retrieve(Request $request): JsonResponse
    {
        $perPage = $request->get('perPage', 15);
        $users = $this->userRepo->getFilteredUsers((int) $perPage);

        return response()->json([
            self::KEY_MESSAGE => 'Lietotāji ielādēti veiksmīgi',
            self::KEY_PAYLOAD => [
                self::KEY_USERS => UserResource::collection($users)->response()->getData(true),
            ]
        ], Response::HTTP_OK);
    }

    public function getOne(int $id): JsonResponse
    {
        $user = $this->userRepo->findById($id);

        return response()->json([
            self::KEY_MESSAGE => 'Lietotāja dati ielādēti',
            self::KEY_PAYLOAD => [self::KEY_USER => new UserResource($user)]
        ], Response::HTTP_OK);
    }

    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepo->findById($id);
        $this->userRepo->delete($user);

        return response()->json([
            self::KEY_MESSAGE => 'Lietotājs dzēsts veiksmīgi',
            self::KEY_PAYLOAD => ['id' => $id]
        ], Response::HTTP_OK);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            self::KEY_MESSAGE => 'Lietotāja profils',
            self::KEY_PAYLOAD => [self::KEY_USER => new UserResource($request->user())]
        ], Response::HTTP_OK);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->userService->updateSettings($request->all());

        return response()->json([
            self::KEY_MESSAGE => 'Iestatījumi saglabāti',
            self::KEY_PAYLOAD => [self::KEY_USER => new UserResource($user)]
        ], Response::HTTP_OK);
    }

    /**
     * Update the currently authenticated user's profile (name, email).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255|unique:users,name,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $this->userRepo->update($user, $validated);

        return response()->json([
            self::KEY_MESSAGE => 'Profils atjaunināts veiksmīgi',
            self::KEY_PAYLOAD => [self::KEY_USER => new UserResource($user->fresh())]
        ], Response::HTTP_OK);
    }

    /**
     * Permanently delete the currently authenticated user and all associated data.
     * Fulfils the GDPR "right to erasure" requirement.
     */
    public function destroySelf(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'password' => ['Parole ir nepareiza.'],
            ]);
        }

        // Record before deletion so user_id is still available
        \App\Models\AuditLog::record('user.delete_self', $user, [
            'email' => $user->email,
        ]);

        // Log out of the current web session
        \Illuminate\Support\Facades\Auth::guard('web')->logout();
        if ($request->hasSession()) { $request->session()->invalidate(); }
        if ($request->hasSession()) { $request->session()->regenerateToken(); }

        // Delete the user — related games/moves/training_sessions cascade via FKs
        $this->userRepo->delete($user);

        return response()->json([
            self::KEY_MESSAGE => 'Konts un visi saistītie dati dzēsti',
        ], Response::HTTP_OK);
    }
}
