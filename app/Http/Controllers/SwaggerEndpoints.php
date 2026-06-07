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
     *         required={"san", "uci", "fen"},
     *         @OA\Property(property="san", type="string", example="Nf3", description="Gājiens SAN notācijā"),
     *         @OA\Property(property="uci", type="string", example="g1f3", description="Gājiens UCI notācijā"),
     *         @OA\Property(property="fen", type="string", example="rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1"),
     *         @OA\Property(property="is_game_over", type="boolean", nullable=true),
     *         @OA\Property(property="time_remaining", type="integer", nullable=true, description="Atlikušais laiks sekundēs"),
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

    /* ──────────────── Matchmaking & Live Game ───────────────── */

    /**
     * @OA\Post(
     *     path="/multiplayer/queue/join",
     *     tags={"Multiplayer"},
     *     summary="Pievienoties mačmēkinga rindai",
     *     description="Pievieno lietotāju ELO sakritības rindai. Rinda tiek glabāta keš atmiņā (Laravel Cache), jo tā ir īslaicīga — spēlētāji gaida tikai dažas sekundes līdz minūtei. Datubāzes tabula nebūtu piemērota šim mērķim.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="time_control", type="integer", enum={180, 300, 600, 900, 1800}, example=600),
     *     )),
     *     @OA\Response(response=200, description="Rindā pievienots",
     *         @OA\JsonContent(
     *             @OA\Property(property="in_queue", type="boolean"),
     *             @OA\Property(property="queue_count", type="integer"),
     *         )),
     *     @OA\Response(response=429, description="Pārsniegts pieprasījumu limits")
     * )
     */

    /**
     * @OA\Get(
     *     path="/multiplayer/queue/poll",
     *     tags={"Multiplayer"},
     *     summary="Pārbaudīt, vai atrasts pretinieks",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Statuss",
     *         @OA\JsonContent(
     *             @OA\Property(property="matched", type="boolean"),
     *             @OA\Property(property="in_queue", type="boolean"),
     *             @OA\Property(property="game", type="object", nullable=true),
     *         )),
     *     @OA\Response(response=429, description="Pārsniegts pieprasījumu limits (45/min)")
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/queue/leave",
     *     tags={"Multiplayer"},
     *     summary="Iziet no mačmēkinga rindas",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Izņemts no rindas")
     * )
     */

    /**
     * @OA\Get(
     *     path="/multiplayer/{id}",
     *     tags={"Multiplayer"},
     *     summary="Spēles stāvoklis (tikai dalībniekiem)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Spēles stāvoklis"),
     *     @OA\Response(response=403, description="Nav dalībnieks šajā spēlē"),
     *     @OA\Response(response=429, description="Pārsniegts pieprasījumu limits (60/min)")
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/{id}/resign",
     *     tags={"Multiplayer"},
     *     summary="Padoties tiešsaistes spēlē",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Padošanās reģistrēta — spēle beigusies"),
     *     @OA\Response(response=400, description="Spēle jau beigusies")
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/{id}/draw",
     *     tags={"Multiplayer"},
     *     summary="Piedāvāt, pieņemt vai noraidīt neizšķirtu",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"action"},
     *         @OA\Property(property="action", type="string", enum={"offer", "accept", "decline"}),
     *     )),
     *     @OA\Response(response=200, description="Darbība apstrādāta"),
     *     @OA\Response(response=400, description="Nederīga darbība")
     * )
     */

    /**
     * @OA\Post(
     *     path="/multiplayer/{id}/timeout",
     *     tags={"Multiplayer"},
     *     summary="Pieprasīt uzvaru pēc laika beigām",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Laika beigu uzvara reģistrēta"),
     *     @OA\Response(response=400, description="Pretiniekam vēl ir laiks")
     * )
     */

    /**
     * @OA\Get(
     *     path="/multiplayer/history",
     *     tags={"Multiplayer"},
     *     summary="Lietotāja tiešsaistes spēļu vēsture",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Spēļu vēsture")
     * )
     */

    /* ──────────────────── ELO & Stats ────────────────────────── */

    /**
     * @OA\Get(
     *     path="/elo/history",
     *     tags={"Games"},
     *     summary="ELO reitinga izmaiņu vēsture",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", maximum=100, example=20)),
     *     @OA\Response(response=200, description="ELO vēsture ielādēta")
     * )
     */

    /**
     * @OA\Get(
     *     path="/game/{id}/download",
     *     tags={"Games"},
     *     summary="Lejupielādēt partiju PGN formātā",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="PGN fails",
     *         @OA\MediaType(mediaType="application/x-chess-pgn",
     *             @OA\Schema(type="string"))),
     *     @OA\Response(response=403, description="Nav piekļuves")
     * )
     */

    /* ──────────────────── Training ───────────────────────────── */

    /**
     * @OA\Post(
     *     path="/training/generate/{gameId}",
     *     tags={"Training"},
     *     summary="Ģenerēt treniņu uzdevumus no partijas kļūdām",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="gameId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Uzdevumi ģenerēti"),
     *     @OA\Response(response=403, description="Nav piekļuves partijai"),
     *     @OA\Response(response=429, description="Pārsniegts limits (20/min)")
     * )
     */

    /**
     * @OA\Post(
     *     path="/training/submit/{sessionId}",
     *     tags={"Training"},
     *     summary="Iesniegt atbildi uz treniņa uzdevumu",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="sessionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"move"},
     *         @OA\Property(property="move", type="string", example="Nf3", description="Gājiens SAN notācijā"),
     *     )),
     *     @OA\Response(response=200, description="Atbilde pārbaudīta",
     *         @OA\JsonContent(
     *             @OA\Property(property="is_correct", type="boolean"),
     *         ))
     * )
     */

    /* ──────────────────── Friends ────────────────────────────── */

    /**
     * @OA\Get(
     *     path="/friends",
     *     tags={"Users"},
     *     summary="Draugu saraksts un gaidošie pieprasījumi",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Draugu saraksts")
     * )
     */

    /**
     * @OA\Post(
     *     path="/friends/add",
     *     tags={"Users"},
     *     summary="Nosūtīt drauga pieprasījumu",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"user_id"},
     *         @OA\Property(property="user_id", type="integer"),
     *     )),
     *     @OA\Response(response=200, description="Pieprasījums nosūtīts"),
     *     @OA\Response(response=422, description="Nevar pievienot sevi vai atkārtots pieprasījums")
     * )
     */

    /* ──────────────────── Game Import ────────────────────────── */

    /**
     * @OA\Post(
     *     path="/games/import",
     *     tags={"Games"},
     *     summary="Importēt partijas no Lichess vai Chess.com",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"username", "source"},
     *         @OA\Property(property="username", type="string", example="DrNykterstein"),
     *         @OA\Property(property="source", type="string", enum={"lichess", "chesscom"}),
     *         @OA\Property(property="max", type="integer", maximum=50, example=10),
     *     )),
     *     @OA\Response(response=200, description="Partijas importētas"),
     *     @OA\Response(response=429, description="Pārsniegts importa limits (5/min)")
     * )
     */
}
