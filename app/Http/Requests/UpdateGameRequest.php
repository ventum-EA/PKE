<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'game_id' => 'required|integer|exists:games,id',
            'pgn' => 'sometimes|string',
            'white_player' => 'nullable|string|max:255',
            'black_player' => 'nullable|string|max:255',
            'result' => 'nullable|in:1-0,0-1,1/2-1/2,*',
            'opening_name' => 'nullable|string|max:255',
            'user_color' => 'nullable|in:white,black',
        ];
    }
}
