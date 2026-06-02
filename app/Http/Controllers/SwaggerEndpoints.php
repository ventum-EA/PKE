<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Supplementary OpenAPI annotations for endpoints not yet annotated inline.
 * l5-swagger scans all @OA blocks project-wide — this file adds the remaining
 * key endpoints in one place for maintainability.
 *
 * Already documented inline: GET /games, GET /game/{id}, POST /login, POST /logout
 */
class SwaggerEndpoints
{
    /* ───────────────────────── Auth ───────────────────────── */

    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Jauna lietotāja reģistrācija",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email", "password", "password_confirmation"},
     *         @OA\Property(property="name", type="string", example="eriks", description="Unikāls, 3–20 rakstzīmes"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="password_confirmation", type="string"),
     *     )),
     *     @OA\Response(response=201, description="Reģistrācija veiksmīga",
     *         @OA\JsonContent(@OA\Property(property="user", ref="#/components/schemas/User"))),
     *     @OA\Response(response=422, description="Validācijas kļūda", @OA\JsonContent(ref="#/components/schemas/ValidationError"))
     * )
     */

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     tags={"Auth"},
     *     summary="Paroles atjaunošanas pieprasījums",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"email"},
     *         @OA\Property(property="email", type="string", format="email"),
     *     )),
     *     @OA\Response(response=200, description="Ja konts eksistē, e-pasts nosūtīts"),
     *     @OA\Response(response=429, description="Pārsniegts pieprasījumu limits")
     * )
     */

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     tags={"Auth"},
     *     summary="Paroles atjaunošana ar marķieri",
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"token", "email", "password", "password_confirmation"},
     *         @OA\Property(property="token", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", minLength=8),
     *         @OA\Property(property="password_confirmation", type="string"),
     *     )),
     *     @OA\Response(response=200, description="Parole atjaunināta"),
     *     @OA\Response(response=422, description="Nederīgs marķieris vai dati")
     * )
     */

    /* ───────────────────────── User ───────────────────────── */

    /**
     * @OA\Get(
     *     path="/user",
     *     tags={"Users"},
     *     summary="Pašreizējais autentificētais lietotājs",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Lietotāja profils",
     *         @OA\JsonContent(@OA\Property(property="user", ref="#/components/schemas/User"))),
     *     @OA\Response(response=401, description="Neautorizēts")
     * )
     */

    /**
     * @OA\Put(
     *     path="/user/settings",
     *     tags={"Users"},
     *     summary="Atjaunināt lietotāja iestatījumus (tēma, skaņa, valoda u.c.)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="dark_mode", type="boolean"),
     *         @OA\Property(property="locale", type="string", enum={"lv", "en"}),
     *         @OA\Property(property="sound_enabled", type="boolean"),
     *         @OA\Property(property="board_theme", type="string", enum={"classic", "brown", "blue", "green", "purple", "high_contrast"}),
     *         @OA\Property(property="piece_style", type="string", enum={"standard", "neo", "alpha", "medieval"}),
     *         @OA\Property(property="default_difficulty", type="integer", minimum=0, maximum=20),
     *     )),
     *     @OA\Response(response=200, description="Iestatījumi saglabāti",
     *         @OA\JsonContent(@OA\Property(property="user", ref="#/components/schemas/User"))),
     *     @OA\Response(response=422, description="Validācijas kļūda")
     * )
     */

    /**
     * @OA\Put(
     *     path="/user/profile",
     *     tags={"Users"},
     *     summary="Atjaunināt lietotāja profilu (vārds, e-pasts)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name", "email"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *     )),
     *     @OA\Response(response=200, description="Profils atjaunināts"),
     *     @OA\Response(response=422, description="Validācijas kļūda")
     * )
     */

    /**
     * @OA\Delete(
     *     path="/user/me",
     *     tags={"Users"},
     *     summary="GDPR konta dzēšana — neatgriezeniski dzēš visus datus",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"password"},
     *         @OA\Property(property="password", type="string", description="Pašreizējā parole apstiprināšanai"),
     *     )),
     *     @OA\Response(response=200, description="Konts dzēsts"),
     *     @OA\Response(response=422, description="Nepareiza parole")
     * )
     */

    /* ───────────────────────── Games ─────────────────────── */

    /**
     * @OA\Post(
     *     path="/game/create",
     *     tags={"Games"},
     *     summary="Augšupielādēt jaunu partiju (PGN)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"pgn"},
     *         @OA\Property(property="pgn", type="string", description="PGN teksts"),
     *         @OA\Property(property="user_color", type="string", enum={"white", "black"}),
     *     )),
     *     @OA\Response(response=201, description="Partija saglabāta",
     *         @OA\JsonContent(@OA\Property(property="game", ref="#/components/schemas/Game"))),
     *     @OA\Response(response=422, description="Nederīgs PGN formāts")
     * )
     */

    /**
     * @OA\Delete(
     *     path="/game/{id}",
     *     tags={"Games"},
     *     summary="Dzēst partiju",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Partija dzēsta"),
     *     @OA\Response(response=403, description="Nav piekļuves"),
     *     @OA\Response(response=404, description="Partija nav atrasta")
     * )
     */

    /**
     * @OA\Post(
     *     path="/game/{id}/analyze",
     *     tags={"Games"},
     *     summary="Uzsākt Stockfish analīzi (klienta vai servera puse)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="depth", type="integer", minimum=1, maximum=25, example=15),
     *         @OA\Property(property="server", type="boolean", description="Ja true — servera puses dziļā analīze caur Laravel Queue"),
     *     )),
     *     @OA\Response(response=200, description="Analīze pabeigta vai ieplānota"),
     *     @OA\Response(response=429, description="Pārsniegts analīžu limits")
     * )
     */

    /**
     * @OA\Post(
     *     path="/game/{id}/moves",
     *     tags={"Games"},
     *     summary="Saglabāt klienta puses (WASM) analīzes rezultātus",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"moves"},
     *         @OA\Property(property="moves", type="array", maxItems=600,
     *             @OA\Items(ref="#/components/schemas/GameMove")),
     *     )),
     *     @OA\Response(response=200, description="Analīzes dati saglabāti"),
     *     @OA\Response(response=422, description="Validācijas kļūda")
     * )
     */

    /**
     * @OA\Post(
     *     path="/game/{id}/share",
     *     tags={"Games"},
     *     summary="Ģenerēt kopīgošanas saiti",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Saite izveidota",
     *         @OA\JsonContent(@OA\Property(property="share_url", type="string", example="http://localhost/shared/abc123xyz"))),
     *     @OA\Response(response=403, description="Nav piekļuves")
     * )
     */

    /**
     * @OA\Get(
     *     path="/shared/{token}",
     *     tags={"Games"},
     *     summary="Skatīt kopīgotu partiju (publisks, bez autentifikācijas)",
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string", minLength=32)),
     *     @OA\Response(response=200, description="Partija ielādēta",
     *         @OA\JsonContent(@OA\Property(property="game", ref="#/components/schemas/Game"))),
     *     @OA\Response(response=404, description="Nepareizs vai noildzis marķieris")
     * )
     */

    /**
     * @OA\Get(
     *     path="/games/stats",
     *     tags={"Games"},
     *     summary="Lietotāja spēļu statistika (uzvaras, atklātnes, kļūdu sadalījums)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Statistika ielādēta",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_games", type="integer"),
     *             @OA\Property(property="wins", type="integer"),
     *             @OA\Property(property="losses", type="integer"),
     *             @OA\Property(property="draws", type="integer"),
     *             @OA\Property(property="win_rate", type="number", format="float"),
     *         ))
     * )
     */

    /* ─────────────────── Multiplayer ─────────────────────── */

    /**
     * @OA\Post(
     *     path="/multiplayer/create",
     *     tags={"Multiplayer"},
     *     summary="Izveidot uzaicinājuma spēli",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="color", type="string", enum={"white", "black", "random"}),
     *         @OA\Property(property="time_control", type="integer", enum={180, 300, 600, 900, 1800}),
     *         @OA\Property(property="rated", type="boolean"),
     *     )),
     *     @OA\Response(response=201, description="Spēle izveidota",
     *         @OA\JsonContent(
     *             @OA\Property(property="game_id", type="integer"),
     *             @OA\Property(property="invite_token", type="string"),
     *             @OA\Property(property="invite_url", type="string"),
     *         ))
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/join/{token}",
     *     tags={"Multiplayer"},
     *     summary="Pievienoties spēlei ar uzaicinājuma marķieri",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="token", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Veiksmīgi pievienojies"),
     *     @OA\Response(response=404, description="Spēle nav atrasta vai jau sākusies")
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/{id}/move",
     *     tags={"Multiplayer"},
     *     summary="Veikt gājienu tiešsaistes spēlē",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"from", "to"},
     *         @OA\Property(property="from", type="string", example="e2"),
     *         @OA\Property(property="to", type="string", example="e4"),
     *         @OA\Property(property="promotion", type="string", enum={"q", "r", "b", "n"}, nullable=true),
     *     )),
     *     @OA\Response(response=200, description="Gājiens pieņemts"),
     *     @OA\Response(response=422, description="Nelikumīgs gājiens"),
     *     @OA\Response(response=429, description="Pārsniegts gājienu limits")
     * )
     */

    /* ─────────────────── Training & Content ──────────────── */

    /**
     * @OA\Get(
     *     path="/training/progress",
     *     tags={"Training"},
     *     summary="Treniņu progresijas dati",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Progresija ielādēta")
     * )
     */

    /**
     * @OA\Get(
     *     path="/daily-puzzle",
     *     tags={"Content"},
     *     summary="Šodienas uzdevums",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Uzdevums ielādēts",
     *         @OA\JsonContent(
     *             @OA\Property(property="fen", type="string"),
     *             @OA\Property(property="correct_move", type="string"),
     *             @OA\Property(property="difficulty", type="integer"),
     *         ))
     * )
     */

    /**
     * @OA\Get(
     *     path="/achievements",
     *     tags={"Content"},
     *     summary="Lietotāja sasniegumu saraksts",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Sasniegumi ielādēti")
     * )
     */
}
