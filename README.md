# ♔ Šaha analīzes un mācību platforma / Chess Analysis & Learning Platform

**PKE 2026 — Ēriks Anisimovičs · Liepājas Valsts Tehnikums**

Tīmekļa platforma šaha spēlētājiem, kas papildina esošās šaha vietnes ar uz mācīšanos vērstu spēļu analīzi. Lietotājs augšupielādē savas partijas, saņem kļūdu skaidrojumus latviešu valodā un trenējas no savām vājajām pozīcijām.

A web platform for chess players that complements existing chess sites with learning-focused game analysis. Users upload their games, receive error explanations in Latvian, and train from their own weak positions.

**Stack:** Laravel 12 · Vue 3 (Composition API) · Pinia · Tailwind CSS 4 · chess.js · Stockfish WASM · Laravel Reverb · MySQL 8.4

---

## Galvenās funkcijas / Key Features

- 🎯 **Partijas analīze / Game Analysis** — Stockfish (client WASM + server deep analysis via Laravel Queue)
- 📚 **Divvalodu skaidrojumi / Bilingual Explanations** — error explanations in Latvian and English (tactical, positional, opening, endgame)
- 🎓 **120+ ECO atklātnes / Opening Library** — with trainable moves and bilingual comments
- 🧩 **Personalizēti treniņi / Personalized Training** — generated from the player's own mistakes
- ♟️ **Spēle pret dzinēju / Play vs Engine** — difficulty 0–20, corresponding to ~400–2800 ELO
- 👥 **Daudzspēlētāju režīms / Multiplayer** — real-time via Laravel Reverb WebSocket with ELO tracking
- 🏆 **Sasniegumi / Achievements** — daily puzzles, friend system, leaderboards
- 🔒 **Drošība / Security** — TOTP 2FA, password reset, GDPR account deletion
- 🌙 **Tēmas / Themes** — full dark and light theme with CSS variable overrides
- 🌍 **i18n** — Latvian and English UI (1303 locale keys), bilingual puzzles

---

## Projekta struktūra / Project Structure

```
app/
├── Http/Controllers/     ← 21 controllers (GameController, MultiplayerController, …)
├── Services/             ← 11 business logic services (GameService, MultiplayerService, …)
├── Repositories/         ← Data access layer with Spatie Query Builder
├── Models/               ← 21 Eloquent models (User, Game, GameMove, OnlineGame, …)
├── Data/                 ← Spatie Data DTOs (GameData, GameMoveData, UserData)
├── Enums/                ← MoveClassification, ErrorCategory, GameResult, UserRole
├── Support/              ← ChessAnalyzer, ExplanationGenerator, PgnParser, ApiResponse
├── Events/               ← 6 broadcast events (multiplayer + notifications)
├── Jobs/                 ← AnalyzeGameJob (server-side Stockfish)
└── Notifications/        ← FriendRequest, GameInvite, WeeklyDigest

resources/js/
├── pages/                ← 22 route pages
├── components/           ← 25 reusable components (ChessBoard, GameAnalysis, …)
├── composables/          ← Reusable logic (useNotification, useLocalized, useWebSocket, …)
├── services/             ← Client services (api, chess, stockfish, openings)
├── stores/               ← Pinia stores (auth, games)
└── locales/              ← lv.json + en.json (1303 keys each)

tests/
├── Feature/              ← 16 PHPUnit integration test files
├── Unit/                 ← 3 PHPUnit unit test files
└── js/                   ← 6 Vitest frontend test files
```

---

## Iestatīšana / Setup

### Linux / macOS

```bash
git clone <repo> && cd PKE
cp .env.example .env

# Backend
composer install
php artisan key:generate
php artisan migrate --seed

# Frontend
npm install
npm run build

# Launch (runs server + queue + vite + logs in parallel)
composer dev
```

### Docker

```bash
docker compose up -d
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build
```

Open http://localhost. Mailpit for password reset emails: http://localhost:8025.

### Windows (PKE demo)

See **[SETUP_WINDOWS.md](SETUP_WINDOWS.md)** for a step-by-step guide covering both Laragon (portable, no admin rights) and Docker Desktop. Includes a demo plan, troubleshooting, and a PGN to paste during the presentation.

---

## Stockfish integrācija / Stockfish Integration

### Client-side (WASM)

`resources/js/services/stockfish.js` runs Stockfish in a Web Worker with UCI protocol. Local files `public/stockfish.js` + `public/stockfish.wasm` (~67 MB) work offline. CDN fallback activates only if local files are unavailable. Configurable via `config/chess.php` → `stockfish`.

### Server-side (Laravel Queue)

`app/Jobs/AnalyzeGameJob.php` invokes the binary `/usr/games/stockfish` via `proc_open` at depth 18+. Delegates classification to `ChessAnalyzer` and explanations to `ExplanationGenerator` — the single source of truth for both client and server analysis. Configurable via `.env`: `STOCKFISH_BINARY`, `STOCKFISH_DEPTH`, `STOCKFISH_TIMEOUT`.

---

## Testēšana / Testing

```bash
# PHP (160 test methods across 19 files)
php artisan test

# Frontend (98 test methods across 6 files)
npm run test:run

# Coverage report
npm run coverage

# Lint
npm run lint
```

**Total: 258 automated tests.** CI workflow `.github/workflows/ci.yml` runs both suites on every push/PR.

---

## Drošība / Security

- HTTPS + Bcrypt passwords + Sanctum session authentication
- CSRF protection via `XSRF-TOKEN` cookie
- XSS — Vue auto-escaping; no unvalidated `v-html`
- SQL injection — all queries via Eloquent ORM (parameterized)
- TOTP 2FA (Google Authenticator, Authy)
- Rate limiting (`throttle` middleware) on auth, analysis, and import routes
- Admin self-deletion guard + audit logging
- GDPR — account deletion via `DELETE /api/user/me` with password confirmation
- Input bounds — `perPage` capped at 100, analysis `depth` capped at config max, move arrays capped at 600

---

## Dokumentācija / Documentation

| File | Description |
|---|---|
| `COMPLIANCE.md` | Requirements traceability matrix — all 32 requirements mapped to code |
| `FUTURE_WORK.md` | Acknowledged limitations and future directions |
| `SETUP_WINDOWS.md` | Windows setup guide for PKE demo (Laragon + Docker) |
| `PKE_anisimovics.docx` | Full PKE documentation (requirements, UML, user guide, testing) |

---

## Licence / License

MIT — see `LICENSE` file.
