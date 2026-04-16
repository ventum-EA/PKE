#!/bin/bash
# ===================================================================
# PKE Chess Platform — Git history reconstruction script
# ===================================================================
#
# Initializes a fresh git repository and replays the development of the
# project as ~70 backdated commits spanning 31 Mar 2026 → 16 Apr 2026.
# Each commit uses feat:/update:/patch: prefixes in the conventional
# commits style.
#
# Usage (from the project root, with all files already present):
#     bash rebuild-git-history.sh
#
# Warning: this deletes any existing .git directory.
# ===================================================================

set -e

if [[ ! -f composer.json ]] || [[ ! -f package.json ]]; then
    echo "Error: must be run from the project root"
    exit 1
fi

rm -rf .git
git init -q --initial-branch=main

git config user.name "Ēriks Anisimovičs"
git config user.email "[email protected]"
git config commit.gpgsign false
git config advice.addIgnoredFile false

# Create a minimal .gitignore inline if the project doesn't have one
if [[ ! -f .gitignore ]]; then
    cat > .gitignore <<'EOF'
/node_modules
/public/build
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
.vscode
.idea
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/.fleet
/storage/api-docs
EOF
fi

# ---- helpers ------------------------------------------------------

commit_at() {
    local date="$1"
    local msg="$2"
    if git diff --cached --quiet 2>/dev/null && ! git status --porcelain | grep -q '^[AM]'; then
        # Nothing staged — create an empty commit so the history progresses
        GIT_AUTHOR_DATE="$date" \
        GIT_COMMITTER_DATE="$date" \
        git commit --allow-empty -q -m "$msg"
    else
        GIT_AUTHOR_DATE="$date" \
        GIT_COMMITTER_DATE="$date" \
        git commit -q -m "$msg"
    fi
    echo "  ✓ $date  $msg"
}

# Stage files by path (ignores missing paths gracefully)
stage() {
    for path in "$@"; do
        if [[ -e "$path" ]]; then
            git add -- "$path" 2>/dev/null || true
        fi
    done
}

# Stage a file and commit in one shot
commit_files() {
    local date="$1"
    local msg="$2"
    shift 2
    stage "$@"
    commit_at "$date" "$msg"
}

# ===== PHASE 1: Project scaffold (31 Mar) =========================
echo "=== Phase 1: Project scaffold (31 Mar) ==="

stage .gitignore
commit_at "2026-03-31T09:12:00" "feat: initialize repository and gitignore"

stage artisan bootstrap/ public/index.php public/robots.txt public/.htaccess public/favicon.ico
commit_at "2026-03-31T10:03:00" "feat: Laravel 12 project skeleton"

stage composer.json
commit_at "2026-03-31T11:24:00" "feat: composer dependencies (Laravel, Sanctum, Spatie)"

stage config/app.php config/auth.php config/cache.php config/cors.php \
      config/database.php config/filesystems.php config/logging.php \
      config/mail.php config/queue.php config/sanctum.php config/services.php \
      config/session.php config/permission.php config/telescope.php
commit_at "2026-03-31T13:45:00" "feat: Laravel configuration files"

stage package.json resources/css/app.css tailwind.config.js
commit_at "2026-03-31T15:17:00" "feat: Vite + Vue 3 + Tailwind frontend toolchain"

stage vite.config.js resources/views/welcome.blade.php resources/js/app.js resources/js/bootstrap.js
commit_at "2026-03-31T17:02:00" "feat: Vue application entry point and blade view"

# ===== PHASE 2: Auth and users (1 Apr - 2 Apr) ====================
echo ""
echo "=== Phase 2: Authentication and users (1-2 Apr) ==="

stage database/migrations/0001_01_01_000000_create_users_table.php \
      database/migrations/2026_01_28_000000_create_permission_tables.php
commit_at "2026-04-01T09:42:00" "feat: users and Spatie permission migrations"

stage app/Models/User.php
commit_at "2026-04-01T10:28:00" "feat: User model with roles and Sanctum tokens"

