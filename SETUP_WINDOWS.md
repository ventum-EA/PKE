# Windows iestatīšanas ceļvedis — PKE demo

Divi varianti: **A ceļš** (Laragon — portatīvs, strādā no USB, nav vajadzīgas admin tiesības) un **B ceļš** (Docker Desktop — tīrākais, bet vajag instalēšanu).

Ieteicams: **mājās izmēģini abus, skolā izmanto to, kas strādā.**

---

## A ceļš — Laragon (ieteicams skolai)

### Ko lejupielādēt iepriekš (mājās)

| Kas | No kurienes | Aptuveni |
|---|---|---|
| Laragon Full | https://laragon.org/download/index.html → **Laragon Full** | ~180 MB |
| Node.js LTS | https://nodejs.org → **Windows Installer (.msi)** vai **Portable (.zip)** | ~35 MB |
| Projekta ZIP | Tavs `PKE.zip` fails | ~160 MB |

Laragon Full ietver: PHP 8.x, MySQL 8, Apache, Composer, Git, HeidiSQL.

### 1. Sagatavo Laragon

```
1. Izpako Laragon uz C:\laragon (vai USB: E:\laragon)
2. Palaid laragon.exe
3. Nospied "Start All" — iededzas zaļais Apache + MySQL
4. Pārbaudi: atver pārlūkā http://localhost → "Laragon" lapa
```

### 2. Pārbaudi PHP un Composer

Atver **Laragon → Terminal** (iekšējais terminālis, kur PATH jau ir iestādīts):

```bash
php -v          # Vajag 8.2+
composer -V     # Vajag 2.x
node -v         # Vajag 18+ (ja nav, instalē Node.js atsevišķi)
npm -v
```

Ja `node` nav atrasts — instalē Node.js, vai arī pieliec ceļu Laragon iestatījumos:
`Laragon → Menu → Preferences → Path` un pievieno Node.js mapi.

### 3. Izveido datubāzi

Atver **HeidiSQL** (nāk līdzi ar Laragon) vai Laragon Terminal:

```bash
mysql -u root -e "CREATE DATABASE chess_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Iestatīt projektu

```bash
cd C:\laragon\www          # vai kur vēlies
# Izpako PKE.zip šeit, lai sanāk C:\laragon\www\PKE

cd PKE

# Konfigurācija
copy .env.example .env

# Atver .env ar Notepad un maini šādas rindiņas:
#   DB_HOST=127.0.0.1       (nevis "mysql" — tas ir Docker nosaukums)
#   DB_PORT=3306
#   DB_DATABASE=chess_platform
#   DB_USERNAME=root
#   DB_PASSWORD=              (Laragon MySQL pēc noklusējuma bez paroles)
#   REDIS_HOST=127.0.0.1     (vai nomaini CACHE_STORE=file un QUEUE_CONNECTION=sync)
#   MAIL_HOST=127.0.0.1
```

### 5. Uzstādi backend

```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

Ja `migrate` kļūda par savienojumu — pārbaudi, ka MySQL ir ieslēgts Laragon.

### 6. Uzstādi frontend

```bash
npm install
npm run build
```

### 7. Palaid

**Vienkāršais veids (demo):**
```bash
php artisan serve --port=80
```
Atver http://localhost — gatavs!

**Pilnais veids (ar queue + Reverb):**
```bash
# Terminālis 1:
php artisan serve

# Terminālis 2:
php artisan queue:listen --tries=1

# Terminālis 3 (ja vajag multiplayer):
php artisan reverb:start

# Terminālis 4 (ja gribi hot-reload izstrādē):
npm run dev
```

### 8. Demo konts

Seeder izveido administratora kontu:
```
E-pasts:  admin@chess.local
Parole:   password
```

Vai reģistrē jaunu kontu caur http://localhost/register.

---

## B ceļš — Docker Desktop

### Priekšnosacījumi

