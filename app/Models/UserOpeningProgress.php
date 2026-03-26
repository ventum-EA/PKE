<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserOpeningProgress extends Model
{
    protected $table = 'user_opening_progress';

    protected $fillable = [
        'user_id', 'opening_id', 'times_practiced',
        'practiced_as_white', 'practiced_as_black',
        'completed', 'last_practiced_at',
    ];

    protected $casts = [
        'practiced_as_white' => 'boolean',
        'practiced_as_black' => 'boolean',
        'completed' => 'boolean',
        'last_practiced_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function opening() { return $this->belongsTo(Opening::class); }
}