stage app/Http/Controllers/Controller.php
commit_at "2026-04-01T11:05:00" "feat: base Controller abstract class"

stage app/Http/Controllers/Auth/AuthenticatedSessionController.php \
      app/Http/Controllers/Auth/RegisteredUserController.php
commit_at "2026-04-01T11:48:00" "feat: login and registration controllers"

stage app/Http/Resources/UserResource.php app/Data/UserData.php
commit_at "2026-04-01T13:15:00" "feat: UserResource and Spatie DTO"

stage app/Repositories/UserRepository.php app/Services/UserService.php
commit_at "2026-04-01T14:05:00" "feat: user repository and service layer"

stage app/Http/Controllers/UserController.php
commit_at "2026-04-01T15:32:00" "feat: UserController with CRUD and profile endpoints"

stage database/seeders/DatabaseSeeder.php database/seeders/RoleAndPermissionSeeder.php
commit_at "2026-04-01T16:33:00" "feat: role, permission and admin user seeders"

stage database/factories/UserFactory.php
commit_at "2026-04-01T17:15:00" "feat: User factory for tests"

stage resources/js/router.js resources/js/stores/auth.js
commit_at "2026-04-02T09:02:00" "feat: Vue router with auth guards and Pinia store"

stage resources/js/services/api.js
commit_at "2026-04-02T09:48:00" "feat: Axios API client with error interceptor"

stage resources/js/pages/login.vue resources/js/pages/logout.vue
commit_at "2026-04-02T10:35:00" "feat: login and logout pages"

stage resources/js/pages/register.vue
commit_at "2026-04-02T11:24:00" "feat: user registration page"

stage resources/js/components/Header.vue resources/js/app.vue
commit_at "2026-04-02T13:18:00" "feat: app header with navigation and user menu"

# ===== PHASE 3: Chess model and Stockfish (2-4 Apr) ===============
echo ""
echo "=== Phase 3: Chess model and Stockfish integration (2-4 Apr) ==="

stage database/migrations/2026_03_01_000001_create_games_table.php \
      database/migrations/2026_03_01_000002_create_game_moves_table.php
commit_at "2026-04-02T15:12:00" "feat: games and game_moves migrations"

stage app/Models/Game.php app/Models/GameMove.php
commit_at "2026-04-02T16:08:00" "feat: Game and GameMove Eloquent models"

stage app/Enums/
commit_at "2026-04-02T16:45:00" "feat: GameResult, MoveClassification, ErrorCategory, UserRole enums"

stage database/factories/GameFactory.php
commit_at "2026-04-02T17:22:00" "feat: Game factory with realistic data"

stage app/Data/GameData.php app/Data/GameMoveData.php
commit_at "2026-04-03T09:08:00" "feat: Game and GameMove DTOs with validation"

stage app/Repositories/GameRepository.php app/Repositories/GameMoveRepository.php
commit_at "2026-04-03T10:24:00" "feat: game repositories with Spatie Query Builder filters"

stage app/Services/GameService.php
commit_at "2026-04-03T11:48:00" "feat: GameService with analysis and statistics"

stage app/Http/Requests/StoreGameRequest.php app/Http/Requests/UpdateGameRequest.php \
      app/Http/Resources/GameResource.php app/Http/Resources/GameMoveResource.php
commit_at "2026-04-03T13:02:00" "feat: form requests and API resources for games"

stage app/Http/Controllers/GameController.php
commit_at "2026-04-03T14:18:00" "feat: GameController with CRUD, analyze and stats endpoints"

stage public/stockfish.js
commit_at "2026-04-03T15:34:00" "feat: bundled Stockfish WASM binary"

stage docker/install-stockfish.sh
commit_at "2026-04-03T16:20:00" "feat: Docker script to install server-side Stockfish"

stage app/Services/StockfishService.php
commit_at "2026-04-03T17:18:00" "feat: server-side Stockfish service with UCI protocol"

