<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Change the currently authenticated user's password.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Pašreizējā parole ir nepareiza.'],
            ]);
        }

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        return response()->json([
            'message' => 'Parole veiksmīgi nomainīta',
        ], Response::HTTP_OK);
    }
}
