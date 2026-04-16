# Nākotnes darbs / Future Work

Šis dokuments apraksta funkcionalitāti, kas tika apzināta kā vēlama, bet
netika pilnībā pabeigta pašreizējā PKE kvalifikācijas darba ietvaros.

## 2.2.21 — Reāllaika tiešsaistes multiplayer

**Statuss:** Nerealizēts (vienīgā nerealizētā funkcionālā prasība)  
**Aptuvenais darba apjoms:** 3-5 dienas

Šī prasība prasa WebSocket infrastruktūru, kas pārsniedz PKE darba apjomu.

**Ieteicamā implementācija:**
1. `composer require laravel/reverb` — Laravel iebūvētais WebSocket serveris
2. Datu bāze: `match_rooms` un `match_moves` tabulas ar migrācijām
3. Backend: `MatchController` (create/join/resign), `GameMoveEvent` broadcast events
4. Frontend: `npm install laravel-echo pusher-js`, `pages/online.vue` ar reāllaika
   galdiņu, tērzēšanu un laika kontroli
5. Gājienu validācija servera pusē — klienta puse nevar būt uzticama

## Gaišā tēma — pilnīga integrācija

**Statuss:** Pamats ieviests (CSS mainīgie, tēmas pārslēgšana, galdiņa elementu adaptācija)  
**Atlikušais darbs:** ~4 stundas

Gaišā tēma ir funkcionāla galvenajiem izkārtojuma elementiem (galvene, fons,
kājene), bet atsevišķi komponenti (piemēram, `GameAnalysis`, `puzzles.vue`,
`scenario.vue`) joprojām izmanto tiešās Tailwind klases (piemēram, `bg-zinc-900/50`),
kas neatbild uz tēmas mainīgajiem. Pilnīgai integrācijai nepieciešams:

1. Visus `bg-zinc-900`, `bg-black/40`, `text-white`, `border-white/5` u.c.
   aizstāt ar `theme-bg-surface`, `theme-text`, `theme-border` u.c.
2. Pievienot vairāk CSS mainīgo sekundārajiem toņiem
3. Pārbaudīt kontrastu gaišajā režīmā visos 25+ Vue komponentēs

## vue-i18n — pilnīga lokalizācija

**Statuss:** Infrastruktūra ieviesta (vue-i18n, LV/EN lokāļu faili, valodas pārslēdzējs)  
**Atlikušais darbs:** ~6 stundas

Pašreiz `$t()` ir piemērots `app.vue`, `login.vue` un galvenei — tas demonstrē
sistēmas darbspēju. Pilnīgai lokalizācijai nepieciešams:

1. Visās 25+ Vue lapās un komponentēs aizstāt cietkodētos latviešu tekstus ar `$t('key')`
2. Pievienot trūkstošos atslēgu-vērtību pārus `lv.json` un `en.json` failos
3. Backend API kļūdu ziņojumus lokalizēt (pašreiz tie ir latviešu valodā)