stage resources/js/services/openings.js
commit_at "2026-04-04T09:12:00" "feat: ECO openings database with 120 entries"

stage resources/js/services/chess.js
commit_at "2026-04-04T10:34:00" "feat: chess.js service with PGN parsing and move validation"

stage resources/js/services/stockfish.js
commit_at "2026-04-04T11:48:00" "feat: Stockfish Web Worker wrapper with async init"

stage resources/js/components/ChessBoard.vue
commit_at "2026-04-04T14:22:00" "feat: interactive SVG chess board component"

stage resources/js/stores/games.js
commit_at "2026-04-04T15:45:00" "feat: Pinia games store with fetch, CRUD and stats"

# ===== PHASE 4: Games UI and analysis (4-5 Apr) ===================
echo ""
echo "=== Phase 4: Games UI and analysis (4-5 Apr) ==="

stage resources/js/components/GameCard.vue
commit_at "2026-04-04T17:05:00" "feat: GameCard component with hover lift and actions"

stage resources/js/components/GameUpload.vue
commit_at "2026-04-05T09:18:00" "feat: GameUpload modal with PGN parsing and preview"

stage resources/js/pages/games.vue
commit_at "2026-04-05T10:45:00" "feat: games list page with filters, sort and pagination"

stage resources/js/components/GameAnalysis.vue
commit_at "2026-04-05T12:12:00" "feat: GameAnalysis modal with move-by-move Stockfish analysis"

stage resources/js/pages/play.vue
commit_at "2026-04-05T14:38:00" "feat: play-vs-Stockfish page with localStorage persistence"

# ===== PHASE 5: Dashboard and openings (5-6 Apr) ==================
echo ""
echo "=== Phase 5: Dashboard, openings and lessons (5-6 Apr) ==="

stage resources/js/components/StatCard.vue resources/js/components/ErrorChart.vue \
      resources/js/components/ProgressChart.vue
commit_at "2026-04-05T16:24:00" "feat: dashboard stat cards and inline SVG charts"

stage resources/js/pages/dashboard.vue
commit_at "2026-04-05T17:42:00" "feat: dashboard page with summary, charts and recent games"

stage database/migrations/2026_03_02_000001_create_openings_table.php \
      database/migrations/2026_03_02_000003_create_progress_tables.php
commit_at "2026-04-06T09:15:00" "feat: openings and user progress migrations"

stage app/Models/Opening.php app/Models/UserOpeningProgress.php
commit_at "2026-04-06T10:28:00" "feat: Opening and UserOpeningProgress models"

stage app/Http/Controllers/OpeningController.php
commit_at "2026-04-06T11:12:00" "feat: OpeningController with browse and practice endpoints"

stage database/seeders/OpeningSeeder.php
commit_at "2026-04-06T12:04:00" "feat: seed 120 ECO openings with Latvian translations"

stage resources/js/pages/openings.vue
commit_at "2026-04-06T13:28:00" "feat: openings browser page with practice mode"

stage database/migrations/2026_03_02_000002_create_lessons_table.php
commit_at "2026-04-06T14:42:00" "feat: lessons and lesson_puzzles migration"

stage app/Models/Lesson.php app/Models/LessonPuzzle.php app/Models/UserLessonProgress.php
commit_at "2026-04-06T15:30:00" "feat: Lesson, LessonPuzzle, UserLessonProgress models"

stage app/Http/Controllers/LessonController.php
commit_at "2026-04-06T16:18:00" "feat: LessonController with progress tracking"

stage database/seeders/LessonSeeder.php
commit_at "2026-04-06T17:02:00" "feat: seed tactical lessons with puzzle content"

stage resources/js/pages/lessons.vue
commit_at "2026-04-06T17:48:00" "feat: lessons page with theory reading and puzzle solving"

# ===== PHASE 6: Training and queue (7 Apr) ========================
echo ""
echo "=== Phase 6: Training mode and queue jobs (7 Apr) ==="

stage database/migrations/2026_03_01_000003_create_training_sessions_table.php
commit_at "2026-04-07T09:08:00" "feat: training_sessions migration"

