# Nākotnes darbs / Future work

Šis dokuments apraksta funkcionalitāti, kas tika apzināta kā vēlama, bet
netika pilnībā pabeigta pašreizējā PKE kvalifikācijas darba ietvaros.
Visas funkcionālās un nefunkcionālās prasības ir izpildītas (sk. COMPLIANCE.md);
šeit aprakstīti papildu uzlabojumi, kas pārsniedz PKE prasību kopumu.

## Gaišā tēma — pilnīga vizuālā pārbaude

**Statuss:** CSS mainīgo pārklājums ieviests (549 rindiņas app.css, katrs Tailwind
lietojums pārrakstīts `[data-theme="light"]` blokā); tēma pārslēdzas dinamiski.

**Atlikušais darbs:** ~2 stundas — vizuāla pārbaude katrā no 22 lapām gaišajā
režīmā un smalkumu korekcija (piemēram, `box-shadow` toņi, `ring` krāsas
taktilajām pogām).

## vue-i18n — pilnīga lokalizācija

**Statuss:** Infrastruktūra ieviesta (vue-i18n, LV/EN lokāļu faili, valodas
pārslēdzējs, `useLocalized` composable ar testiem).

**Atlikušais darbs:** ~6 stundas — visās 22+ Vue lapās aizstāt cietkodētos
latviešu tekstus ar `$t('key')` un papildināt `lv.json` / `en.json`.

## Mašīnmācīšanās kļūdu klasifikators

**Statuss:** Nav uzsākts.

Pašreizējā `ExplanationGenerator` izmanto uz veidņu bibliotēku balstītus
skaidrojumus. ~15% pozīciju skaidrojums ir pārāk vispārīgs (sk. secinājumu
Nr. 4). Nākotnes virziens: apmācīt nelielu klasifikatoru (piemēram, XGBoost),
kas ieejas pazīmēs izmanto materiāla starpību, figūru aktivitāti, karaļa
drošību un atklātnes fāzi, lai izvēlētos precīzāku veidni.

## Mobilā lietotne

**Statuss:** Nav uzsākts.

React Native vai Flutter lietotne, kas izmanto esošo REST API. Offline
analīzes atbalsts caur Stockfish WASM bundli.

## Trenera — audzēkņa sadarbība

**Statuss:** Nav uzsākts.

Funkcija, kurā treneris redz savu audzēkņu partijas un progresiju, var
atstāt anotācijas un veidot individuālus treniņu plānus.
