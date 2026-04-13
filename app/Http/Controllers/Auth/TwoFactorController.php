<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a new 2FA secret and return the provisioning URI + QR code SVG.
     * The user must verify the code before 2FA is actually enabled.
     */
    public function setup(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Nepareiza parole.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $secret = $this->google2fa->generateSecretKey(32);

        // Store the secret temporarily (not yet confirmed)
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'Šaha Platforma'),
            $user->email,
            $secret
        );

        // Generate SVG QR code via BaconQrCode
        $qrSvg = $this->generateQrSvg($otpAuthUrl);

        return response()->json([
            'message'  => '2FA iestatīšana uzsākta',
            'payload'  => [
                'secret'   => $secret,
                'qr_svg'   => $qrSvg,
                'otp_url'  => $otpAuthUrl,
            ],
        ]);
    }

    /**
     * Confirm the 2FA setup by verifying the first OTP code.
     * Also generates 8 recovery codes.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json([
                'message' => 'Vispirms izsauc /2fa/setup lai ģenerētu noslēpumu.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid) {
            return response()->json([
                'message' => 'Nepareizs verifikācijas kods.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate 8 recovery codes
        $recoveryCodes = collect(range(1, 8))->map(fn () => Str::random(10))->values()->all();

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        AuditLog::record('2fa.enable', $user);

        return response()->json([
            'message'  => '2FA veiksmīgi aktivizēta!',
            'payload'  => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Verify a 2FA code during login flow.
     * Called after the user has authenticated with email + password.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20',
        ]);

        $user = $request->user();

        if (!$user->two_factor_enabled || !$user->two_factor_secret) {
            return response()->json([
                'message' => '2FA nav aktivizēta šim kontam.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $code = $request->code;

        // Try OTP first
        if (strlen($code) === 6 && $this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            $request->session()->put('2fa_verified', true);
            return response()->json(['message' => '2FA verifikācija veiksmīga']);
        }

        // Try recovery code
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($code, $recoveryCodes);

        if ($index !== false) {
            // Consume the recovery code (one-time use)
            unset($recoveryCodes[$index]);
            $user->two_factor_recovery_codes = array_values($recoveryCodes);
            $user->save();

            $request->session()->put('2fa_verified', true);
            AuditLog::record('2fa.recovery_used', $user);

            return response()->json([
                'message'           => '2FA verifikācija ar rezerves kodu veiksmīga',
                'remaining_codes'   => count($recoveryCodes),
            ]);
        }

        return response()->json([
            'message' => 'Nepareizs kods vai rezerves kods.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Disable 2FA. Requires password confirmation.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Nepareiza parole.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->two_factor_enabled = false;
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        AuditLog::record('2fa.disable', $user);

        return response()->json(['message' => '2FA deaktivizēta.']);
    }

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Nepareiza parole.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$user->two_factor_enabled) {
            return response()->json([
                'message' => '2FA nav aktivizēta.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $codes = collect(range(1, 8))->map(fn () => Str::random(10))->values()->all();
        $user->two_factor_recovery_codes = $codes;
        $user->save();

        return response()->json([
            'message'  => 'Jauni rezerves kodi ģenerēti.',
            'payload'  => ['recovery_codes' => $codes],
        ]);
    }

    /**
     * Generate an SVG QR code using BaconQrCode.
     */
    private function generateQrSvg(string $data): string
    {
        $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd();
        $imageRenderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            $renderer
        );
        $writer = new \BaconQrCode\Writer($imageRenderer);

        return $writer->writeString($data);
    }
}