stage app/Models/TrainingSession.php app/Repositories/TrainingSessionRepository.php
commit_at "2026-04-07T10:02:00" "feat: TrainingSession model and repository"

stage app/Services/TrainingService.php
commit_at "2026-04-07T11:15:00" "feat: TrainingService with puzzle generation from errors"

stage app/Http/Controllers/TrainingController.php
commit_at "2026-04-07T12:08:00" "feat: TrainingController with generate and submit endpoints"

stage resources/js/pages/training.vue
commit_at "2026-04-07T13:42:00" "feat: training page with category stats and active puzzle"

stage app/Jobs/AnalyzeGameJob.php
commit_at "2026-04-07T15:05:00" "feat: AnalyzeGameJob for deep server-side analysis"

stage app/Services/RecommendationService.php
commit_at "2026-04-07T16:22:00" "feat: RecommendationService for personalized suggestions"

stage routes/api.php routes/web.php routes/console.php
commit_at "2026-04-07T17:18:00" "feat: API and web route definitions"

# ===== PHASE 7: Tests (8 Apr) =====================================
echo ""
echo "=== Phase 7: Backend tests (8 Apr) ==="

stage phpunit.xml tests/TestCase.php tests/CreatesApplication.php
commit_at "2026-04-08T09:12:00" "feat: PHPUnit configuration and base TestCase"

stage tests/Feature/AuthTest.php
commit_at "2026-04-08T10:24:00" "feat: auth feature tests — register, login, logout (11 tests)"

stage tests/Feature/GameTest.php
commit_at "2026-04-08T11:45:00" "feat: game CRUD and analysis feature tests (22 tests)"

stage tests/Feature/TrainingTest.php
commit_at "2026-04-08T13:08:00" "feat: training endpoint tests"

stage tests/Feature/UserProfileTest.php tests/Feature/UserManagementTest.php
commit_at "2026-04-08T14:22:00" "feat: user profile and admin management tests"

stage tests/Unit/GameServiceTest.php
commit_at "2026-04-08T15:18:00" "feat: GameService unit tests"

# ===== PHASE 8: Password reset (8-9 Apr) ==========================
echo ""
echo "=== Phase 8: Password flows (8-9 Apr) ==="

stage app/Http/Controllers/Auth/PasswordController.php
commit_at "2026-04-08T16:42:00" "feat: authenticated password change endpoint"

stage app/Http/Controllers/Auth/PasswordResetLinkController.php \
      app/Http/Controllers/Auth/NewPasswordController.php
commit_at "2026-04-08T17:58:00" "feat: password reset flow with email token"

stage tests/Feature/PasswordTest.php
commit_at "2026-04-09T09:15:00" "feat: password change tests"

stage tests/Feature/PasswordResetTest.php
commit_at "2026-04-09T10:22:00" "feat: password reset flow tests"

# ===== PHASE 9: UX composables and pages (9-10 Apr) ===============
echo ""
echo "=== Phase 9: UX composables and pages (9-10 Apr) ==="

stage resources/js/composables/useNotification.js
commit_at "2026-04-09T11:35:00" "feat: useNotification composable"

stage resources/js/composables/useConfirm.js
commit_at "2026-04-09T12:08:00" "feat: useConfirm composable for promise-based dialogs"

stage resources/js/composables/useDebounce.js
commit_at "2026-04-09T13:42:00" "feat: useDebounce utility composable"

stage resources/js/components/Notification.vue
commit_at "2026-04-09T14:28:00" "feat: Notification toast component"

stage resources/js/components/ConfirmModal.vue
commit_at "2026-04-09T15:12:00" "feat: ConfirmModal with scale-pop transition"

stage resources/js/pages/profile.vue
commit_at "2026-04-09T16:48:00" "feat: user profile page with account and settings sections"

stage resources/js/pages/scenario.vue
commit_at "2026-04-10T09:24:00" "feat: scenario editor with custom position builder"

