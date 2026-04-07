# ♔ Šaha Analīzes un Mācību Platforma

**PKE 2026 — Ēriks Anisimovičs · Liepājas Valsts Tehnikums**

## Kas ir šī platforma?

Tiešsaistes šaha platforma ar **reālu Stockfish integrāciju** (WASM pārlūkā + servera dzinējs), interaktīvu šaha galdiņu, spēli pret dzinēju un treniņu režīmu ar risināmiem uzdevumiem.

**Tehnoloģijas:** Laravel 12 + Sail, Vue 3 + Pinia, Tailwind CSS 4, chess.js, Stockfish WASM

---

## Stockfish integrācija

### Pārlūka puse (WASM)
- **Faili:** `resources/js/services/stockfish.js`
- Ielādē Stockfish 10 no cdnjs.cloudflare.com kā Web Worker
- Izmanto UCI protokolu: `position fen ... → go depth N → bestmove`
- **Spēle pret dzinēju:** Reāllaika gājienu ģenerēšana ar regulējamu grūtību (Skill Level 0–20)
- **Analīze:** Katrs gājiens tiek analizēts ar `engine.analyze(fen, depth)` — atgriež eval, bestMove, PV līniju

### Servera puse (Laravel Queue)
- **Faili:** `app/Services/StockfishService.php`, `app/Jobs/AnalyzeGameJob.php`
- Izsauc `/usr/games/stockfish` bināro caur `proc_open`
- Darbojas kā Queue job — nontiek fona procesā ar augstāku dziļumu (depth 18+)
- Konfigurējams: `STOCKFISH_PATH`, `STOCKFISH_DEPTH`, `STOCKFISH_TIMEOUT` .env mainīgie
- Instalācija konteinerā: `apt-get install stockfish`

---

## Interaktīvais šaha galdiņš

**Fails:** `resources/js/components/ChessBoard.vue`

Pilnībā custom SVG komponents (bez ārējām bibliotēkām):
- 8×8 režģis ar figūru Unicode simboliem
- Klikšķis uz figūras → parāda legālos gājienus (punkti = tukši laukumi, gredzeni = sitieni)
- Klikšķis uz mērķa → veic gājienu
- Pēdējā gājiena izcēlums (dzeltens)
- Kļūdu laukumu izcēlums (sarkans/oranžs)
- Bandinieka paaugstināšanas dialogs
- Orientācijas maiņa (baltais/melnais skatījums)
- Koordinātu marķējums (a-h, 1-8)
- Pilna chess.js integrācija gājienu validācijai

---

## Spēle pret Stockfish

**Lapa:** `/play` · **Fails:** `resources/js/pages/play.vue`

- Reāllaika spēle pret Stockfish WASM tieši pārlūkā
- **Regulējama grūtība:** 0 (Iesācējs) → 20 (Meistars) ar skaidru marķējumu
- **Domāšanas laika regulēšana:** 0.5s – 5s
- Krāsas izvēle (baltais/melnais)
- Dzinēja statusa indikators (domā / gaida)
- Gājienu vēsture ar krāsu kodēšanu
- Spēles beigu noteikšana (mats, pats, neizšķirts)
- **Saglabāšana:** Pēc spēles var saglabāt partiju datubāzē turpmākai analīzei

---

## Partiju analīze

**Komponents:** `resources/js/components/GameAnalysis.vue`

1. Augšupielādē PGN → chess.js parsē gājienus un ģenerē FEN katrai pozīcijai
2. Lietotājs spiež "Analizēt ar Stockfish" → WASM dzinējs analizē katru pozīciju
3. Rezultāts: katrs gājiens saņem **reālu evalvāciju** un klasifikāciju:
   - `best` / `excellent` / `good` / `inaccuracy` / `mistake` / `blunder`
4. Kļūdas tiek kategorizētas: taktiskā, pozicionālā, atklātnes, galotnes
5. Latviešu valodas skaidrojumi ar konkrētu labāko gājienu

**Interaktīvā analīze:**
- Šaha galdiņš rāda pozīciju
- Navigācija: ⏮ ◀ ▶ ⏭ + tastatūras bultiņas
- Eval josla (baltais/melnais pārsvars)
- Kļūdainie laukumi izcēlti uz galdiņa
- Gājienu saraksts ar krāsu kodēšanu — klikšķis → lec uz pozīciju
- Kopsavilkums: rupjo kļūdu, kļūdu, neprecizitāšu skaits

**Servera analīze:** `POST /api/game/{id}/analyze?server=true` → dispatcho `AnalyzeGameJob` ar augstāku dziļumu

---

## Treniņu režīms

**Lapa:** `/training` · **Fails:** `resources/js/pages/training.vue`

1. Lietotājs izvēlas analizētu partiju
2. Sistēma ģenerē uzdevumus no kļūdainajām pozīcijām (`TrainingService::generatePuzzleFromErrors`)
3. Katrs uzdevums rāda pozīciju uz **interaktīvā galdiņa**
4. Lietotājs veic gājienu → sistēma pārbauda, vai tas ir pareizais
5. Rezultāts: ✓ Pareizi / ✕ Nepareizi ar pareizās atbildes parādīšanu
6. Progresa izsekošana pa kategorijām (taktiskās, pozicionālās, atklātnes, galotnes)
7. Precizitātes procentuālais rādītājs katrai kategorijai

---

## Administrācijas panelis

**Lapa:** `/admin` · **Fails:** `resources/js/pages/admin.vue`

Trīs cilnes:

### ◈ Pārskats
- Kopējais lietotāju skaits, partiju skaits, analizēto partiju skaits
- Vidējais gājienu skaits partijā
- Rezultātu sadalījuma josla (1-0, 0-1, ½-½, *)
- Top 10 populārākās atklātnes

### ◉ Lietotāji
- Pilna tabula: ID, vārds, e-pasts, loma, ELO, reģistrācijas datums
- Dzēšanas iespēja

### ♟ Partijas
- Pilna tabula: ID, spēlētāji, atklātne, rezultāts, gājienu skaits, analīzes statuss, datums

---

## Uzstādīšana

```bash
cd chess-platform
composer install
./vendor/bin/sail up -d

# Instalēt Stockfish servera analīzei (neobligāti)
./vendor/bin/sail shell
apt-get update && apt-get install -y stockfish
exit

./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

### Piekļuves dati
| E-pasts | Parole | Loma |
|---------|--------|------|
| admin@example.com | password | Admin |
| user@example.com | password | Lietotājs |

---

**Autors:** Ēriks Anisimovičs, 4PT
**Vadītājs:** Skolotājs Kristovskis Raimonds
**Eksāmens:** 2026. gada 16.–17. jūnijs
