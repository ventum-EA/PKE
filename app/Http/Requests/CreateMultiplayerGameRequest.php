<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates multiplayer game creation parameters.
 *
 * Used by MultiplayerController::create()
 */
class CreateMultiplayerGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'color' => 'nullable|in:white,black,random',
            'time_control' => 'required|integer|in:180,300,600,900,1800',
            'rated' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'color.in' => 'Krāsai jābūt: white, black vai random.',
            'time_control.required' => 'Laika kontrole ir obligāta.',
            'time_control.in' => 'Nepareiza laika kontroles vērtība.',
        ];
    }
}
