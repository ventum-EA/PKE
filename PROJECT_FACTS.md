# Project Technical Facts

These are code-verified facts extracted directly from the codebase. Use them to cross-check any claims in the documentation. If the documentation states something different from what's listed here, the documentation is wrong.

---

## Tech Stack (from composer.json + package.json)

**Backend:** Laravel 12, PHP 8.2, MySQL
**Frontend:** Vue 3.5, Vite 7, Pinia 3, Vue Router 4, Vue I18n 10, chess.js 1.0-beta.8
**Styling:** Tailwind CSS 4
**Real-time:** Laravel Reverb (WebSocket), Laravel Echo, Pusher JS client
**Auth:** Laravel Sanctum (session-based), Spatie Laravel Permission (RBAC), pragmarx/google2fa-laravel (2FA)
**API docs:** darkaonline/l5-swagger (OpenAPI 3.0)
**Data layer:** Spatie Laravel Data (DTOs), Spatie Laravel Query Builder
**Testing:** PHPUnit 11, Vitest 2, Playwright, Mockery
**CI/CD:** GitHub Actions (3 jobs: php-tests with MySQL 8.4, js-tests + ESLint + Vite build, php-lint)
**Deployment:** Docker (PHP 8.2-Apache, MySQL, Stockfish binary, Node.js 20)
**Other:** html2canvas, jsPDF, ryanhs/chess (server-side PGN validation), laravel/pint (code style)

---

## Architecture Patterns

- **Repository Pattern:** `GameRepositoryInterface` → `GameRepository`, `GameMoveRepositoryInterface` → `GameMoveRepository`, bound in `RepositoryServiceProvider`
- **Service Layer:** `GameService`, `EloService`, `TrainingService`, `MultiplayerService`, `MatchmakingService`, `RecommendationService`, `AchievementService`, `PatternDetectionService`, `GameImportService`, `StockfishService`, `UserService`
- **DTOs:** `GameData`, `UserData`, `GameMoveData` (Spatie Data)
- **Enums (PHP 8.1 backed):** `MoveClassification` (BEST/EXCELLENT/GOOD/INACCURACY/MISTAKE/BLUNDER), `GameResult`, `ErrorCategory`, `UserRole`
- **API Resources:** `GameResource`, `GameMoveResource`, `UserResource`
- **Form Requests:** `StoreGameRequest`, `UpdateGameRequest`, `CreateMultiplayerGameRequest`, `SaveMovesRequest`, `UpdateSettingsRequest`
- **Events (WebSocket):** `GameMoveEvent`, `GameEndEvent`, `DrawOfferEvent`, `MatchFoundEvent`, `FriendStatusEvent`, `UserNotificationEvent`
- **Jobs:** `AnalyzeGameJob` (server-side Stockfish analysis via Laravel Queue)
- **Notifications:** `WeeklyDigestNotification`, `GameInviteNotification`, `FriendRequestNotification`
- **Console Commands:** `SendWeeklyDigest`, `ImportLichessPuzzles`
- **Middleware:** `Ensure2FAVerified` (blocks non-2FA-verified users from protected routes)
- **Strict types:** 96/96 PHP files under `app/` use `declare(strict_types=1)` — 100% coverage

---

## API Routes (from routes/api.php — 65 routes total)

### Public (no auth)
- `POST /login` (throttle: 10/min)
- `POST /register` (throttle: 3/10min)
- `POST /forgot-password` (throttle: 3/10min, guest)
- `POST /reset-password` (throttle: 5/10min, guest)
- `GET /email/verify/{id}/{hash}` (signed)
- `POST /email/resend` (throttle: 3/min)
- `GET /shared/{token}` (public game sharing)
- `GET /openings` / `GET /openings/{opening}`
- `GET /lessons` / `GET /lessons/{lesson}`

### Authenticated (auth:sanctum)
- `POST /logout`
- `GET /user` / `PUT /user/settings` / `PUT /user/profile` / `PUT /user/password` / `DELETE /user/me`
- `POST /2fa/setup` / `POST /2fa/confirm` / `POST /2fa/verify` / `POST /2fa/disable` / `POST /2fa/recovery-codes`

### Admin (auth:sanctum + can:manage users)
- `GET /users` / `GET /user/{id}` / `POST /user/create` / `PUT /user/modify` / `DELETE /user/{id}`
- `GET /admin/stats` / `GET /admin/games` / `PUT /admin/user/{id}/role` / `POST /admin/user/{id}/reset-elo` / `DELETE /admin/game/{id}`
- `GET /audit-logs`

### Games (auth:sanctum)
- `GET /games` / `POST /game/create` / `GET /game/{id}` / `PUT /game/modify` / `DELETE /game/{id}`
- `POST /game/{id}/analyze` (throttle: 10/min)
- `GET /game/{id}/moves` / `POST /game/{id}/moves`
- `POST /game/{id}/share` / `GET /game/{id}/download`
- `GET /games/stats`

