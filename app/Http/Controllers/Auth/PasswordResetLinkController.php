<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            Log::warning('Password reset email failed: ' . $e->getMessage());

            // Return a generic success-like message so we don't leak
            // whether the email exists or that mail is misconfigured.
            return response()->json([
                'message' => 'Ja šāds e-pasts eksistē, atjaunošanas saite tiks nosūtīta.',
            ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Paroles atjaunošanas saite nosūtīta.']);
        }

        return response()->json(['message' => 'Neizdevās nosūtīt saiti.'], 400);
    }
}