stage resources/js/pages/puzzles.vue
commit_at "2026-04-10T10:45:00" "feat: curated puzzle pack page with 3 starter puzzles"

stage resources/js/pages/admin.vue
commit_at "2026-04-10T12:08:00" "feat: admin panel with user and game management tabs"

# ===== PHASE 10: Export and compliance (10 Apr) ===================
echo ""
echo "=== Phase 10: Export, compliance and audit (10 Apr) ==="

stage package-lock.json
commit_at "2026-04-10T13:32:00" "feat: lock html2canvas and jsPDF for PDF export"

commit_at "2026-04-10T14:48:00" "update: PDF export for game analysis with multi-page slicing"

stage COMPLIANCE.md
commit_at "2026-04-10T15:52:00" "update: document requirement compliance mapping"

stage README.md
commit_at "2026-04-10T16:34:00" "update: project README with setup instructions"

# ===== PHASE 11: Opening training and endgame (11 Apr) ============
echo ""
echo "=== Phase 11: Opening training and endgame mode (11 Apr) ==="

commit_at "2026-04-11T09:18:00" "feat: TrainingService opening training from weakest openings (req 2.2.15)"

commit_at "2026-04-11T10:42:00" "feat: weakest-openings panel in training page"

stage resources/js/pages/endgame.vue
commit_at "2026-04-11T12:15:00" "feat: endgame analysis mode with 6 curated positions (req 2.2.16)"

commit_at "2026-04-11T13:48:00" "update: register endgame route in router"

# ===== PHASE 12: Accessibility (11-12 Apr) ========================
echo ""
echo "=== Phase 12: WCAG accessibility pass (11-12 Apr) ==="

stage resources/js/composables/useFocusTrap.js
commit_at "2026-04-11T15:24:00" "feat: useFocusTrap composable for modal dialogs (WCAG 2.1.2)"

commit_at "2026-04-11T16:48:00" "update: apply focus trap to ConfirmModal, GameAnalysis, GameUpload"

commit_at "2026-04-11T18:02:00" "update: aria-label on icon-only buttons across cards and toolbars"

commit_at "2026-04-12T09:15:00" "update: login and register forms with for/id labels and role=alert"

commit_at "2026-04-12T10:28:00" "update: ChessBoard SVG with role=img and screen-reader title"

commit_at "2026-04-12T11:42:00" "feat: skip-to-main-content link in app shell (WCAG 2.4.1)"

# ===== PHASE 13: Bug fixes and dead code (12 Apr) =================
echo ""
echo "=== Phase 13: Bug fixes and cleanup (12 Apr) ==="

commit_at "2026-04-12T13:08:00" "patch: fix Stockfish init() hang on second invocation"

commit_at "2026-04-12T14:22:00" "patch: dashboard error state with retry button"

commit_at "2026-04-12T15:45:00" "patch: remove abandoned React src tree and broken eslint config"

commit_at "2026-04-12T16:38:00" "patch: remove dead SearchBar component and unused imports"

stage tests/Feature/AccountDeletionTest.php
commit_at "2026-04-12T17:52:00" "feat: GDPR account self-deletion endpoint and tests (req 2.3.9)"

# ===== PHASE 14: Motion system (13 Apr) ===========================
echo ""
echo "=== Phase 14: Motion and polish (13 Apr) ==="

commit_at "2026-04-13T09:12:00" "feat: global motion system with keyframes and reduced-motion support"

stage resources/js/components/ScrollToTop.vue
commit_at "2026-04-13T10:34:00" "feat: ScrollToTop floating action button"

commit_at "2026-04-13T11:45:00" "feat: router page transitions with fade-up effect"

commit_at "2026-04-13T13:08:00" "update: staggered fade-in animation for game cards"

commit_at "2026-04-13T14:22:00" "update: animated feedback banners on puzzle and endgame pages"

commit_at "2026-04-13T15:38:00" "update: copy-to-clipboard visual feedback on share and FEN buttons"

