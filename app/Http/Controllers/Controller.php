<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Šaha Analīzes Platformas API",
 *     description="REST API Šaha analīzes un mācību platformai (PKE kvalifikācijas darbs).
 *                  Visi autentificētie galapunkti izmanto Laravel Sanctum sesiju.",
 *     @OA\Contact(name="Ēriks Anisimovičs")
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="Lokālais izstrādes serveris"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="apiKey",
 *     in="cookie",
 *     name="laravel_session",
 *     description="Sesijas sīkdatne (Sanctum web guard)"
 * )
 *
 * @OA\Tag(name="Auth", description="Autentifikācija un reģistrācija")
 * @OA\Tag(name="Games", description="Šaha partiju CRUD un analīze")
 * @OA\Tag(name="Training", description="Personalizēti treniņu uzdevumi")
 * @OA\Tag(name="Users", description="Lietotāju pārvaldība")
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=@OA\Schema(type="array", @OA\Items(type="string"))
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Game",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=42),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="white_player", type="string", example="Magnus Carlsen"),
 *     @OA\Property(property="black_player", type="string", example="Hikaru Nakamura"),
 *     @OA\Property(property="result", type="string", enum={"1-0","0-1","1/2-1/2","*"}),
 *     @OA\Property(property="pgn", type="string"),
 *     @OA\Property(property="opening_name", type="string", nullable=true),
 *     @OA\Property(property="opening_eco", type="string", nullable=true, example="C60"),
 *     @OA\Property(property="user_color", type="string", enum={"white","black"}),
 *     @OA\Property(property="total_moves", type="integer", example=42),
 *     @OA\Property(property="is_analyzed", type="boolean"),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
abstract class Controller {}
