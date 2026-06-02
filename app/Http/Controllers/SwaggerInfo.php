<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Šaha Analīzes Platforma API",
 *     description="REST API for the Chess Analysis & Learning Platform (PKE 2026). Provides game management, Stockfish analysis, multiplayer, training, achievements, and user management endpoints. All authenticated routes use Laravel Sanctum session cookies.",
 *     @OA\Contact(name="Ēriks Anisimovičs", email="eriks@example.com"),
 *     @OA\License(name="MIT")
 * )
 *
 * @OA\Server(url="http://localhost/api", description="Local development")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     description="Laravel Sanctum session cookie or Bearer token"
 * )
 *
 * @OA\Tag(name="Auth", description="Registration, login, logout, password reset, 2FA")
 * @OA\Tag(name="Games", description="Game CRUD, analysis, sharing, export")
 * @OA\Tag(name="Multiplayer", description="Real-time multiplayer via WebSocket")
 * @OA\Tag(name="Training", description="Error-based training and progress tracking")
 * @OA\Tag(name="Users", description="Profile, settings, GDPR deletion")
 * @OA\Tag(name="Content", description="Openings, lessons, daily puzzles, achievements")
 *
 * @OA\Schema(
 *     schema="Game",
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="pgn", type="string", example="1. e4 e5 2. Nf3 Nc6 1-0"),
 *     @OA\Property(property="white_player", type="string", example="Player1"),
 *     @OA\Property(property="black_player", type="string", example="Player2"),
 *     @OA\Property(property="result", type="string", enum={"1-0", "0-1", "1/2-1/2", "*"}),
 *     @OA\Property(property="opening_name", type="string", example="Italian Game"),
 *     @OA\Property(property="opening_eco", type="string", example="C50"),
 *     @OA\Property(property="total_moves", type="integer", example=25),
 *     @OA\Property(property="is_analyzed", type="boolean"),
 *     @OA\Property(property="user_color", type="string", enum={"white", "black"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="GameMove",
 *     @OA\Property(property="move_number", type="integer", example=5),
 *     @OA\Property(property="color", type="string", enum={"white", "black"}),
 *     @OA\Property(property="move_san", type="string", example="Nf3"),
 *     @OA\Property(property="eval_before", type="number", format="float", example=0.3),
 *     @OA\Property(property="eval_after", type="number", format="float", example=0.1),
 *     @OA\Property(property="classification", type="string", enum={"best", "excellent", "good", "inaccuracy", "mistake", "blunder"}),
 *     @OA\Property(property="error_category", type="string", enum={"tactical", "positional", "opening", "endgame"}, nullable=true),
 *     @OA\Property(property="explanation", type="string", nullable=true),
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string", example="eriks"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="elo_rating", type="integer", example=1200),
 *     @OA\Property(property="locale", type="string", enum={"lv", "en"}),
 *     @OA\Property(property="dark_mode", type="boolean"),
 *     @OA\Property(property="two_factor_enabled", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 * )
 *
 * @OA\Schema(
 *     schema="ApiSuccess",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="payload", type="object"),
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object",
 *         @OA\AdditionalProperties(type="array", @OA\Items(type="string"))
 *     ),
 * )
 */
class SwaggerInfo {}