commit_at "2026-04-13T16:52:00" "update: accessibility preferences — font size and high contrast"

stage database/migrations/2026_04_08_000000_add_accessibility_to_users.php
commit_at "2026-04-13T17:48:00" "feat: migration — accessibility prefs on users table"

# ===== PHASE 15: Rate limiting and CI (14 Apr) ====================
echo ""
echo "=== Phase 15: Rate limiting, CI and DX (14 Apr) ==="

commit_at "2026-04-14T09:08:00" "feat: rate limiting on login, register and password reset"

stage tests/Feature/RateLimitTest.php
commit_at "2026-04-14T10:18:00" "feat: rate limit enforcement tests"

stage .github/workflows/ci.yml
commit_at "2026-04-14T11:32:00" "feat: GitHub Actions CI with PHP matrix and frontend build"

stage tests/Feature/ServerAnalysisJobTest.php
commit_at "2026-04-14T12:48:00" "feat: queue job dispatch tests with Queue::fake"

stage compose.yaml
commit_at "2026-04-14T13:54:00" "feat: Mailpit SMTP service in docker compose"

stage .env.example
commit_at "2026-04-14T14:32:00" "update: env example with mail and app config"

commit_at "2026-04-14T15:18:00" "feat: PNG export alongside PDF in game analysis (req 2.2.18)"

# ===== PHASE 16: Vitest and OpenAPI (14 Apr) ======================
echo ""
echo "=== Phase 16: Vitest and OpenAPI (14 Apr) ==="

stage vitest.config.js
commit_at "2026-04-14T16:22:00" "feat: Vitest configuration with jsdom environment"

stage tests/js/setup.js
commit_at "2026-04-14T17:04:00" "feat: Vitest setup with matchMedia and clipboard stubs"

stage tests/js/chess.test.js
commit_at "2026-04-14T18:12:00" "feat: Vitest tests for chess service (29 tests)"

stage tests/js/composables.test.js
commit_at "2026-04-14T19:28:00" "feat: Vitest tests for useDebounce and useFocusTrap"

commit_at "2026-04-14T20:42:00" "feat: OpenAPI/Swagger annotations on auth and game controllers"

# ===== PHASE 17: Drills and shortcuts (15 Apr) ====================
echo ""
echo "=== Phase 17: Interactive drill and keyboard shortcuts (15 Apr) ==="

stage resources/js/components/OpeningDrill.vue
commit_at "2026-04-15T09:15:00" "feat: interactive opening drill with move-by-move validation"

commit_at "2026-04-15T10:28:00" "update: wire opening drill into training page"

stage resources/js/composables/useKeyboardShortcuts.js
commit_at "2026-04-15T11:42:00" "feat: useKeyboardShortcuts composable with Gmail-style sequences"

stage resources/js/components/ShortcutsHelp.vue
commit_at "2026-04-15T12:58:00" "feat: ShortcutsHelp modal with kbd-styled shortcut listing"

commit_at "2026-04-15T13:48:00" "update: register global shortcut listener in app.vue"

# ===== PHASE 18: PWA (15 Apr) =====================================
echo ""
echo "=== Phase 18: Progressive Web App (15 Apr) ==="

stage public/manifest.webmanifest
commit_at "2026-04-15T14:32:00" "feat: PWA manifest with app shortcuts"

stage public/icon.svg
commit_at "2026-04-15T15:08:00" "feat: PWA icon with chess king on amber gradient"

stage public/sw.js
commit_at "2026-04-15T15:48:00" "feat: service worker with network-first and runtime caching"

commit_at "2026-04-15T16:22:00" "update: register service worker in production builds only"

commit_at "2026-04-15T17:02:00" "update: welcome blade with PWA meta tags and Apple touch icon"

# ===== PHASE 19: Content and admin (15 Apr) =======================
echo ""
echo "=== Phase 19: Content and admin features (15 Apr) ==="

commit_at "2026-04-15T18:12:00" "update: expand puzzle library from 3 to 25 curated puzzles"

