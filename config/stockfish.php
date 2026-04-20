<?php

return [
    'binary_path'   => env('STOCKFISH_PATH', '/usr/games/stockfish'),
    'default_depth' => (int) env('STOCKFISH_DEPTH', 18),
    'timeout'       => (int) env('STOCKFISH_TIMEOUT', 30),
    'max_depth'     => 25,
    'min_depth'     => 8,
];
