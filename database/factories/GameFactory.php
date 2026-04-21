<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use App\Enums\GameResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * Complete legal PGN games that chess.js can parse without errors.
     * Each entry is a self-contained game with matching opening metadata.
     */
    private array $games = [
        [
            'name' => 'Sicilian Defense', 'eco' => 'B33',
            'pgn' => '1. e4 c5 2. Nf3 Nc6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 e5 6. Ndb5 d6 7. Bg5 a6 8. Na3 b5 9. Nd5 Be7 10. Bxf6 Bxf6 11. c3 O-O 12. Nc2 Bg5 13. a4 bxa4 14. Rxa4 a5 15. Bc4 Rb8 16. b3 Kh8 17. Nce3 g6 18. O-O f5 19. exf5 Bxf5 20. Nxf5 gxf5',
        ],
        [
            'name' => 'Ruy Lopez', 'eco' => 'C65',
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bb5 Nf6 4. O-O Be7 5. Re1 d6 6. c3 O-O 7. h3 Nb8 8. d4 Nbd7 9. Bc4 c6 10. a4 a5 11. Nbd2 Re8 12. Nf1 Bf8 13. Ng3 g6 14. b3 Bg7 15. Ba3 Nf8 16. Qd2 Ne6 17. Rad1 Nc7 18. d5 c5 19. Bb2 Bd7 20. Bc2 Qe7',
        ],
        [
            'name' => "Queen's Gambit Declined", 'eco' => 'D35',
            'pgn' => '1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. cxd5 exd5 5. Bg5 Be7 6. e3 O-O 7. Bd3 Nbd7 8. Nf3 Re8 9. Qc2 c6 10. O-O Nf8 11. Rab1 Bd6 12. b4 Ng6 13. a4 Bg4 14. b5 Rc8 15. Nd2 Bxe2 16. Nxe2 Ne4',
        ],
        [
            'name' => 'French Defense', 'eco' => 'C11',
            'pgn' => '1. e4 e6 2. d4 d5 3. Nc3 Nf6 4. e5 Nfd7 5. f4 c5 6. Nf3 Nc6 7. Be3 cxd4 8. Nxd4 Bc5 9. Qd2 O-O 10. O-O-O a6 11. Nb3 Be7 12. Bd3 b5 13. Qf2 Nb4 14. Bb1 b4 15. Ne2 a5 16. Ned4 Ba6',
        ],
        [
            'name' => 'Caro-Kann Defense', 'eco' => 'B12',
            'pgn' => '1. e4 c6 2. d4 d5 3. e5 Bf5 4. Nf3 e6 5. Be2 c5 6. Be3 Qb6 7. Nc3 Nc6 8. O-O cxd4 9. Bxd4 Nxd4 10. Nxd4 Bc5 11. Na4 Qa5 12. Nxc5 Qxc5 13. c3 Ne7 14. Bd3 Bxd3 15. Qxd3 O-O 16. Rac1 Qb6',
        ],
        [
            'name' => 'Italian Game', 'eco' => 'C54',
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d4 exd4 6. cxd4 Bb4+ 7. Bd2 Bxd2+ 8. Nbxd2 d5 9. exd5 Nxd5 10. Qb3 Nce7 11. O-O O-O 12. Rfe1 c6 13. a4 Qb6 14. Qc2 Bf5 15. Qc1 Nd3 16. Re2 N5f4 17. Ree1 Rfe8',
        ],
        [
            'name' => 'English Opening', 'eco' => 'A20',
            'pgn' => '1. c4 e5 2. g3 Nf6 3. Bg2 d5 4. cxd5 Nxd5 5. Nc3 Nb6 6. Nf3 Nc6 7. O-O Be7 8. a3 O-O 9. b4 Be6 10. Rb1 f6 11. d3 a5 12. b5 Nd4 13. Nd2 Qd7 14. e3 Nf5 15. Nce4 Nd5 16. Bb2 Rad8',
        ],
        [
            'name' => "King's Indian Defense", 'eco' => 'E97',
            'pgn' => '1. d4 Nf6 2. c4 g6 3. Nc3 Bg7 4. e4 d6 5. Nf3 O-O 6. Be2 e5 7. O-O Nc6 8. d5 Ne7 9. Ne1 Nd7 10. f3 f5 11. Be3 f4 12. Bf2 g5 13. Nd3 Ng6 14. c5 Nf6 15. Rc1 Rf7 16. cxd6 cxd6 17. Nb5 a6 18. Na3 b5',
        ],
        [
            'name' => 'Pirc Defense', 'eco' => 'B07',
            'pgn' => '1. e4 d6 2. d4 Nf6 3. Nc3 g6 4. f4 Bg7 5. Nf3 O-O 6. Bd3 Na6 7. O-O c5 8. d5 Nc7 9. a4 Rb8 10. Qe1 b6 11. Qh4 Nd7 12. f5 a6 13. Bg5 Re8 14. Rae1 b5 15. axb5 axb5',
        ],
        [
            'name' => 'Scotch Game', 'eco' => 'C45',
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. d4 exd4 4. Nxd4 Bc5 5. Nxc6 Qf6 6. Qf3 bxc6 7. Nd2 d6 8. Nb3 Bb6 9. a4 a5 10. Be2 Ne7 11. O-O O-O 12. Qg3 Ng6 13. f4 Qe7 14. Kh1 f5 15. exf5 Bxf5',
        ],
        [
            'name' => "Queen's Indian Defense", 'eco' => 'E15',
            'pgn' => '1. d4 Nf6 2. c4 e6 3. Nf3 b6 4. g3 Ba6 5. b3 Bb4+ 6. Bd2 Be7 7. Bg2 c6 8. Bc3 d5 9. Ne5 Nfd7 10. Nxd7 Nxd7 11. Nd2 O-O 12. O-O Rc8 13. e4 c5 14. Re1 dxe4 15. Nxe4 cxd4 16. Bd2 Bb7',
        ],
        [
            'name' => 'Nimzo-Indian Defense', 'eco' => 'E21',
            'pgn' => '1. d4 Nf6 2. c4 e6 3. Nc3 Bb4 4. Nf3 b6 5. Bg5 Bb7 6. e3 h6 7. Bh4 g5 8. Bg3 Ne4 9. Qc2 Bxc3+ 10. bxc3 d6 11. Bd3 Nxg3 12. hxg3 Nd7 13. e4 Qe7 14. O-O O-O-O 15. Rfe1 Nf6 16. Nd2 Rdg8',
        ],
        [
            'name' => 'Slav Defense', 'eco' => 'D11',
            'pgn' => '1. d4 d5 2. c4 c6 3. Nf3 Nf6 4. e3 Bg4 5. h3 Bxf3 6. Qxf3 e6 7. Nc3 Nbd7 8. Bd3 dxc4 9. Bxc4 Bd6 10. O-O O-O 11. Rd1 Qe7 12. e4 e5 13. Be3 Rfd8 14. Rac1 Bb4 15. dxe5 Nxe5 16. Qe2 Nxc4 17. Qxc4 Be7',
        ],
        [
            'name' => 'London System', 'eco' => 'D02',
            'pgn' => '1. d4 d5 2. Nf3 Nf6 3. Bf4 c5 4. e3 Nc6 5. Nbd2 e6 6. c3 Bd6 7. Bg3 O-O 8. Bd3 b6 9. Ne5 Bb7 10. f4 Ne7 11. O-O Nf5 12. Bf2 c4 13. Bc2 b5 14. Qe2 a5 15. g4 Nd6',
        ],
        [
            'name' => 'Catalan Opening', 'eco' => 'E01',
            'pgn' => '1. d4 Nf6 2. c4 e6 3. g3 d5 4. Bg2 dxc4 5. Qa4+ Nbd7 6. Qxc4 a6 7. Nf3 b5 8. Qc2 Bb7 9. O-O c5 10. dxc5 Bxc5 11. a4 b4 12. Nbd2 O-O 13. Nb3 Be7 14. Rd1 Qb6 15. Bf4 Rfd8',
        ],
        [
            'name' => 'Vienna Game', 'eco' => 'C28',
            'pgn' => '1. e4 e5 2. Nc3 Nf6 3. Bc4 Nc6 4. d3 Bb4 5. Nf3 d6 6. O-O Bxc3 7. bxc3 Na5 8. Bb3 Nxb3 9. axb3 O-O 10. Bg5 h6 11. Bh4 Re8 12. Re1 Be6 13. d4 Qd7 14. Nd2 Nh7 15. f4 exf4',
        ],
    ];

    public function definition(): array
    {
        $game = $this->faker->randomElement($this->games);
        $result = $this->faker->randomElement(GameResult::cases());

        $pgn = $game['pgn'] . ' ' . $result->value;
        $totalMoves = (int) ceil(substr_count($pgn, '.') / 1);
        // More accurate: count actual half-moves
        $cleaned = preg_replace('/\d+\./', '', $pgn);
        $cleaned = preg_replace('/(1-0|0-1|1\/2-1\/2|\*)/', '', $cleaned);
        $tokens = preg_split('/\s+/', trim($cleaned));
        $halfMoves = count(array_filter($tokens, fn($t) => !empty(trim($t))));
        $moveCount = (int) ceil($halfMoves / 2);

        return [
            'pgn'          => $pgn,
            'user_id'      => User::factory(),
            'white_player' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'black_player' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'result'       => $result->value,
            'opening_name' => $game['name'],
            'opening_eco'  => $game['eco'],
            'total_moves'  => $moveCount,
            'user_color'   => $this->faker->randomElement(['white', 'black']),
            'is_analyzed'  => $this->faker->boolean(40),
            'played_at'    => $this->faker->dateTimeBetween('-90 days', 'now'),
        ];
    }
}