- **Docker Desktop** instalēts un darbojas (vajag admin tiesības vienreiz)
- **WSL 2** ieslēgts (Docker Desktop to piedāvā instalēt)

### 1. Palaid

```bash
cd PKE
copy .env.example .env

# Pirmā reize — var ilgt 5-10 min
docker compose up -d

# Composer iekšā konteinerī
docker compose exec laravel.test composer install
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed

# Frontend
docker compose exec laravel.test npm install
docker compose exec laravel.test npm run build

# Stockfish servera analīzei (neobligāts)
docker compose exec laravel.test bash docker/install-stockfish.sh
```

### 2. Atver

```
Platforma:  http://localhost
Mailpit:    http://localhost:8025   (paroles atjaunošanas e-pasti)
```

### 3. Apstāt / Restartēt

```bash
docker compose down          # apstāt
docker compose up -d         # restartēt
docker compose down -v       # apstāt un IZDZĒST datubāzi
```

---

## Biežākās problēmas

### "Port 80 is already in use"

Kaut kas cits aizņem portu 80. Risinājumi:
```bash
# Nomainīt portu uz 8000:
php artisan serve --port=8000
# un atver http://localhost:8000

# Vai Docker — .env failā:
APP_PORT=8080
```

### "SQLSTATE[HY000] [2002] Connection refused"

`.env` failā `DB_HOST` nav pareizs:
- **Laragon:** `DB_HOST=127.0.0.1`
- **Docker:** `DB_HOST=mysql`

### "npm: command not found"

Node.js nav PATH. Laragon terminālis to parasti atrisina. Ja ne:
```bash
# Pārbaudi, kur Node ir
where node

# Ja nav — lejupielādē no https://nodejs.org un instalē
```

### "Class not found" vai "Vite manifest not found"

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
npm run build                # obligāts pirms demo!
```

### Stockfish WASM neielādējas

Pārlūkā konsolē (F12) parādās "Failed to load stockfish.wasm". Tas ir 67 MB fails — dažos serveros ir body size limits. Risinājums:
- Pārliecinies, ka `public/stockfish.wasm` eksistē (67 MB)
- Apache: `LargeFileLimit` vai `php.ini` → `upload_max_filesize` nav saistīts, fails tiek servēts statiski

### Redis nav pieejams

Ja skolā nav Redis, `.env` failā:
```
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
```
Viss strādās, tikai queue izpildīsies sinhroni (lēnāk).

---

## Demo plāns — ko rādīt eksāmenā

Ieteicamā secība (15-20 min):

1. **Reģistrācija** → jauns konts, uzrāda validāciju
2. **PGN augšupielāde** → ielīmē PGN, partija saglabājas
3. **Stockfish analīze** → analizē partiju, rāda klasifikāciju + LV skaidrojumus
4. **Treniņš no kļūdām** → ģenerē uzdevumus no kļūdainām pozīcijām
5. **Spēle pret Stockfish** → spēlē 5-6 gājienus ar grūtību 5
6. **Atklātnes** → pārlūko ECO atklātnes, praktizē gājienus
7. **Multiplayer** → atver 2 pārlūkos, izveido spēli, spēlē 3 gājienus
8. **Eksports** → PDF eksports no analīzes
9. **Profils** → gaišā/tumšā tēma, valodas maiņa LV↔EN
10. **GDPR** → konta dzēšana ar paroles apstiprinājumu

**PGN ko ielīmēt demo laikā:**
```
1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 d6 8. c3 O-O 9. h3 Nb8 10. d4 Nbd7 1-0
```

### Iepriekš sagatavo

- [ ] Laragon/Docker darbojas un platforma ir pieejama
- [ ] `npm run build` ir izpildīts (nevis `npm run dev`)
- [ ] Demo PGN ir nokopēts clipboard
- [ ] Divi pārlūka logi atvērti multiplayer demo
- [ ] Admin konts pieejams (admin@chess.local / password)
