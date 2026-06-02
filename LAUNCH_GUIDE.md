# Palaišana, testēšana un attālā piekļuve

Šis ceļvedis aptver trīs scenārijus: **ātrā palaišana** ar Docker,
**pilna testēšana** (visi 3 testu līmeņi), un **piekļuve no skolas**
bez Laragon/Redis/MySQL instalēšanas.

---

## 1. Ātrā palaišana (viena komanda)

### Priekšnosacījumi

Vajadzīgs tikai **Docker Desktop** (lejupielādēt: https://www.docker.com/products/docker-desktop/).
Windows: instalēšanas laikā ieslēdz WSL 2 (Docker to piedāvā automātiski).

Pārbaudi, ka Docker darbojas:

```bash
docker --version
docker compose version
```

### Palaist

```bash
# 1. Izpako projektu un atver termināli tajā mapē
cd PKE_6

# 2. Viena komanda — izveido konteinerus, datubāzi, migrē, iespējo
docker compose -f docker-compose.standalone.yml up --build -d
```

Pirmā reize aizņem **3-8 minūtes** (lejupielādē PHP, MySQL, kompilē
frontend). Katru nākamo reizi — dažas sekundes.

### Pārbaudi

```
Platforma:    http://localhost
Mailpit:      http://localhost:8025   (šeit redzēs paroles atjaunošanas e-pastus)
```

### Demo konts

Seeder automātiski izveido administratora kontu:

```
E-pasts:   admin@chess.local
Parole:    password
```

Vai reģistrē jaunu kontu: http://localhost/register

### Apstādināt / Restartēt / Notīrīt

```bash
docker compose -f docker-compose.standalone.yml down        # apstādināt
docker compose -f docker-compose.standalone.yml up -d        # restartēt
docker compose -f docker-compose.standalone.yml down -v      # apstādināt + IZDZĒST datubāzi
```

---

## 2. Pilna testēšana

Projektam ir **trīs testu līmeņi**. Visus var palaist no Docker konteinera
vai lokāli.

### 2A. PHP testi (PHPUnit — 160 testi)

Pārbauda: API, autentifikāciju, spēles CRUD, multiplayer, treniņu, ELO aprēķinu,
achievements, GDPR dzēšanu, rate limiting.

```bash
# No Docker konteinera:
docker compose -f docker-compose.standalone.yml exec app php artisan test

# Vai lokāli (ja ir PHP 8.2+ un MySQL):
php artisan test
php artisan test --filter=GameTest         # tikai spēļu testi
php artisan test --filter=MultiplayerTest  # tikai multiplayer
php artisan test --filter=AuthTest         # tikai autentifikācija
```

Ko gaidīt:

```
Tests: 160 passed
```

### 2B. JavaScript testi (Vitest — 98 testi)

Pārbauda: šaha loģiku (chess.js wrapper), PGN parsēšanu, composable hooks,
lokalizāciju, ChessBoard komponentu.

```bash
# No Docker (ja node_modules nav):
docker compose -f docker-compose.standalone.yml exec app bash -c "npm ci && npx vitest run"

# Vai lokāli:
npm ci
npx vitest run
npx vitest run --reporter=verbose   # detalizēts izvads
```

Ko gaidīt:

```
Test Files: 7 passed
Tests: 98 passed
```

### 2C. E2E testi (Playwright — 15 testi)

Pārbauda: pilnu lietotāja ceļu caur pārlūku — reģistrācija, pieslēgšanās,
PGN augšupielāde, Stockfish WASM analīze, kopīgošana, spēle pret Stockfish,
navigācija, pieejamība.

**Vajadzīgs:** Node.js 18+ lokāli (Playwright kontrolē īstu pārlūku).

```bash
# 1. Instalē Playwright (vienreiz):
npm ci
npx playwright install --with-deps chromium

# 2. Pārliecinies, ka platforma darbojas:
#    http://localhost jābūt pieejamam

# 3. Palaid testus:
npx playwright test

# Ar vizuālo UI:
npx playwright test --ui

# Tikai analīzes flow:
npx playwright test game-analysis

# Ar lēnu izpildi (redzēt, kas notiek pārlūkā):
npx playwright test --headed --timeout=120000
```

Ko gaidīt:

```
3 passed test files
  auth.spec.js            — 4 tests
  game-analysis.spec.js   — 4 tests
  play-navigation.spec.js — 7 tests
```

Piezīme: game-analysis testi var aizņemt 30-60 sekundes, jo Stockfish WASM
analizē reālu partiju pārlūkā.

### 2D. Viss kopā

```bash
# Backend testi
docker compose -f docker-compose.standalone.yml exec app php artisan test

# Frontend unit testi
npx vitest run

# E2E testi (kamēr Docker darbojas)
npx playwright test
```

Kopā: **258 unit/integration + 15 E2E = 273 automatizētie testi**.

---

## 3. Piekļuve no skolas (bez Laragon)

### Problēma

Skolā bieži nav iespējams instalēt Laragon, MySQL, Redis, PHP.
Risinājums: **palaid visu mājās Docker konteinerī** un pieslēdzies
no skolas ar **Cloudflare Tunnel** (bezmaksas, nav jāatver porti).

### Kā tas strādā

```
[Skola: pārlūks] → internets → [Cloudflare tunelis] → [Tavas mājas PC: Docker]
```

Cloudflare Tunnel izveido drošu savienojumu no tavas mājas datora uz
Cloudflare serveri. Tev piešķir URL, piemēram, `https://chess-pke-demo.trycloudflare.com`.
Nav jākonfigurē maršrutētājs, nav jāatver porti.

### Solis pa solim

**Mājās (vienu reizi iestatīt):**

```bash
# 1. Palaid platformu
docker compose -f docker-compose.standalone.yml up --build -d

# 2. Instalē cloudflared
#    Windows:   winget install cloudflare.cloudflared
#    Mac:       brew install cloudflared
#    Linux:     sudo apt install cloudflared
#    Vai lejupielādē: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/

# 3. Izveido tuneli (nav vajadzīgs Cloudflare konts!)
cloudflared tunnel --url http://localhost:80
```

Terminālis parādīs:

```
Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):
https://chess-demo-abc123.trycloudflare.com
```

**Skolā:**

Atver to URL jebkurā pārlūkā. Gatavs — pilna platforma, ieskaitot
Stockfish analīzi (WASM strādā pārlūkā).

### Svarīgi

- **Mājas dators jābūt ieslēgtam** un Docker jādarbojas
- **Tunelis jābūt atvērtam** (terminālis nedrīkst būt aizvērts)
- URL mainās katru reizi, kad pārstartē `cloudflared` (bezmaksas versijā)
- Ja vajag **pastāvīgu URL**, izveido Cloudflare kontu (bezmaksas) un
  reģistrē tuneli ar `cloudflared tunnel create chess-demo`

### Alternatīvas

**ngrok** (ja cloudflared nestrādā):

```bash
# Instalē: https://ngrok.com/download (vajadzīgs bezmaksas konts)
ngrok http 80
```

Piešķirs URL: `https://abc123.ngrok-free.app`

**Tailscale** (ja abos datoros var instalēt):

```bash
# Mājās un skolā instalē Tailscale: https://tailscale.com/download
# Pieslēdzies ar to pašu kontu
# Skolā atver: http://majas-dators:80
```

---

## 4. Ko rādīt eksāmenā (demo plāns)

Ieteicamā secība (15-20 min):

| # | Ko rādīt | Laiks |
|---|----------|-------|
| 1 | **Reģistrācija** — jauns konts, uzrāda validāciju | 1 min |
| 2 | **PGN augšupielāde** — ielīmē PGN, partija saglabājas | 1 min |
| 3 | **Stockfish analīze** — analizē, rāda klasifikāciju + LV skaidrojumus | 3 min |
| 4 | **Treniņš no kļūdām** — ģenerē uzdevumus no kļūdainām pozīcijām | 2 min |
| 5 | **Spēle pret Stockfish** — spēlē 5-6 gājienus ar grūtību 5 | 2 min |
| 6 | **Multiplayer** — 2 pārlūkos, izveido spēli, spēlē 3 gājienus | 2 min |
| 7 | **Profils** — gaišā/tumšā tēma, valodas maiņa LV↔EN, galdiņa tēmas | 1 min |
| 8 | **2FA** — ieslēdz, rāda QR kodu, recovery kodus | 1 min |
| 9 | **Testi** — palaid `php artisan test` un `npx vitest run` | 1 min |
| 10 | **GDPR** — konta dzēšana ar paroles + atslēgvārda apstiprinājumu | 1 min |

**PGN ko ielīmēt:**

```
1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5
7. Bb3 d6 8. c3 O-O 9. h3 Nb8 10. d4 Nbd7 1-0
```

### Sagatavošanās čeklists

- [ ] Docker darbojas un `docker compose up -d` ir izpildīts
- [ ] http://localhost atver platformu
- [ ] Cloudflare tunelis darbojas (ja demo skolā)
- [ ] `npm run build` izpildīts
- [ ] Demo PGN nokopēts clipboard
- [ ] Divi pārlūka logi atvērti multiplayer demo
- [ ] Admin konts pieejams (`admin@chess.local` / `password`)

---

## Problēmu risināšana

### "Port 80 is already in use"

```bash
# Nomainīt portu .env vai docker-compose.standalone.yml:
#   ports: - "8080:80"
# Tad atver http://localhost:8080
```

### "Docker build fails" ar npm/composer kļūdu

```bash
# Notīrīt Docker kešu un mēģināt no jauna:
docker compose -f docker-compose.standalone.yml down -v
docker system prune -f
docker compose -f docker-compose.standalone.yml up --build -d
```

### Stockfish WASM neielādējas (67MB)

Ja pārlūka konsolē (F12) rāda "Failed to load stockfish.wasm":
- Pārbaudi, ka `public/stockfish.wasm` eksistē (67 MB)
- Dažos tīklos 67 MB fails var tikt bloķēts — izmēģini caur tuneli

### Datubāze ir tukša pēc restart

```bash
docker compose -f docker-compose.standalone.yml exec app php artisan migrate --seed --force
```

### Playwright testi neizdodas

```bash
# Pārinstalē pārlūkus:
npx playwright install --with-deps chromium

# Palielini timeout (lēnam datoram):
npx playwright test --timeout=120000
```
