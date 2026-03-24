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
}
