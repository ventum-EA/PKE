<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Lietotāja autentifikācija",
     *     description="Pieslēdz lietotāju ar e-pastu un paroli. Atbildē tiek iestatīta sesijas sīkdatne.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="[email protected]"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Veiksmīga autorizācija",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nepareizi dati"),
     *     @OA\Response(response=422, description="Validācijas kļūda", @OA\JsonContent(ref="#/components/schemas/ValidationError")),
     *     @OA\Response(response=429, description="Pārāk daudz mēģinājumu (rate limit)")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Nepareizi autentifikācijas dati.',
            ], 401);
        }

        if ($request->hasSession()) { $request->session()->regenerate(); }

        $user = Auth::user();
        \App\Models\AuditLog::record('auth.login', $user);

        $response = [
            'message' => 'Veiksmīga autorizācija',
            'user' => new UserResource($user),
        ];

        // If 2FA is enabled, the frontend must show the OTP input before
        // granting full access. The Ensure2FAVerified middleware blocks
        // all other endpoints until /2fa/verify succeeds.
        if ($user->two_factor_enabled) {
            $response['2fa_required'] = true;
        }

        return response()->json($response);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Lietotāja atslēgšanās",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Atslēgšanās veiksmīga"),
     *     @OA\Response(response=401, description="Neautorizēts")
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        if ($request->hasSession()) { $request->session()->invalidate(); }
        if ($request->hasSession()) { $request->session()->regenerateToken(); }

        return response()->json(['message' => 'Atslēgšanās veiksmīga']);
    }
}