### Training (auth:sanctum)
- `POST /training/generate/{gameId}` (throttle: 20/min)
- `POST /training/openings` (throttle: 20/min)
- `POST /training/submit/{sessionId}`
- `POST /training/complete`
- `GET /training/progress` / `GET /training/progress-report`

### Content & Features (auth:sanctum)
- `GET /recommendations`
- `GET /elo/history`
- `POST /openings/{opening}/progress` / `POST /lessons/{lesson}/progress`
- `GET /achievements` / `POST /achievements/check`
- `GET /daily-puzzle` / `POST /daily-puzzle/submit` / `GET /daily-puzzle/history`
- `GET /game/{id}/annotations` / `POST /game/{id}/annotations` / `DELETE /game/{id}/annotations/{moveIndex}`

### Multiplayer (auth:sanctum)
- `POST /multiplayer/create` / `POST /multiplayer/join/{token}`
- `POST /multiplayer/queue/join` / `POST /multiplayer/queue/leave`
- `GET /multiplayer/queue/poll` (throttle: 45/min)
- `GET /multiplayer/{id}` (throttle: 60/min)
- `POST /multiplayer/{id}/move` (throttle: 30/min)
- `POST /multiplayer/{id}/resign` / `POST /multiplayer/{id}/draw` / `POST /multiplayer/{id}/timeout`
- `GET /multiplayer/history`

### Social & Import (auth:sanctum)
- `GET /friends` / `POST /friends/add` / `POST /friends/{id}/accept` / `DELETE /friends/{id}`
- `POST /games/import` (throttle: 5/min)
- `GET /patterns` / `POST /patterns/detect`
- `GET /puzzle-bank/next` / `POST /puzzle-bank/{id}/submit` / `GET /puzzle-bank/stats` / `GET /puzzle-bank/themes`

---

## Database (22 migrations)

| Migration | Tables created/modified |
|-----------|----------------------|
| `0001_01_01_000000_create_users_table` | users, password_reset_tokens, sessions |
| `0001_01_01_000001_create_cache_table` | cache, cache_locks |
| `2026_01_28_000000_create_permission_tables` | permissions, roles, model_has_permissions, model_has_roles, role_has_permissions |
| `2026_03_01_000001_create_games_table` | games (id, user_id FK cascade, pgn text, white/black_player, result enum, opening_name/eco, total_moves, user_color enum, is_analyzed, share_token unique, played_at, timestamps, softDeletes) |
| `2026_03_01_000002_create_game_moves_table` | game_moves |
| `2026_03_01_000003_create_training_sessions_table` | training_sessions |
| `2026_03_02_000001_create_openings_table` | openings |
| `2026_03_02_000002_create_lessons_table` | lessons, lesson_puzzles |
| `2026_03_02_000003_create_progress_tables` | user_opening_progress, user_lesson_progress |
| `2026_03_15_000001_create_audit_logs_table` | audit_logs |
| `2026_03_20_000001_add_two_factor_to_users_table` | adds 2FA columns to users |
| `2026_04_08_000000_add_accessibility_to_users` | adds dark_mode, sound_enabled, font_size, high_contrast to users |
| `2026_04_19_000001_create_elo_history_table` | elo_history |
| `2026_04_19_000002_add_preferences_to_users` | adds locale, preferred_color, etc. to users |
| `2026_04_25_000001_create_achievements_tables` | achievements, user_achievements |
| `2026_04_25_000002_create_daily_puzzles_tables` | daily_puzzles, daily_puzzle_attempts |
| `2026_04_25_000003_create_game_annotations_table` | game_annotations |
| `2026_04_28_000001_create_multiplayer_tables` | online_games, online_game_moves |
| `2026_05_01_000001_add_board_customization_to_users` | adds board_theme, piece_style, etc. to users |
| `2026_05_02_000001_add_performance_indexes` | adds composite indexes on game_annotations, daily_puzzle_attempts, elo_history |
| `2026_05_05_000001_create_data_enrichment_tables` | puzzle_bank, puzzle_attempts, friendships, game_imports, user_patterns |
| `2026_05_20_192116_drop_role_column_from_users` | drops legacy role column |

---

## ELO System Constants (from EloService.php)

| Constant | Value |
|----------|-------|
| K_FACTOR_NEW (< 30 games) | 40 |
| K_FACTOR_DEFAULT (30+ games) | 20 |
| K_FACTOR_HIGH (ELO ≥ 2400) | 10 |
| NEW_PLAYER_THRESHOLD | 30 games |
| HIGH_RATING_THRESHOLD | 2400 |
| MIN_ELO | 100 |
| MAX_ELO | 3000 |
| TRAINING_BASE_ELO per correct | 3 |
| TRAINING_ACCURACY_BONUS (≥80%) | +5 |
| TRAINING_MAX_ELO_GAIN | 15 |
| Expected score formula | E(A) = 1 / (1 + 10^((Rb - Ra) / 400)) |

