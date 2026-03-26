<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use App\Enums\GameResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    private array $openings = [
        ['name' => 'Sicīliešu aizsardzība', 'eco' => 'B20', 'pgn' => '1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 a6'],
        ['name' => 'Spāņu partija', 'eco' => 'C60', 'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7'],
        ['name' => 'Dāmas gambīts', 'eco' => 'D30', 'pgn' => '1. d4 d5 2. c4 e6 3. Nf3 Nf6 4. Nc3 Be7 5. Bg5 O-O'],
        ['name' => 'Franču aizsardzība', 'eco' => 'C00', 'pgn' => '1. e4 e6 2. d4 d5 3. Nc3 Nf6 4. e5 Nfd7 5. f4 c5'],
        ['name' => 'Karo-Kanna aizsardzība', 'eco' => 'B10', 'pgn' => '1. e4 c6 2. d4 d5 3. Nc3 dxe4 4. Nxe4 Bf5 5. Ng3 Bg6'],
        ['name' => 'Itāliešu partija', 'eco' => 'C50', 'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d4 exd4'],
        ['name' => 'Angļu atklātne', 'eco' => 'A20', 'pgn' => '1. c4 e5 2. Nc3 Nf6 3. g3 d5 4. cxd5 Nxd5 5. Bg2 Be6'],
        ['name' => 'Karališa indiešu aizsardzība', 'eco' => 'E60', 'pgn' => '1. d4 Nf6 2. c4 g6 3. Nc3 Bg7 4. e4 d6 5. Nf3 O-O'],
    ];

    private array $middlegames = [
        '6. Be2 e5 7. Nb3 Be7 8. O-O O-O 9. Be3 Be6 10. f3 Nbd7 11. Qd2 b5',
        '6. Re1 b5 7. Bb3 d6 8. c3 O-O 9. h3 Nb8 10. d4 Nbd7 11. Nbd2 Bb7',
        '6. Bxf6 Bxf6 7. e3 O-O 8. Bd3 c5 9. O-O Nc6 10. Rc1 cxd4 11. exd4 Nb4',
        '6. dxc5 Nxc5 7. Bb5+ Bd7 8. Be2 e5 9. O-O Be7 10. f4 exf4 11. Bxf4 O-O',
    ];

    private array $endgames = [
        '12. Rad1 Rc8 13. Rfe1 Qb6 14. Bf1 Rfd8 15. a3 Rac8 16. Qe2 Nf8',
        '12. a4 c5 13. d5 Nc4 14. Bxc4 bxc4 15. Nd2 Bg5 16. Nxc4 Bxc1',
        '12. Be2 Re8 13. Bf3 Be6 14. Qe2 Qb6 15. Rfd1 Rad8 16. Rac1 Nd5',
    ];

    public function definition(): array
    {
        $opening = $this->faker->randomElement($this->openings);
        $middle = $this->faker->randomElement($this->middlegames);
        $end = $this->faker->randomElement($this->endgames);
        $result = $this->faker->randomElement(GameResult::cases());

        $pgn = $opening['pgn'] . ' ' . $middle . ' ' . $end . ' ' . $result->value;

        return [
            'pgn' => $pgn,
            'user_id' => User::factory(),
            'white_player' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'black_player' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'result' => $result->value,
            'opening_name' => $opening['name'],
            'opening_eco' => $opening['eco'],
            'total_moves' => $this->faker->numberBetween(20, 60),
            'user_color' => $this->faker->randomElement(['white', 'black']),
            'is_analyzed' => $this->faker->boolean(40),
            'played_at' => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }
}
