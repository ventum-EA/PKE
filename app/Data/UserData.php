<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required, Email]
        public string $email,

        public ?string $password = null,
        public ?string $role = 'user',
        public ?int $user_id = null,
        public ?int $elo_rating = 1200,
        public ?string $preferred_color = 'white',
        public ?string $locale = 'lv',
        public ?bool $dark_mode = false,
        public ?bool $sound_enabled = true,
    ) {}
}
