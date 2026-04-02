<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pgn' => 'required|string',
            'white_player' => 'nullable|string|max:255',
            'black_player' => 'nullable|string|max:255',
            'result' => 'nullable|in:1-0,0-1,1/2-1/2,*',
            'opening_name' => 'nullable|string|max:255',
            'opening_eco' => 'nullable|string|max:10',
            'total_moves' => 'nullable|integer|min:0',
            'user_color' => 'nullable|in:white,black',
            'played_at' => 'nullable|date',
        ];
    }
}