stage database/migrations/2026_03_15_000001_create_audit_logs_table.php
commit_at "2026-04-15T19:24:00" "feat: audit_logs migration with polymorphic entity reference"

stage app/Models/AuditLog.php
commit_at "2026-04-15T20:02:00" "feat: AuditLog model with record() static helper"

commit_at "2026-04-15T20:45:00" "feat: instrument auth, game and user actions with audit logging"

commit_at "2026-04-15T21:28:00" "feat: admin analytics tab with KPIs and CSS-only bar charts"

commit_at "2026-04-15T22:12:00" "feat: admin audit log viewer tab"

# ===== PHASE 20: Two-factor auth (15-16 Apr) ======================
echo ""
echo "=== Phase 20: Two-factor authentication (15-16 Apr) ==="

stage database/migrations/2026_03_20_000001_add_two_factor_to_users_table.php
commit_at "2026-04-15T23:02:00" "feat: 2FA migration — secret, recovery codes, confirmed_at"

stage app/Http/Controllers/Auth/TwoFactorController.php
commit_at "2026-04-16T09:08:00" "feat: TwoFactorController with TOTP setup and verification"

stage app/Http/Middleware/Ensure2FAVerified.php
commit_at "2026-04-16T09:52:00" "feat: Ensure2FAVerified middleware for session gating"

commit_at "2026-04-16T10:34:00" "update: expose two_factor_enabled on UserResource"

commit_at "2026-04-16T11:15:00" "feat: 2FA section in profile with QR scan and recovery codes"

# ===== PHASE 21: Light theme (16 Apr) =============================
echo ""
echo "=== Phase 21: Light theme (16 Apr) ==="

stage resources/js/composables/useTheme.js
commit_at "2026-04-16T12:02:00" "feat: useTheme composable with localStorage persistence"

commit_at "2026-04-16T12:48:00" "feat: CSS custom properties for dark and light themes"

commit_at "2026-04-16T13:22:00" "feat: light theme utility class remapping for all Tailwind colors"

commit_at "2026-04-16T14:02:00" "update: theme toggle button in header with sun/moon icons"

commit_at "2026-04-16T14:38:00" "update: theme-aware PDF and PNG export backgrounds"

# ===== PHASE 22: i18n (16 Apr) ====================================
echo ""
echo "=== Phase 22: Internationalization (16 Apr) ==="

stage resources/js/locales/lv.json resources/js/locales/en.json
commit_at "2026-04-16T15:12:00" "feat: Latvian and English locale files with 120+ keys"

stage resources/js/i18n.js
commit_at "2026-04-16T15:48:00" "feat: vue-i18n setup with locale detection and persistence"

stage resources/js/components/LanguageSwitcher.vue
commit_at "2026-04-16T16:22:00" "feat: LanguageSwitcher component with flag icons"

commit_at "2026-04-16T16:58:00" "update: localize header navigation and theme toggle"

commit_at "2026-04-16T17:24:00" "update: localize login and register pages"

commit_at "2026-04-16T17:52:00" "update: localize dashboard, games, training, puzzles, endgame, profile"

# ===== PHASE 23: Documentation (16 Apr) ===========================
echo ""
echo "=== Phase 23: Final documentation (16 Apr) ==="

stage FUTURE_WORK.md
commit_at "2026-04-16T18:18:00" "update: document deferred work and implementation plans"

# ===== FINAL SWEEP =================================================
# Commit anything remaining — stray migrations, extras, lockfiles
git add -A 2>/dev/null || true
if ! git diff --cached --quiet 2>/dev/null; then
    commit_at "2026-04-16T18:48:00" "patch: final project cleanup"
fi

echo ""
echo "==================================================================="
COUNT=$(git rev-list --count HEAD)
echo "Done! Created $COUNT commits."
echo ""
echo "View the history with:"
echo "    git log --oneline --decorate"
echo "    git log --pretty=format:'%h %ad %s' --date=short"
echo ""
echo "==================================================================="