**Stockfish skill → ELO mapping:** 0→400, 1→500, 2→600, 3→700, 4→850, 5→1000, 6→1150, 7→1300, 8→1400, 9→1500, 10→1600, 11→1700, 12→1800, 13→1900, 14→2000, 15→2100, 16→2200, 17→2350, 18→2500, 19→2650, 20→2800

**Training difficulty multipliers:** easy=0.5, medium=1.0, hard=1.5
**Training category multipliers:** tactical=1.2, positional=1.0, opening=0.8, endgame=1.1

**Three ELO processing methods:**
1. `processGameResult` — vs Stockfish, symmetric win/loss
2. `processTrainingResult` — never loses ELO, only gains (capped at +15)
3. `processMultiplayerResult` — symmetric, both players updated

---

## Move Classification Thresholds (from chess.js)

| Eval diff (pawns) | Classification |
|-------------------|----------------|
| ≤ 0.05 | best |
| ≤ 0.15 | excellent |
| ≤ 0.35 | good |
| ≤ 0.9 | inaccuracy |
| ≤ 2.5 | mistake |
| > 2.5 | blunder |

**Error categories:** tactical, positional, opening, endgame (assigned based on game phase and FEN analysis)

---

## Test Counts (verified by grep)

| Category | Files | Tests |
|----------|-------|-------|
| PHP Feature tests | 16 | 150 |
| PHP Unit tests | 3 | 9 (EloCalculation: 9, GameService: 3, ExplanationGenerator: 9 — total 21) |
| JS Unit (Vitest) | 8 | 147 |
| E2E (Playwright) | 3 | 15 |
| **Total** | **30** | **321** |

**PHP test files:** AuthTest, GameControllerTest, GameTest, MultiplayerTest, TrainingTest, FriendTest, AchievementTest, DailyPuzzleTest, AnnotationTest, PasswordTest, PasswordResetTest, AccountDeletionTest, UserManagementTest, UserProfileTest, RateLimitTest, ServerAnalysisJobTest, EloCalculationTest, GameServiceTest, ExplanationGeneratorTest

**JS test files:** chess.test.js (49), chess-integration.test.js (25), stockfish.test.js (24), composables.test.js (15), openings.test.js (11), ChessBoard.test.js (9), useLocalized.test.js (7), new-composables.test.js (7)

**E2E test files:** auth.spec.js (4), play-navigation.spec.js (7), game-analysis.spec.js (4)

**CI/CD (GitHub Actions):** 3 jobs — php-tests (MySQL 8.4 service, PHPUnit parallel), js-tests (Vitest + ESLint + Vite build), php-lint (PHP syntax check)

---

## Matchmaking Implementation

The matchmaking queue (`joinQueue`, `leaveQueue`, `pollQueue`, `findMatch`) uses **Laravel Cache** (in-memory or Redis depending on config), NOT a database table. This is a deliberate architectural decision: matchmaking entries are ephemeral (typically last seconds to minutes while a player waits for a match) and do not need persistence. When a match is found, the resulting game IS stored in the `online_games` database table.

---

## Analysis Flow

Two independent analysis paths:

1. **Client-side (WASM):** Stockfish compiled to WebAssembly runs in the user's browser. The frontend analyzes each move and sends results to `POST /game/{id}/moves` which stores them in `game_moves` table. This is the fast, default path.

2. **Server-side (Queue):** When `POST /game/{id}/analyze?server=true` is called, it dispatches `AnalyzeGameJob` to the Laravel Queue. The job runs Stockfish binary on the server for deeper analysis (higher depth). Results overwrite the stored moves.

The `analyze` endpoint with `server=false` (default) simply returns already-stored analysis results from the database — it does NOT run any analysis itself.

---

## Controllers (21 total)

GameController, MultiplayerController, TrainingController, UserController, AdminController, AchievementController, AnnotationController, DailyPuzzleController, FriendController, GameImportController, LessonController, OpeningController, PuzzleBankController, RecommendationController, AuthenticatedSessionController, RegisteredUserController, NewPasswordController, PasswordController, PasswordResetLinkController, TwoFactorController, SwaggerEndpoints (annotation-only)

---

## User Model Fields

**Core:** id, name, email, password, elo_rating, preferred_color, locale
**Accessibility:** dark_mode, sound_enabled, font_size, high_contrast
**2FA:** two_factor_enabled, two_factor_secret (hidden), two_factor_recovery_codes (hidden), two_factor_confirmed_at
**Board:** board_coordinates, move_confirmation, auto_queen, board_theme, piece_style, animation_speed
**Game:** default_difficulty, show_elo_opponent
**Email prefs:** email_friend_requests, email_game_invites, email_weekly_digest
**Roles:** Spatie Permission (user, admin)
