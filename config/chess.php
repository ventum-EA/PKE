<?php

declare(strict_types=1);

/*
 |--------------------------------------------------------------------------
 | Chess Platform Configuration
 |--------------------------------------------------------------------------
 |
 | Centralized constants for the chess platform. All "magic numbers"
 | scattered throughout services and controllers should be defined here
 | so that thresholds can be tuned in one place.
 |
 */

return [

    /*
     |--------------------------------------------------------------------------
     | Elo rating
     |--------------------------------------------------------------------------
     */
    'elo' => [
        'default'        => env('CHESS_DEFAULT_ELO', 1200),
        'min'            => 100,
        'max'            => 3000,
        'min_change'     => 1,
        'max_change'     => 50,
        'k_factor'       => 32,    // Elo K-factor for ranked games
        'k_factor_new'   => 40,    // K-factor for users with < 20 games
        'provisional_games' => 20,
    ],

    /*
     |--------------------------------------------------------------------------
     | Stockfish engine
     |--------------------------------------------------------------------------
     */
    'stockfish' => [
        // Browser side (WASM) — both file path and analysis depth
        'wasm_path'      => '/stockfish.wasm',
        'default_depth'  => 15,
        'analysis_depth' => env('STOCKFISH_DEPTH', 18),
        'max_depth'      => 22,
        'min_depth'      => 1,

        // Server side (native binary) — used for deep analysis via queue
        'binary_path'    => env('STOCKFISH_BINARY', '/usr/games/stockfish'),
        'timeout'        => env('STOCKFISH_TIMEOUT', 30),

        // Play vs Stockfish — UCI skill level range
        'min_skill'      => 0,
        'max_skill'      => 20,
        'default_skill'  => 10,
        'min_move_time'  => 500,    // ms
        'max_move_time'  => 5000,   // ms
        'default_move_time' => 1500,
    ],

    /*
     |--------------------------------------------------------------------------
     | Multiplayer
     |--------------------------------------------------------------------------
     */
    'multiplayer' => [
        'abandon_seconds'  => 300,     // 5 minutes of inactivity → can be claimed
        'invite_token_length' => 32,
        'time_controls'    => [180, 300, 600, 900, 1800],  // 3, 5, 10, 15, 30 min
        'default_time_control' => 600,                      // 10 min
        'queue_poll_ms'    => 2000,
    ],

    /*
     |--------------------------------------------------------------------------
     | Move classification thresholds (pawn-equivalent losses)
     |
     | Aligned with chess.com and lichess analysis standards.
     |--------------------------------------------------------------------------
     */
    'classification' => [
        'best_threshold'       => 0.05,
        'excellent_threshold'  => 0.15,
        'good_threshold'       => 0.35,
        'inaccuracy_threshold' => 0.90,
        'mistake_threshold'    => 2.50,
        // Anything above mistake_threshold is a blunder
    ],

    /*
     |--------------------------------------------------------------------------
     | Game phase detection
     |--------------------------------------------------------------------------
     */
    'phases' => [
        'opening_piece_threshold' => 12,
        'opening_half_move_max'   => 24,
        'endgame_piece_threshold' => 10,
    ],

    /*
     |--------------------------------------------------------------------------
     | Imports (Lichess, Chess.com)
     |--------------------------------------------------------------------------
     */
    'import' => [
        'max_games_per_import' => 50,
        'lichess_api_url'      => 'https://lichess.org/api/games/user',
        'chesscom_api_url'     => 'https://api.chess.com/pub/player',
        'timeout'              => 30,
    ],

    /*
     |--------------------------------------------------------------------------
     | Pagination
     |--------------------------------------------------------------------------
     */
    'pagination' => [
        'games_per_page'   => 12,
        'puzzles_per_page' => 20,
        'lessons_per_page' => 12,
    ],

    /*
     |--------------------------------------------------------------------------
     | Session
     |--------------------------------------------------------------------------
     */
    'session' => [
        'max_active_sessions' => 200,   // soft target for capacity planning
        'idle_timeout_minutes' => 120,
    ],

];
