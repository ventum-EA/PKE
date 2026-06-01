<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates client-side (WASM) analysis results before saving to the database.
 *
 * Used by GameController::saveMoves()
 */
class SaveMovesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware; game ownership checked in controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'moves' => 'required|array|min:1|max:600',
            'moves.*.move_number' => 'required|integer|min:1',
            'moves.*.color' => 'required|in:white,black',
            'moves.*.move_san' => 'required|string|max:10',
            'moves.*.eval_before' => 'nullable|numeric',
            'moves.*.eval_after' => 'nullable|numeric',
            'moves.*.eval_diff' => 'nullable|numeric',
            'moves.*.best_move' => 'nullable|string|max:10',
            'moves.*.classification' => 'nullable|string|in:best,excellent,good,inaccuracy,mistake,blunder',
            'moves.*.error_category' => 'nullable|string|in:tactical,positional,opening,endgame',
            'moves.*.explanation' => 'nullable|string|max:500',
            'moves.*.fen_before' => 'nullable|string|max:100',
            'moves.*.fen_after' => 'nullable|string|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'moves.required' => 'Gājienu saraksts ir obligāts.',
            'moves.max' => 'Partija nedrīkst pārsniegt 600 gājienus.',
            'moves.*.classification.in' => 'Nepareiza gājiena klasifikācija.',
            'moves.*.error_category.in' => 'Nepareiza kļūdas kategorija.',
        ];
    }
}
