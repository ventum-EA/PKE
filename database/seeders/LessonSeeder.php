<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonPuzzle;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        Lesson::query()->delete();
        LessonPuzzle::query()->delete();

        $lessons = [
            ['basics-01','basics','The Chessboard','Šaha galdiņš','Iepazīstiet 64 laukumus, līnijas, rindas un diagonāles.',1,
                'Šaha galdiņš ir 8×8 laukumu režģis — kopā 64 laukumi, pārmaiņus gaišie un tumšie.\n\nLĪNIJAS (vertikālas): apzīmētas ar burtiem a–h no kreisās uz labo pusi.\nRINDAS (horizontālas): numurētas ar cipariem 1–8 no apakšas uz augšu.\nDIAGONĀLES: laukumu virknes pa slīpumu. Katram laukumam ir unikāla adrese — piemēram, e4 ir e-līnijas 4. rinda.\n\nSvarīgi laukumi:\n- CENTRS: d4, d5, e4, e5 — galdiņa "sirds", kur figūras ir visaktīvākās\n- KARAĻA FLANGS: laukumi labajā pusē (f, g, h līnijas)\n- DĀMAS FLANGS: laukumi kreisajā pusē (a, b, c līnijas)\n\nBaltais vienmēr sāk pirmo gājienu. Galdiņš vienmēr novietots tā, lai baltajam apakšējā labajā stūrī būtu GAIŠS laukums (h1).','♟','gray',
                [
                    ['rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1','e2e4','Bandinieks e2 dodas uz e4 — centru. Šī ir vispopulārākā spēles atklāšana.','["Virziet bandinieku uz galdiņa centru"]'],
                ]],
            ['basics-02','basics','The Pawn','Bandinieks','Mazākā figūra ar lielāko potenciālu — kā bandinieks kustas un sit.',1,
                'Bandinieks ir unikāla figūra — tā kustas un sit DAŽĀDI:\n\nKUSTĒŠANĀS: tikai uz PRIEKŠU, vienu laukumu. No SĀKUMA pozīcijas var iet 2 laukumus.\nSITIENA: pa DIAGONĀLI uz priekšu — vienu laukumu pa kreisi vai pa labi.\n\nBandinieks nevar iet atpakaļ — katrs gājiens ir neatgriezenisks!\n\nPAAUGSTINĀŠANA: ja bandinieks sasniedz pretējo malu (8. rindu baltajam, 1. rindu melnajam), tas OBLIGĀTI kļūst par dāmu, torni, laidni vai zirgu. Parasti izvēlas dāmu.\n\nEN PASSANT (garāmejot): ja pretinieka bandinieks tikko gāja 2 laukumus un nostājās BLAKUS jūsu bandiniekam, jūs varat to sist "garāmejot" — jūsu bandinieks dodas uz laukumu AIZMUGURĒ pretinieka bandiniekam. Šo var darīt TIKAI uzreiz nākamajā gājienā.\n\nBandinieka vērtība: 1 punkts, bet galotnē brīvais bandinieks (kas var paaugstināties) ir nenovērtējams.','♟','gray',
                [
                    ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1','d7d5','Melnais bandinieks iet 2 laukumus uz priekšu no sākuma pozīcijas — d7 uz d5, apstrīdot centru.','["No sākuma pozīcijas bandinieks var iet 2 laukumus", "Kontrolējiet centru!"]'],
                    ['rnbqkbnr/ppp1pppp/8/3pP3/8/8/PPPP1PPP/RNBQKBNR w KQkq d6 0 2','e5d6','En passant! Baltā bandinieks e5 sit melnā bandinieku d5 "garāmejot" — dodas uz d6.','["Melnais tikko gāja d7-d5 garām jūsu bandiniekam e5", "Šis ir en passant — sitiena pa diagonāli garāmejot"]'],
                ]],
            ['basics-03','basics','The Knight','Zirgs','Vienīgā figūra, kas lec pāri citiem — "L" formas gājiens.',1,
                'Zirgs kustas unikāli — "L" formā: 2 laukumus vienā virzienā un 1 perpendikulāri.\n\nGALVENĀS ĪPAŠĪBAS:\n- VIENĪGĀ figūra, kas var PĀRLĒKT pāri citām figūrām\n- Zirgs VIENMĒR maina laukuma krāsu (no gaiša uz tumšu un otrādi)\n- Centrā zirgs kontrolē 8 laukumus, malā — tikai 2-4\n- Zirgs ir spēcīgāks SLĒGTĀS pozīcijās (kur bandinieki bloķē laidņus un torņus)\n\nIEGAUMĒŠANAS TRIKS: iedomājiet burtu "L" — 2 laukumi taisni, tad 1 uz sāniem. Vai otrādi — 1 taisni, 2 uz sāniem.\n\nVērtība: 3 punkti (apmēram tikpat, cik laidnis).','♞','amber',
                [
                    ['8/4k3/8/4N3/8/8/8/4K3 w - - 0 1','e5f7','Zirgs no e5 lec uz f7 — divi uz augšu, viens pa labi. Centrā zirgs kontrolē 8 laukumus!','["Zirgs kustas L formā", "2 laukumi vienā virzienā, 1 uz sāniem"]'],
                    ['r1bqkbnr/pppppppp/2n5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 1 2','g1f3','Zf3 attīsta zirgu un uzbrūk e5 laukumam. Zirgs bieži ir pirmā figūra, ko attīsta.','["Attīstiet zirgu uz aktīvu laukumu", "f3 kontrolē centru un neaizšķērso citas figūras"]'],
                ]],
            ['basics-04','basics','The Bishop','Laidnis','Diagonāļu valdnieks — gaišlaukumu un tumšlaukumu laidņi.',1,
                'Laidnis kustas pa DIAGONĀLĒM — jebkuru attālumu, kamēr ceļā nav citu figūru.\n\nGALVENĀS ĪPAŠĪBAS:\n- Katrs laidnis VIENMĒR paliek uz savas krāsas laukumiem (gaišais laidnis — gaišajos, tumšais — tumšajos)\n- LAIDŅU PĀRIS ir spēcīgāks nekā divi zirgi vairumā pozīciju\n- Laidnis ir spēcīgāks ATKLĀTĀS pozīcijās (bez bandinieku barjerām diagonālēs)\n- Laidnis kontrolē līdz 13 laukumiem no centra\n\n"LABS" vs "SLIKTS" laidnis: laidnis ir "labs", ja tas nav ierobežots saviem bandiniekiem. Ja jūsu bandinieki stāv uz tās pašas krāsas laukumiem kā laidnis — laidnis ir "slikts" (bloķēts).\n\nVērtība: 3 punkti (nedaudz vairāk nekā zirgs atklātās pozīcijās).','♝','blue',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 1 2','f1c4','Laidnis dodas no f1 uz c4 — garajā diagonālē, mērķējot uz vājo f7 laukumu.','["Laidnis kustas pa diagonāli", "c4 ir aktīvs laukums, kas kontrolē centru un f7"]'],
                ]],
            ['basics-05','basics','The Rook','Tornis','Horizontāļu un vertikāļu saimnieks — spēcīgs uz atvērtām līnijām.',1,
                'Tornis kustas pa HORIZONTĀLĒM (rindām) un VERTIKĀLĒM (līnijām) — jebkuru attālumu.\n\nGALVENĀS ĪPAŠĪBAS:\n- Tornis ir visspēcīgākais uz ATVĒRTĀM līnijām (līnijām bez bandiniekiem)\n- Divi torņi vienā līnijā vai rindā ir ārkārtīgi spēcīgi (tie aizsargā viens otru)\n- Tornis 7. rindā (baltajam) vai 2. rindā (melnajam) uzbrūk pretinieka bandiniekiem no aizmugures\n- Torņi parasti aktivizējas VĒLĀK spēlē (pēc rokādes)\n\nATKLĀTĀ LĪNIJA: līnija bez NEVIENA bandinieka. Ievietojiet torni tajā!\nPUSATVĒRTĀ LĪNIJA: līnija ar tikai VIENU bandinieku. Arī laba tornim.\n\nVērtība: 5 punkti.','♜','emerald',
                [
                    ['r3k2r/pppppppp/8/8/8/8/PPPPPPPP/R3K2R w KQkq - 0 1','e1g1','Rokāde! Tornis no h1 dodas uz f1, kur tas kontrolēs atvērto f-līniju.','["Rokāde aktivizē torni un drošina karali vienlaicīgi"]'],
                ]],
            ['basics-06','basics','The Queen','Dāma','Visspēcīgākā figūra — apvieno torņa un laidņa spējas.',1,
                'Dāma kustas jebkurā virzienā — horizontāli, vertikāli un diagonāli — jebkuru attālumu. Tā apvieno torņa un laidņa kustību.\n\nGALVENĀS ĪPAŠĪBAS:\n- Vērtība: 9 punkti — gandrīz tikpat kā divi torņi!\n- Dāma ir BĪSTAMA, bet arī IEVAINOJAMA — pretinieks vienmēr cenšas to uzbrukt\n- NEKAD neizvediet dāmu pārāk agri atklātnē — pretinieka figūras to dzīs, zaudējot laiku\n\nKĻŪDA #1: agra dāmas izveide. Iesācēji bieži izved dāmu 2.-3. gājienā. Problēma: katrs pretinieka gājiens, kas uzbrūk dāmai, arī attīsta figūru. Jūs zaudējat laiku.\n\nPADOMS: dāmu aktivizējiet PĒC zirgu un laidņu attīstīšanas un rokādes.','♛','pink',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/2B1P3/8/PPPP1PPP/RNBQK1NR w KQkq - 0 2','d1h5','Dāma uz h5 — agresīvs gājiens, kas draud Dxf7#. Bet melnais var aizsargāties ar Zf6!','["Dāma var doties jebkurā virzienā", "h5 draud f7, bet uzmanību — tā var būt pārāk agra!"]'],
                ]],
            ['basics-07','basics','The King','Karalis','Vissvarīgākā figūra — ja viņš ir matēts, jūs zaudējat.',1,
                'Karalis kustas vienu laukumu jebkurā virzienā — bet tas ir vissvarīgākā figūra spēlē.\n\nNOTEIKUMI:\n- Karalis NEDRĪKST iet uz laukumu, ko kontrolē pretinieka figūra\n- Divi karaļi NEDRĪKST stāvēt blakus laukumos\n- Karalis nevar "ignorēt" šahu — VIENMĒR jāreaģē\n\nROKĀDE: vienīgais gājiens, kur karalis kustas 2 laukumus. Īsā rokāde (O-O): karalis e1→g1. Garā rokāde (O-O-O): karalis e1→c1.\n\nRokādes nosacījumi:\n1. Ne karalis, ne tornis nav iepriekš gājis\n2. Starp tiem nav figūru\n3. Karalis NAV šahā\n4. Karalis neiet CAUR laukumu, kas ir šahā\n\nGALOTNĒ karalis kļūst par AKTĪVU figūru — tas var un tam vajag piedalīties cīņā!','♔','red',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','e1g1','Rokāde! Karalis dodas drošībā aiz bandinieku vairoga, tornis aktivizējas.','["Rokāde — vienīgais gājiens, kur 2 figūras kustas vienlaicīgi"]'],
                ]],
            ['basics-08','basics','Check & Checkmate','Šahs un mats','Kā dot šahu un kā to pārvērst matā.',1,
                'ŠAHS: jūsu figūra uzbrūk pretinieka karalim. Pretiniekam jāreaģē!\n\n3 veidi, kā izvairīties no šaha:\n1. PĀRVIETOT karali\n2. BLOĶĒT šahu ar citu figūru\n3. SIST figūru, kas dod šahu\n\nJa NEVIENS no šiem 3 veidiem nedarbojas → MATS (checkmate). Spēle beidzas.\n\nDUBULTŠAHS: divas figūras vienlaicīgi dod šahu. Pretinieks NEVAR bloķēt vai sist — JĀPĀRVIETO karalis. Tas ir visspēcīgākais uzbrukuma veids.\n\nPADOMS: vienmēr pajautājiet — "vai mans gājiens dod šahu?" Šahs spiež pretinieku reaģēt jūsu noteikumos.','♔','red',
                [
                    ['r1bqkb1r/pppp1ppp/2n2n2/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 4 4','h5f7','Dxf7# — MATS! Dāma sit f7 ar laidņa atbalstu. Karalis nevar ne bēgt, ne bloķēt, ne sist.','["f7 ir vājākais laukums — to aizsargā TIKAI karalis", "Dāma + laidnis sadarbojās"]'],
                ]],
            ['basics-09','basics','Stalemate','Pats (neizšķirts)','Kā izvairīties no pata — biežākā iesācēju kļūda galotnē.',1,
                'PATS: jūsu gājiena kārta, bet nav NEVIENA likumīga gājiena, un karalis NAV šahā. Rezultāts: NEIZŠĶIRTS.\n\nPats ir SLIKTI tam, kuram ir materiāla PĀRSVARS — jūs varējāt uzvarēt, bet tā vietā — neizšķirts!\n\nBIEŽĀKĀS PATA SITUĀCIJAS:\n1. Dāma iesloga karali stūrī pārāk agresīvi\n2. Bandinieks bloķē karaļa pēdējo izeju\n3. Visi pretinieka bandinieki ir bloķēti un karalis ieslodzīts\n\nKĀ IZVAIRĪTIES:\n- Pirms katra gājiena pajautājiet: "vai pretiniekam paliek kāds likumīgs gājiens?"\n- Neaizslēdziet VISAS izejas vienlaicīgi\n- Galotnē ar dāmu — kontrolējiet tikai DAŽAS līnijas, ne visas','⚠','amber',
                [
                    ['7k/8/6QK/8/8/8/8/8 w - - 0 1','g6g7','Dg7# ir MATS — pareizi! Bet Dg8?? būtu PATS (melnajam nav gājienu un NAV šahā). Uzmanību!','["Dodiet matu, nevis patu!", "Dāmai jādod šahs uz laukumu, no kura karalis nevar bēgt"]'],
                ]],
            ['basics-10','basics','Piece Values & Trades','Figūru vērtības un apmaiņas','Kad apmaiņa ir izdevīga un kad — nē.',1,
                'Katra figūra ir noteiktu punktu "vērta":\n♙ Bandinieks = 1  ♞ Zirgs = 3  ♝ Laidnis = 3  ♜ Tornis = 5  ♛ Dāma = 9\n\nIZDEVĪGA apmaiņa: sitat VĒRTĪGĀKU figūru ar MAZĀK vērtīgu.\nPiemērs: Zirgs (3) sit dāmu (9) = +6 punkti. Lieliski!\n\nNEIZDEVĪGA apmaiņa: zaudējat vairāk nekā iegūstat.\nPiemērs: Tornis (5) sit zirgu (3) un tiek sists = -2 punkti. Slikti.\n\nVIENĀDA apmaiņa: zirgs pret zirgu, tornis pret torni = 0.\nKad tas ir labi? Ja jums ir materiāla pārsvars — apmainiet figūras un vinniet galotnē!\n\nIZŅĒMUMI: dažreiz "sliktu" apmaiņu kompensē citi faktori — pozīcijas priekšrocība, uzbrukums karalim, bandinieku struktūra.','⚖','gray',
                [
                    ['rnbqkb1r/pppppppp/5n2/8/4P3/2N5/PPPP1PPP/R1BQKBNR b KQkq - 2 2','f6e4','Zirgs sit bandinieku e4 — iegūst materiālu! Bet uzmanību: c3 zirgs var atbildēt.','["Vai šis sitiens ir drošs?", "Kas notiek pēc Zxe4?"]'],
                ]],
            ['tac-01','tactics','The Fork','Dakša','Viena figūra uzbrūk divām vienlaicīgi.',1,
                'Dakša ir taktisks motīvs, kur VIENA figūra uzbrūk DIVĀM vai vairākām pretinieka figūrām vienlaicīgi. Pretinieks var aizsargāt tikai vienu — otru jūs iegūstat.\n\nVISBIEŽĀKĀ: zirga dakša — zirgs uzbrūk karalim + dāmai. Karalis JĀPĀRVIETO, dāma zaudēta.\nBet dakšu var veikt JEBKURA figūra — arī bandinieks!\n\nKĀ MEKLĒT: skatieties, kur jūsu figūra var nostāties tā, lai tā "redzētu" divas pretinieka figūras vienlaicīgi.','⚔','amber',
                [
                    ['r1bqkb1r/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq - 3 3','c6d4','Zirgs uz d4 uzbrūk gan laidnim c4 (pēc sitiena), gan e2 bandiniekam. Dakša!','["Vai zirgs var nostāties laukumā, kas uzbrūk divām figūrām?"]'],
                    ['r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','d2d4','d4 uzbrūk e5 un c5 laukumam (ja laidnis tur atrastos) — bandinieka dakša!','["Kāds bandinieka gājiens uzbrūk diviem laukumiem vienlaicīgi?"]'],
                ]],
            ['tac-02','tactics','Knight Fork Mastery','Zirga dakšas meistarklase','Iemācieties atpazīt zirga dakšas iespējas.',1,
                'Zirga dakša ir VISBIEŽĀKAIS taktiskais motīvs. Kāpēc?\n- Zirgs lec pāri figūrām — grūti paredzēt\n- Zirga "L" formas gājiens ir neparasts — viegli neievērot\n- Zirgs uz c2 vai c7 ir bīstams — uzbrūk karalim un tornim vienlaicīgi\n\nKLASISKIE DAKŠAS LAUKUMI:\n- c7 (vai c2): karalis + tornis\n- f7 (vai f2): karalis + dāma\n- e6 / d3: dāma + tornis\n\nPADOMS: vienmēr pārbaudiet — "vai mans zirgs var LĒKT kaut kur un uzbrukt 2+ figūrām?','⚔','amber',
                [
                    ['r3k2r/ppp2ppp/3p4/4N3/2B5/8/PPP2PPP/R3K2R w KQkq - 0 1','e5f7','Zxf7! Zirgs sit bandinieku un uzbrūk karalim e8 + tornim h8. Klasiskā dakša!','["Zirgs e5 var lēkt uz f7", "f7 ir starp karali un torni"]'],
                ]],
            ['tac-03','tactics','The Pin','Piespraušana','Figūra nevar kustēties, jo aiz tās ir vērtīgāka figūra.',1,
                'Piespraušana: jūsu tālsitējs (laidnis, tornis vai dāma) uzbrūk figūrai, aiz kuras ir VĒRTĪGĀKA figūra vai karalis.\n\nABSOLŪTĀ piespraušana: aiz piespraustās figūras ir KARALIS — figūra NEDRĪKST kustēties!\nRELATĪVĀ piespraušana: aiz tās ir cita vērtīga figūra — var kustēties, bet zaudēs.\n\nKĀ IZMANTOT:\n1. Atrodiet piesprausto figūru\n2. Uzbrūciet tai ar bandiniekiem vai citām figūrām\n3. Pretinieks nevar aizbēgt!','📌','blue',
                [
                    ['rn1qkbnr/ppp1pppp/8/3p4/4P1b1/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3','f1e2','Laidnis uz e2 aizsargā piesaisto zirgu f3. Alternatīva: h3 dzen laidni prom.','["Zirgs f3 ir piesprausts — kā to aizsargāt?"]'],
                ]],
            ['tac-04','tactics','The Skewer','Šķēres','Uzbrukums vērtīgākai figūrai — tā atkāpjas, un mēs sitat mazāk vērtīgo.',2,
                'Šķēres (skewer) ir PRETĒJAS piespraušanai. Uzbrukums vērtīgākai figūrai (priekšā), aiz kuras ir mazāk vērtīga figūra (aizmugurē).\n\nPiemērs: Tornis uzbrūk KARALIM pa līniju, aiz karaļa stāv DĀMA. Karalis JĀPĀRVIETO → tornis sit dāmu.\n\nŠķēres darbojas TIKAI ar tālsitējiem (laidnis, tornis, dāma), jo tiem jāredz CAURI abām figūrām.','🔪','red',
                [
                    ['4r1k1/5ppp/8/8/8/8/1B3PPP/4R1K1 w - - 0 1','b2g7','Lg7+! Laidnis dod šahu karalim. Pēc karaļa gājiena — laidnis sit torni e8.','["Vai laidnis var dot šahu un redzēt torni aiz karaļa?"]'],
                ]],
            ['tac-05','tactics','Discovered Attack','Atklātais uzbrukums','Viena figūra atkāpjas un atklāj citas figūras uzbrukumu.',2,
                'Atklātais uzbrukums: jūs pārvietojat vienu figūru, un aiz tās esošā figūra "atklājas" — uzbrūk pretinieka figūrai.\n\nATKLĀTAIS ŠAHS: atklātā figūra dod šahu. Tas ir sevišķi spēcīgs, jo pretiniekam JĀREAĢĒ uz šahu, kamēr jūsu pārvietotā figūra var darīt jebko.\n\nDUBULTŠAHS: gan pārvietotā, gan atklātā figūra dod šahu. Vienīgā aizsardzība — karaļa pārvietošana!','💥','purple',
                [
                    ['r1bqr1k1/pppp1ppp/2n5/3np3/2B5/5N2/PPPP1PPP/RNBQR1K1 w - - 0 1','c4d5','Lxd5! Laidnis sit zirgu un atklāj torņa e1 uzbrukumu uz melnā torni e8.','["Ko atklāj laidņa sitiens?", "Skatieties e-līniju pēc laidņa gājiena"]'],
                ]],
            ['tac-06','tactics','Double Attack','Dubultuzbrukums','Viens gājiens rada draudus divos virzienos.',1,
                'Dubultuzbrukums: jūsu gājiens rada DIVUS draudus vienlaicīgi. Pretinieks var atvairīt tikai vienu.\n\nAtšķirība no dakšas: dakšā VIENA figūra uzbrūk divām. Dubultuzbrukumā viens GĀJIENS rada divus draudus (var būt dažādu figūru draudus).\n\nPiemērs: jūs uzbrūkat ar zirgu un vienlaicīgi draudat ar bandinieka paaugstināšanu.','⚡','amber',
                [
                    ['r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','d2d4','d4 uzbrūk e5 bandiniekam UN atver diagonāli c1 laidnim — dubultuzbrukums!','["Kāds gājiens uzbrūk bandiniekam UN atver jaunu figūru?"]'],
                ]],
            ['tac-07','tactics','Remove the Defender','Likvidē aizsargu','Sitiet figūru, kas aizsargā svarīgu laukumu vai figūru.',2,
                'Aizsarga likvidēšana: ja pretinieka figūra aizsargā kaut ko svarīgu — sitiet TO figūru!\n\nPLĀNS:\n1. Atrodiet mērķi (neaizsargāts laukums, figūra vai mats)\n2. Pajautājiet: "KAS to aizsargā?"\n3. Likvidējiet aizsargu — mērķis kļūst pieejams\n\nPiemērs: Zirgs aizsargā d5 bandinieku. Jūs sitat zirgu. d5 bandinieks tagad ir neaizsargāts.','🎯','red',
                [
                    ['r2qkb1r/ppp2ppp/2n1bn2/3pp1B1/3PP3/2N2N2/PPP2PPP/R2QKB1R w KQkq - 0 1','g5f6','Lxf6! Sit zirgu, kas aizsargāja d5. Pēc gxf6, exd5 iegūst bandinieku un sabojā struktūru.','["Kas aizsargā d5 bandinieku?", "Likvidējiet aizsargu!"]'],
                ]],
            ['tac-08','tactics','Back Rank Tactics','Pēdējās rindas taktika','Karalis ieslodzīts aiz saviem bandiniekiem — bīstami!',1,
                'Pēdējās rindas mats: karalis stāv pēdējā rindā, aiz saviem bandiniekiem, un tornis vai dāma ielaužas pa atvērtu līniju — MATS.\n\nAIZSARDZĪBA — "lodziņš" (luft): virziet vienu bandinieku uz priekšu (h3 vai g3), lai karalim būtu izeja.\n\nBĪSTAMI: ja abiem spēlētājiem ir vāja pēdējā rinda, sitieni uz šo rindu var būt taktiskās kombinācijas pamats.','🏰','emerald',
                [
                    ['6k1/5ppp/8/8/8/8/5PPP/4R1K1 w - - 0 1','e1e8','Te8# — MATS! Tornis ielaužas pa atvērtu e-līniju. Karalis ieslodzīts aiz f7, g7, h7.','["Melnā karalis ir ieslodzīts — izmantojiet atvērto līniju"]'],
                    ['3r2k1/5ppp/8/8/8/8/5PPP/3RR1K1 w - - 0 1','e1e8','Te8+! Šahs! Pēc Txe8, Txe8# — dubulttorņu mats pēdējā rindā.','["Izmantojiet abus torņus", "Pēc apmaiņas otrs tornis turpina"]'],
                ]],
            ['tac-09','tactics','Deflection','Novirzīšana','Piespiediet aizsardzības figūru atstāt savu pozīciju.',2,
                'Novirzīšana: jūs piespiedat pretinieka figūru pārvietoties, tā atstājot neaizsargātu citu figūru vai laukumu.\n\nPiemērs: pretinieka tornis aizsargā pēdējo rindu. Jūs uzbrūkat tornim no sāniem — tas jāpārvieto. Tagad pēdējā rinda ir neaizsargāta!\n\nNovirzīšana bieži ietver UPURI — jūs "piedāvājat" figūru, kas pretiniekam "jāpieņem", atstājot svarīgāku laukumu.','↪','purple',
                [
                    ['3r2k1/5ppp/8/8/2b5/5N2/5PPP/3R2K1 w - - 0 1','d1d8','Txd8+! Sit torni ar šahu. Pēc Kxd8 vai Txd8, laidnis c4 paliek neaizsargāts.','["Tornis var sist ar šahu — ko melnais zaudē?"]'],
                ]],
            ['tac-10','tactics','Overloaded Piece','Pārslogotā figūra','Figūra, kas aizsargā pārāk daudzas lietas vienlaicīgi.',2,
                'Pārslogotā figūra veic DIVUS vai vairākus aizsardzības uzdevumus vienlaicīgi. Ja to piespiež izpildīt vienu — otrais paliek neaizsargāts.\n\nKĀ ATPAZĪT:\n1. Atrodiet figūru, kas aizsargā vairākas lietas\n2. Uzbrūciet vienai no tām\n3. Kad figūra "reaģē" — otrā lieta kļūst neaizsargāta\n\nPiemērs: dāma aizsargā torni UN pēdējo rindu. Jūs uzbrūkat tornim — dāma to aizsargā. Tagad pēdējā rinda ir atvērta!','⚡','amber',
                [
                    ['2r3k1/5ppp/8/8/8/8/5PPP/1R2R1K1 w - - 0 1','e1e8','Te8+! Tornis dod šahu. Pēc Txe8, Txe8# — pēdējās rindas mats!','["Melnā tornis aizsargā pēdējo rindu — bet vai tas paspēj?"]'],
                ]],
            ['str-01','strategy','Center Control','Centra kontrole','Kas kontrolē centru — kontrolē spēli.',1,
                'Centrs ir laukumi d4, d5, e4, e5. Figūras centrā ir AKTĪVĀKAS — tās kontrolē vairāk laukumus.\n\nZirgs centrā (d4): 8 laukumi. Zirgs stūrī (a1): 2 laukumi.\nLaidnis centrā: līdz 13 laukumiem. Laidnis stūrī: 7.\n\nKĀ KONTROLĒT:\n1. Virziet e un d bandiniekus uz centru\n2. Attīstiet figūras uz laukumiem, kas kontrolē centru (Zf3, Lc4, Zc3)\n3. Nepadodiet centru bez cīņas','🎯','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 1','d2d4','d4! Tagad baltajam ir ideālais centrs — e4+d4 kontrolē d5, e5, c5, f5.','["Kāds bandinieks papildina e4 centra kontroli?"]'],
                ]],
            ['str-02','strategy','Piece Development','Figūru attīstība','Izvediet figūras no sākuma pozīcijām — katru gājienu jaunu.',1,
                'ATTĪSTĪBA = figūru izvešana no sākuma pozīcijām uz aktīviem laukumiem.\n\nSECĪBA:\n1. Centra bandinieki (e4/d4)\n2. ZIRGI (var lēkt pāri — izved pirmos)\n3. LAIDŅI (pēc bandinieku virzīšanas — ceļš atvērts)\n4. ROKĀDE (drošina karali, aktivizē torni)\n5. Dāma un torņi (pēdējie, uz atvērtām līnijām)\n\nKĻŪDA: virzīt to pašu figūru 2+ reizes, kamēr citas vēl nav attīstītas. Katrs "papildu" gājiens ar jau attīstītu figūru = pretinieks attīsta JAUNU figūru = jūs atpaliekat.','📈','emerald',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2','g1f3','Zf3 — attīstiet zirgu! Kontrolē centru, uzbrūk e5. Pirmais zirgs, tad laidnis.','["Attīstiet jaunu figūru, nevis virziet bandinieku"]'],
                ]],
            ['str-03','strategy','King Safety','Karaļa drošība','Rokāde, bandinieku vairogs un bīstamie brīži.',1,
                'Karalis centrā ir NEDROŠS — atklātnes laikā centrs tiek atvērts, un figūras var uzbrukt no visām pusēm.\n\nROKĀDE aizsargā karali:\n- Karalis paslēpjas aiz 3 bandinieku vairoga\n- Tornis aktivizējas centrā\n\nBANDINIEKU VAIROGS: f2+g2+h2 (vai f7+g7+h7) aizsargā karali. Nevirziet šos bandiniekus bez iemesla!\n\ng3/g6 gājiens: bīstams, ja pretinieks var atvērt g-līniju. BET arī noderīgs fianketo pozīcijās (laidnis uz g2).\n\nKARALIS CENTRĀ = BĪSTAMI. Rokāde pirmajās 10 gājienos ir OBLIGĀTA gandrīz katrā partijā.','🛡','red',
                [
                    ['r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/3P1N2/PPP2PPP/RNBQK2R w KQkq - 4 4','e1g1','Rokāde! Karalis ir drošībā, tornis aktivizējas. Neatlieciet rokādi!','["Karalis centrā ir nedrošs — ko darīt?"]'],
                ]],
            ['str-04','strategy','Pawn Structure','Bandinieku struktūra','Dubultotie, izolētie un brīvie bandinieki.',2,
                'Bandinieku struktūra ir bandinieku izvietojums. Tā nosaka spēles raksturu.\n\nDUBULTOTIE bandinieki: divi jūsu bandinieki VIENĀ līnijā. Vāji, jo nevar aizsargāt viens otru.\nIZOLĒTAIS bandinieks: nav draudzīgu bandinieku BLAKUS līnijās. Vājš, jo jāaizsargā ar figūrām.\nBRĪVAIS bandinieks (passed pawn): nav pretinieka bandinieku ceļā vai blakus. Ļoti spēcīgs galotnē!\nBANDINIEKU ĶĒDE: savienotu bandinieku diagonāla līnija. Bāze (aizmugurējais) ir vājākais punkts.\n\nZELTA LIKUMS: nesabojājiet savu struktūru bez iemesla. Katra dubultošana vai izolēšana ir ilgtermiņa vājums.','♟','gray',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/3PP3/8/PPP2PPP/RNBQKBNR b KQkq - 0 2','e5d4','exd4 apmaina bandiniekus centrā. Baltajam tagad izolēts d4 bandinieks — bet tas ir aktīvs!','["Apmainiet centrā — kurš no diviem bandiniekiem sist?"]'],
                ]],
            ['str-05','strategy','Open Files for Rooks','Atvērtās līnijas torņiem','Torņi ir visspēcīgākie uz līnijām bez bandiniekiem.',2,
                'ATVĒRTĀ LĪNIJA: līnija BEZ bandinieku. Tornis vai dāma tur ir visspēcīgākā.\nPUSATVĒRTĀ LĪNIJA: ar tikai VIENU bandinieku. Arī laba.\n\nKĀ IEGŪT ATVĒRTU LĪNIJU:\n1. Apmainiet bandiniekus (exd5 cxd5 → atvērta e-līnija)\n2. Virziet bandinieku prom no līnijas\n3. Upurējiet bandinieku, lai atvērtu līniju\n\nTornis atvērtā līnijā var:\n- Ielauzties pretinieka teritorijā (7. rindā!)\n- Kontrolēt svarīgus laukumus\n- Atbalstīt bandinieku virzīšanu','♜','emerald',
                [
                    ['r1bqkb1r/ppp2ppp/2n1pn2/3p4/3PP3/2N2N2/PPP2PPP/R1BQKB1R w KQkq - 0 5','e4d5','exd5! Atveram e-līniju tornim pēc rokādes. Pēc exd5 — e-līnija ir atvērta!','["Kāds apmaiņas gājiens atver līniju jūsu tornim?"]'],
                ]],
            ['open-01','openings','Italian Game','Itāļu partija','Viena no vecākajām un visinstruktīvākajām atklātnēm.',1,
                'Itāļu partija: 1.e4 e5 2.Zf3 Zc6 3.Lc4\n\nGALVENĀ IDEJA: Laidnis uz c4 mērķē uz vājo f7 laukumu. Kopā ar Zf3, kas kontrolē centru, baltais iegūst harmonisku pozīciju.\n\nMelnā LABĀKĀ ATBILDE: 3...Lc5 — attīsta laidni un kontrolē centru.\n\nTURPINĀJUMI:\n- 4.d3 (klusais variants) — stabils, pozicionāls\n- 4.c3 (Evans Gambit sagatavošana) — agresīvs\n- 4.d4 (centrālais uzbrukums) — aktīvs\n\nŠī ir ideāla atklātne iesācējiem — māca visus atklātnes principus.','📖','emerald',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 1 2','f1c4','Lc4! Itāļu partija — laidnis mērķē uz f7, vājāko laukumu melnā pozīcijā.','["Kur laidnis var doties, lai uzbruktu f7?"]'],
                    ['r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 2 3','d2d4','d4! Centra uzbrukums. Atklāj ceļu c1 laidnim un apstrīd centru.','["Kāds bandinieka gājiens atklāj centru un aktivizē laidni?"]'],
                ]],
            ['open-02','openings','Sicilian Defense','Sicīliešu aizsardzība','Vispopulārākā melnā atbilde uz 1.e4.',2,
                'Sicīliešu aizsardzība: 1.e4 c5\n\nGALVENĀ IDEJA: melnais atbild ASIMETRISKI. c5 apstrīd centra d4 laukumu, bet neveido simetrisku pozīciju.\n\nKĀPĒC TĀ IR POPULĀRA:\n- Asimetriskas pozīcijas = vairāk izredžu uzvarēt ar melnajām\n- c5 bandinieks vēlāk var tikt apmainīts pret d4, dodot melnajam c-līniju\n- Sarežģītas pozīcijas — var pārspēt pēc sagatavotības\n\nGALVENIE VARIANTI: Najdorfas, Drakona, Ševeningenas, Svešņikova. Katrs ir vesela pasaule!','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1','c7c5','c5! Sicīliešu aizsardzība. Melnais apstrīd d4, neradot simetrisku pozīciju.','["Kāds bandinieka gājiens apstrīd centru no sāniem?"]'],
                ]],
            ['open-03','openings','French Defense','Franču aizsardzība','Stabila un stratēģiska atbilde uz 1.e4.',1,
                'Franču aizsardzība: 1.e4 e6\n\nGALVENĀ IDEJA: melnais gatavojas d5, apstrīdot centru otrajā gājienā. e6 atbalsta d5 bandinieku.\n\nPLUSAS:\n+ Stabila bandinieku struktūra\n+ Skaidrs plāns (d5, Zf6, Lc5 vai Le7)\n+ Grūti uzbrukt melnajam\n\nMĪNUSI:\n- Gaišlaukumu laidnis (c8) bieži paliek "slikts" — bloķēts aiz e6 bandinieka\n- Mazāk telpa nekā Sicīlietim\n\nPADOMS: ja jums patīk stabila spēle un ilgtermiņa plānošana — Franču aizsardzība ir lieliska izvēle.','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1','e7e6','e6! Franču aizsardzība. Sagatavo d5, radot stabilu centra struktūru.','["Kāds gājiens sagatavo d7-d5?"]'],
                ]],
            ['end-01','endgame','King + Queen vs King','K+D pret K','Kā matēt ar dāmu — pamata tehnika.',1,
                'Karalis + Dāma VIENMĒR var matēt vientuļu karali. Tehnika:\n\n1. Ierobežojiet pretinieka karali ar dāmu (kontrolējiet rindu vai līniju)\n2. Tuviniet savu karali\n3. Spiežiet pretinieka karali uz malu\n4. Spiežiet uz STŪRI\n5. Matējiet\n\nBĪSTAMI: PATS! Ja dāma kontrolē PĀRĀK DAUDZ — pretiniekam var nebūt gājienu.\nPADOMS: katrā gājienā atstājiet pretiniekam vismaz 1 likumīgu gājienu, līdz matējat.','♛','purple',
                [
                    ['k7/8/1K6/8/8/8/8/7Q w - - 0 1','h1b7','Db7# vai Dh8# — mats stūrī! Karalis b6 un dāma sadarbojās.','["Melnā karalis ir stūrī — kur dāma var dot matu?"]'],
                ]],
            ['end-02','endgame','King + Rook vs King','K+T pret K','Obligāta tehnika — kā matēt ar torni.',1,
                'K+T VIENMĒR var matēt vientuļu karali, bet tas ir grūtāk nekā ar dāmu.\n\nTEHNIKA (4 posmi):\n1. SADALI galdiņu: tornis nostājas uz līnijas/rindas, kas sadala galdiņu divās daļās\n2. TUVINI karali: jūsu karalis tuvojas pretinieka karalim\n3. SPIED uz malu: ar torni un karali kopā spiežat pretinieku uz galdiņa malu\n4. MATĒ: kad pretinieks ir uz malas, karalis nostājas pretī, tornis dod matu\n\nBĪSTAMI: pats! Ja tornis aizslēdz VISAS izejas — var būt neizšķirts.\nSVARĪGI: šī tehnika prasa apmēram 15-20 gājienus. Nesteidzieties!','♜','purple',
                [
                    ['6k1/8/6K1/8/8/8/8/R7 w - - 0 1','a1a8','Ta8# — MATS! Tornis ielaužas 8. rindā, karalis g6 bloķē visas izejas.','["Melnā karalis jau ir uz malas — kur tornis dod matu?"]'],
                ]],
            ['end-03','endgame','Pawn Endgames: Opposition','Bandinieku galotne: opozīcija','Vissvarīgākais galotnes koncepts.',2,
                'OPOZĪCIJA: divi karaļi stāv viens pretī otram ar vienu tukšu laukumu starp tiem. Tam, kura GĀJIENS — ir SLIKTĀK (viņam jāatkāpjas).\n\nKĀPĒC TAS IR SVARĪGI:\nBandinieku galotnēs karalis ir galvenā figūra. Opozīcija nosaka, vai karalis var izlauzties cauri pretinieka barjerai.\n\nLIKUMS: ja starp karaļiem ir NEPĀRA skaits laukumu un ir PRETINIEKA gājiens — JUMS ir opozīcija.\n\nPRAKTISKI: pirms bandinieka virzīšanas — iegūstiet opozīciju ar karali! Karalis iet PIRMS bandinieka, ne otrādi.','♔','purple',
                [
                    ['8/8/4k3/8/8/4P3/8/4K3 w - - 0 1','e1e2','Ke2! Iegūstam opozīciju — starp karaļiem 3 laukumi (nepāra). Melnajam jāatkāpjas.','["Nevirziet bandinieku! Vispirms karalis", "Ke2 — karaļi stāv pretī ar 3 tukšiem laukumiem"]'],
                ]],
            ['end-04','endgame','Pawn Promotion Race','Bandinieku sacīkste','Kurš bandinieks pirmais kļūst par dāmu?',2,
                'Ja abiem spēlētājiem ir brīvie bandinieki — sākas SACĪKSTE: kurš pirmais paaugstinās?\n\nKĀ SKAITĪT:\n1. Saskaitiet, cik gājieni jūsu bandiniekam līdz 8. rindai\n2. Saskaitiet pretinieka bandiniekam\n3. Ņemiet vērā, kura GĀJIENA kārta\n4. Ja paspējat pirmais — paaugstiniet un uzvariet\n\nKARAĻA KVADRĀTS: ja pretinieka karalis var "ienākt" imaginārā kvadrātā no bandinieka līdz paaugstināšanas laukumam — karalis paspēj apturēt bandinieku. Ja nevar — bandinieks paaugstinojas.','🏃','purple',
                [
                    ['8/P5k1/8/8/8/8/6K1/8 w - - 0 1','a7a8','a8=D! Bandinieks kļūst par dāmu. Melnā karalis ir pārāk tālu, lai apturētu.','["Bandinieks ir vienu soli no paaugstināšanas!"]'],
                ]],
            ['end-05','endgame','Rook Endgames: Basics','Torņu galotnes pamati','Visbiežākais galotnes veids — kā to spēlēt pareizi.',2,
                'Torņu galotnes ir VISBIEŽĀKĀS (apmēram 50% no visām galotnēm). Pamata principi:\n\n1. AKTĪVAIS TORNIS ir svarīgāks par bandinieku: tornis aizmugurē ir stiprāks nekā par vienu bandinieku vairāk.\n2. TORNIS AIZMUGURĒ: novietojiet torni AIZMUGURĒ brīvajam bandiniekam (neatkarīgi no tā, vai tas ir jūsu vai pretinieka bandinieks).\n3. KARAĻA AKTIVITĀTE: karalis jābūt tuvu darbībai — neturiet to pasīvi.\n4. LUCENAS POZĪCIJA: ja jums ir tornis + bandinieks pret torni un bandinieks ir 7. rindā — parasti var uzvarēt.\n5. FILODORA POZĪCIJA: ja aizsargājat un tornis ir 3. rindā — parasti neizšķirts.','♜','purple',
                [
                    ['8/8/8/8/4k3/8/8/3RK3 w - - 0 1','d1d4','Td4! Tornis nostājas centrā, kontrolējot 4. rindu un bloķējot melnā karali.','["Kur tornis vislabāk atbalsta bandinieka virzīšanu?", "Tornis aizmugurē = labākā pozīcija"]'],
                ]],
            ['mate-01','checkmate_patterns','Scholar\'s Mate','Aizbildņa mats','Mats 4 gājienos — un kā no tā aizsargāties.',1,
                '1.e4 e5 2.Dh5 Zc6 3.Lc4 Zf6?? 4.Dxf7# — MATS!\n\nKāpēc darbojas: f7 aizsargā TIKAI karalis. Dāma+laidnis sit f7 ar matu.\n\nKĀ AIZSARGĀTIES:\n- 2...Zf6! (nevis Zc6) — zirgs uzbrūk dāmai, kas spiesta atkāpties\n- Vai 2...g6 — bloķē dāmas ceļu\n\nKĀPĒC TĀ IR VĀJA TAKTIKA: dāma h5 var tikt dzīta ar tempo gājieniem. Pret zinošu pretinieku aizbildņa mats nedarbojas.','♔','coral',
                [
                    ['r1bqkbnr/pppp1ppp/2n5/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR w KQkq - 4 4','h5f7','Dxf7# — aizbildņa mats! f7 bija aizsargāts tikai ar karali, bet dāmu atbalsta laidnis c4.','["f7 ir vājākais laukums", "Dāma + laidnis sadarbojās"]'],
                ]],
            ['mate-02','checkmate_patterns','Smothered Mate','Smotētais mats','Zirgs dod matu karalim, kas ieslodzīts savām figūrām.',2,
                'Smotētais mats: karalis ir pilnībā apņemts ar savām figūrām, un zirgs dod šahu, no kura nav izejas.\n\nKLASISKĀ POZĪCIJA: Melnā karalis h8, tornis g8, bandinieki g7+h7. Zirgs lec uz f7 — MATS!\n\nKāpēc darbojas: karalis nevar ne kustēties (paša figūras bloķē), ne sist zirgu (neviena figūra to neaizsniedz), ne bloķēt (zirga šahu nevar bloķēt).','♔','coral',
                [
                    ['6rk/6pp/8/6N1/8/8/8/6K1 w - - 0 1','g5f7','Zf7# — smotētais mats! Karalis h8 ieslodzīts: tornis g8 un bandinieki g7, h7 bloķē visus laukumus.','["Karalis ir ieslodzīts savām figūrām", "Zirgs var lēkt uz laukumu, no kura nav izejas"]'],
                ]],
            ['mate-03','checkmate_patterns','Back Rank Mate','Pēdējās rindas mats','Visbiežākais mats reālās partijās.',1,
                'Pēdējās rindas mats notiek, kad:\n1. Karalis ir pēdējā rindā\n2. Bandinieki bloķē atkāpšanos\n3. Tornis vai dāma ielaužas pa atvērtu līniju\n\nPROFILAKSE — "lodziņš": virziet h-bandinieku vienu laukumu (h3/h6), lai karalim būtu izeja. Šis gājiens der gandrīz vienmēr.','♔','coral',
                [
                    ['6k1/5ppp/8/8/8/8/5PPP/R5K1 w - - 0 1','a1a8','Ta8# — pēdējās rindas mats! Melnā karalis ieslodzīts aiz f7, g7, h7.','["Melnā karalis ir ieslodzīts — kā to izmantot?"]'],
                ]],
            ['mate-04','checkmate_patterns','Epaulette Mate','Epolešu mats','Karaļa paša figūras bloķē atkāpšanos no sāniem.',2,
                'Epolešu mats: karalis stāv malā, un viņa paša figūras (parasti torņi vai laidņi) bloķē atkāpšanās laukumus pa abām pusēm — kā epoletes uz pleciem.\n\nDāma dod šahu no priekšpuses, un karalis nevar ne pa labi, ne pa kreisi.','♔','coral',
                [
                    ['3rkr2/8/4Q3/8/8/8/8/4K3 w - - 0 1','e6e7','De7# — epolešu mats! Torņi d8 un f8 bloķē karali no sāniem, dāma e7 aizsēdz priekšu.','["Karalis ir iespiests starp saviem torņiem", "Dāma var dot matu no priekšpuses"]'],
                ]],
            ['mate-05','checkmate_patterns','Arabian Mate','Arābu mats','Torņa un zirga sadarbība stūra tuvumā.',2,
                'Arābu mats: tornis dod matu pēdējā rindā, un zirgs kontrolē karaļa aizbēgšanas laukumus.\n\nKLASISKĀ POZĪCIJA: karalis stūrī (h8), tornis uz a8 vai h-līnijā, zirgs uz f7 vai g6 kontrolē izejas.','♔','coral',
                [
                    ['k7/8/2N5/8/8/8/8/R3K3 w Q - 0 1','a1a7','Ta7# — arābu mats! Tornis kontrolē 7. rindu, zirgs c6 kontrolē a7 un b8.','["Tornis un zirgs sadarbojās — kur tornis dod matu?"]'],
                ]],

            ['tac-11','tactics','Zwischenzug','Starpgājiens','Negaidīts starpgājiens pirms gaidītās atbildes.',2,
                'Starpgājiens (zwischenzug): tā vietā, lai atbildētu uz pretinieka draudu, jūs izdarāt CITU gājienu, kas ir vēl svarīgāks — parasti šahs vai drauds matam.\n\nPiemērs: pretinieks sit jūsu zirgu. Tā vietā, lai situ atpakaļ, jūs dodat ŠAHU. Pēc karaļa gājiena — TIKAI TĀDĀ sitat atpakaļ, bet tagad labākā pozīcijā.\n\nStarpgājieni pārvērš vienādu apmaiņu par jums izdevīgu.','💡','purple',
                [
                    ['r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','c4f7','Lxf7+! Starpgājiens — šahs pirms normāla attīstības turpinājuma. Karalis zaudē rokādi.','["Vai ir gājiens, kas dod šahu un iegūst materiālu PIRMS normāla turpinājuma?"]'],
                ]],
            ['tac-12','tactics','Windmill Tactic','Vējdzirnavas','Atkārtots atklātais šahs, kas iznīcina pretinieka pozīciju.',3,
                'Vējdzirnavas: atkārtota ATKLĀTĀ ŠAHA un SITIENA secība. Figūra dod atklāto šahu, tad atgriežas un sit jaunu figūru, tad atkal atklātais šahs...\n\nŠī ir viena no skaistākajām šaha kombinācijām. Slavenākais piemērs: Torre vs Adams, 1920 — laidnis un tornis kopā iznīcināja visu melnā pozīciju.','🌀','purple',
                [
                    ['r4rk1/ppp2ppp/8/3np3/2B5/5N2/PPPP1PPP/RNBQR1K1 w - - 0 1','c4d5','Lxd5 sit zirgu un vienlaicīgi atver e-līniju tornim. Dubulta iedarbība!','["Ko laidnis atklāj, sitat zirgu d5?"]'],
                ]],
            ['tac-13','tactics','Clearance Sacrifice','Atbrīvošanas upuris','Upurē figūru, lai atbrīvotu laukumu citai figūrai.',2,
                'Atbrīvošanas upuris: jūs upurējat figūru, lai atbrīvotu LAUKUMU vai LĪNIJU citai, spēcīgākai figūrai.\n\nPiemērs: jūsu zirgs stāv uz laukuma, kur dāmai vajadzētu nostāties. Jūs upurējat zirgu ar sitienu — laukums brīvs, dāma ieņem to un dod matu.','💎','pink',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 1 2','f1c4','Laidnis dodas uz c4, atbrīvojot f1 laukumu tornim (pēc rokādes). Attīstība + plānošana!','["Kur laidnis var doties, lai atbrīvotu ceļu tornim?"]'],
                ]],
            ['tac-14','tactics','X-Ray Attack','Rentgena uzbrukums','Figūra uzbrūk CAURI citai figūrai.',2,
                'Rentgena uzbrukums: jūsu tālsitējs (tornis, laidnis, dāma) kontrolē laukumu CAURI pretinieka figūrai.\n\nPiemērs: jūsu tornis stāv pretī pretinieka tornim tajā pašā līnijā. Aiz pretinieka torņa stāv viņa karalis. Ja torņi tiek apmainīti — jūsu tornis tagad šahs!','🔍','blue',
                [
                    ['r3k2r/ppp2ppp/3p4/4N3/2B5/8/PPP2PPP/R3K2R w KQkq - 0 1','e5f7','Zxf7! Zirgs sit, un laidnis c4 tagad redz karali caur f7 laukumu — rentgena efekts!','["Pēc zirga sitiena — ko laidnis redz?"]'],
                ]],
            ['tac-15','tactics','Counting Captures','Sitienu skaitīšana','Vai sitiens ir drošs? Saskaitiet uzbrucējus un aizsargus.',1,
                'Pirms katra sitiena VIENMĒR skaitiet:\n1. Cik JŪSU figūras uzbrūk laukumam?\n2. Cik PRETINIEKA figūras to aizsargā?\n\nJa uzbrucēju VAIRĀK nekā aizsargu — sitiens ir labs.\nJa VIENĀDI — svarīgi, kura figūra sit pirmā (sitat ar MAZĀK vērtīgu).\nJa aizsargu VAIRĀK — nesitiet!\n\nŠis princips ir PAMATA taktiskā prasme.','🔢','amber',
                [
                    ['r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3','f1b5','Lb5 piesprauž zirgu c6. Saskaitiet: kas uzbrūk c6? Lb5 un potenciāli d4. Kas aizsargā? Tikai b7. Sitiens būs izdevīgs!','["Cik figūras uzbrūk c6 un cik aizsargā?"]'],
                ]],
            ['str-06','strategy','Piece Activity','Figūru aktivitāte','Aktīvas figūras ir labākas par pasīvām — pat ja tās ir vienādas.',2,
                'Aktīva figūra kontrolē VAIRĀK laukumus un ietekmē spēli VAIRĀK nekā pasīva.\n\nZirgs d5 (kontrolē 8 laukumus) > Zirgs a1 (kontrolē 2)\nLaidnis uz atvērtas diagonāles > Laidnis aiz saviem bandiniekiem\nTornis atvērtā līnijā > Tornis aiz bandinieku sienas\n\nVIENMĒR pajautājiet: kura mana figūra ir VISMAZĀK aktīva? Uzlabojiet TO.','📈','emerald',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 1 2','f1c4','Lc4 — laidnis kļūst AKTĪVS, kontrolējot garo diagonāli un f7 laukumu.','["Kura figūra vēl nav attīstīta un var kļūt aktīva?"]'],
                ]],
            ['str-07','strategy','Weak Squares','Vājie laukumi','Laukumi, kurus jūsu bandinieki vairs nevar kontrolēt.',2,
                'Vājais laukums: laukums, kuru nevar aizsargāt ar bandiniekiem.\n\nPiemērs: ja esat virzījis f-bandinieku un g-bandinieku, h3/h6 laukums kļūst vājš — neviens bandinieks to vairs nevar aizsargāt.\n\nKĀ IZMANTOT pretinieka vājos laukumus:\n- Ievietojiet ZIRGU vājajā laukumā — pretinieks to nevar padzīt ar bandiniekiem!\n- Šādu zirgu sauc par FORPOSTU','🎯','emerald',
                [
                    ['rnbqkb1r/pp3ppp/2p1pn2/3p4/3PP3/2N2N2/PPP2PPP/R1BQKB1R w KQkq - 0 5','e4e5','e5! Bandinieks virzās uz priekšu, radot spēcīgu forpostu e5 un spiežot melnā zirgu prom.','["Kāds bandinieka gājiens rada telpisku pārsvaru?"]'],
                ]],
            ['str-08','strategy','Good vs Bad Bishop','Labais un sliktais laidnis','Kad laidnis ir spēcīgs un kad — vājš.',2,
                'LABAIS laidnis: tā bandinieki stāv uz PRETĒJĀS krāsas laukumiem. Laidnis ir brīvs kustēties.\nSLIKTAIS laidnis: tā bandinieki stāv uz TĀS PAŠAS krāsas laukumiem. Laidnis ir bloķēts.\n\nPiemērs: Franču aizsardzībā melnā laidnis c8 bieži ir \'slikts\' — bandinieki e6 un d5 bloķē tā diagonāles.\n\nKĀ UZLABOT sliktu laidni:\n- Apmainiet to pret pretinieka zirgu\n- Virziet bandiniekus uz pretējām krāsām\n- Izvediet laidni ĀRPUS bandinieku ķēdes','♝','blue',
                [
                    ['rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq - 0 2','d7d5','d5 apstrīd centru, bet radīs \'slikto laidni\' c8 — tas paliks aiz e6 un d5.','["Kāds ir melnā plāns centrā?", "d5 ir pareizi, bet ievērojiet c8 laidņa problēmu"]'],
                ]],
            ['str-09','strategy','Trading Pieces','Figūru apmaiņa','Kad apmainīt un kad — nē. Stratēģiski lēmumi.',2,
                'KAD APMAINĪT:\n- Ja jums ir MATERIĀLA PĀRSVARS → apmainiet un vinniet galotnē\n- Ja pretinieks UZBRŪK → apmainiet uzbrucējus\n- Ja jūsu pozīcija ir ŠAURĀKA → apmainiet, lai iegūtu telpu\n\nKAD NEAPMAINĪT:\n- Ja jums ir UZBRUKUMS → paturiet figūras uzbrukumam\n- Ja jūsu figūras ir AKTĪVĀKAS → paturiet tās uz galdiņa\n- Ja pretiniekam ir \'sliktas\' figūras → neapmainiet tās (ļaujiet tām būt sliktām)','⚖','gray',
                [
                    ['r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','b5c6','Lxc6 — apmainām laidni pret zirgu. Kāpēc? Sabojā melnā bandinieku struktūru (dubultotie bandinieki)!','["Vai apmaiņa ir izdevīga, ja tā sabojā pretinieka struktūru?"]'],
                ]],
            ['str-10','strategy','Prophylaxis','Profilakse','Novērsiet pretinieka plānus PIRMS tie tiek īstenoti.',2,
                'Profilakse: tā vietā, lai domātu tikai par SAVIEM plāniem, pajautājiet: KO PRETINIEKS GRIB darīt? Un NEĻAUJIET viņam!\n\nPiemēri:\n- Pretinieks grib rokēt → atveriet centru pirms tam\n- Pretinieks grib virzīt d5 → kontrolējiet d5 laukumu\n- Pretinieks grib uzbrukt karaļa flangam → pārvietojiet aizsardzības figūras\n\nLabākie spēlētāji tērē 50% laika, domājot par pretinieka plāniem.','🛡','red',
                [
                    ['rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP3PPP/R1BQKBNR w KQkq - 2 4','c1d2','Ld2! Profilakse — aizsargā zirgu c3 no laidņa b4 piespraušanas un gatavojas attīstībai.','["Melnā laidnis b4 piesprauž zirgu — kā to atrisināt?"]'],
                ]],
            ['open-04','openings','Queen\'s Gambit','Dāmas gambits','1.d4 d5 2.c4 — klasiskā dāmas flanga atklātne.',2,
                'Dāmas gambits: 1.d4 d5 2.c4\n\nBaltais piedāvā bandinieku c4, lai apmainītu flanga bandinieku pret centra bandinieku d5.\n\nMelnā atbildes:\n- 2...dxc4 (Pieņemtais gambits) — pieņem upuri, bet zaudē centru\n- 2...e6 (Noraidītais gambits) — stabils, patur centru\n- 2...c6 (Slāvu aizsardzība) — patur centru un atbalsta d5\n\nPieņemtais gambits NAV īsts upuris — baltais vienmēr atgūst bandinieku.','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq - 0 1','d7d5','d5! Kontrolē centru. Tagad pēc 2.c4 ir Dāmas gambits.','["Kāds ir labākais veids apstrīdēt d4 bandinieku?"]'],
                ]],
            ['open-05','openings','London System','Londonas sistēma','Vienkārša un uzticama baltā atklātne — ideāla iesācējiem.',1,
                'Londonas sistēma: 1.d4 2.Lf4 3.e3 4.Zf3 5.Le2 6.O-O\n\nGALVENĀ IDEJA: neatkarīgi no melnā atbildes, baltais attīsta figūras tajā pašā secībā.\n\nKĀPĒC TĀ IR LABA IESĀCĒJIEM:\n- Nav jāiegaumē daudz variantu\n- Vienmēr droša pozīcija\n- Skaidrs plāns: attīstīt, rokēt, tad uzbrukt\n\nBūtībā: d4, Lf4 (pirms e3!), e3, Zf3, Le2, O-O. Vienmēr šajā secībā.','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq - 0 1','c1f4','Lf4! Londonas sistēma — attīstiet laidni PIRMS e3, citādi laidnis paliks ieslodzīts.','["Londonas sistēmā laidnis iet uz f4 PIRMS e3"]'],
                ]],
            ['open-06','openings','Scandinavian Defense','Skandināvu aizsardzība','1.e4 d5 — tūlītēja centra apstrīdēšana.',1,
                'Skandināvu aizsardzība: 1.e4 d5\n\nMelnais UZREIZ apstrīd centra bandinieku. Pēc 2.exd5 Dxd5 3.Zc3 — dāma tiek dzīta, bet melnais iegūst atvērtu pozīciju.\n\nModernais variants: 2.exd5 Zf6 — tā vietā, lai tūlīt atgūtu bandinieku ar dāmu, melnais attīsta zirgu un atgūst vēlāk.','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq - 0 1','d7d5','d5! Skandināvu aizsardzība — tūlītēja centra apstrīdēšana. Drosmīgs gājiens!','["Kāds gājiens tūlīt apstrīd e4?"]'],
                ]],
            ['open-07','openings','King\'s Indian Defense','Karaļa indiešu aizsardzība','Hipermoderna atklātne — ļauj baltajam iegūt centru un tad to apstrīd.',2,
                'Karaļa indiešu: 1.d4 Zf6 2.c4 g6 3.Zc3 Lg7\n\nIDEJA: melnais ĻAUJ baltajam izveidot lielu centru (e4+d4), bet tad to APSTRĪD ar e5 vai c5.\n\nŠī ir agresīva, dinamiska atklātne. Melnais bieži upurē bandinieku vai pat figūru uzbrukuma labā karaļa flangam.\n\nNAV iesācējiem — bet ir svarīgi zināt par tās esamību.','📖','emerald',
                [
                    ['rnbqkbnr/pppppppp/8/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq - 0 2','g8f6','Zf6! Melnais attīsta zirgu un gatavojas fianketo (g6, Lg7). Hipermoderna pieeja.','["Attīstiet zirgu, nevis virziet bandinieku"]'],
                ]],
            ['end-06','endgame','King Activity in Endgames','Karaļa aktivitāte galotnē','Galotnē karalis ir CĪŅAS figūra, ne slēpjamā.',2,
                'Vidussplēlē karalis slēpjas. Galotnē — karalis CĪNĀS.\n\nKāpēc? Jo mazāk figūru uz galdiņa, jo mazāk draudu karalim. Un karalis ir SPĒCĪGS — kontrolē 8 laukumus!\n\nPRINCIPS: tiklīdz figūras tiek apmainītas un pozīcija kļūst atvērta — CENTRALIZĒJIET KARALI.\n\nCentrā karalis var:\n- Atbalstīt bandinieku virzīšanu\n- Uzbrukt pretinieka bandiniekiem\n- Bloķēt pretinieka karali','♔','purple',
                [
                    ['8/8/4k3/8/3pK3/8/8/8 w - - 0 1','e4d4','Kxd4! Karalis sit bandinieku un centralizējas. Galotnē karalis ir aktīvs!','["Karalis var sist bandinieku un kļūt aktīvāks"]'],
                ]],
            ['end-07','endgame','The Square Rule','Kvadrāta likums','Vai karalis paspēj noķert bandinieku? Vienkāršs vizuāls likums.',1,
                'Kvadrāta likums: ja pretinieka karalis var \'ienākt\' iedomātā KVADRĀTĀ no bandinieka līdz paaugstināšanas laukumam — karalis PASPĒJ apturēt bandinieku. Ja nevar — bandinieks paaugstinojas.\n\nKĀ SKAITĪT: no bandinieka pozīcijas līdz paaugstināšanas rindai = kvadrāta mala. Uzzīmējiet kvadrātu.\n\nPiemērs: bandinieks uz a5. Līdz a8 = 3 laukumi. Kvadrāts ir a5-a8-d8-d5. Ja karalis IR šajā kvadrātā vai var ienākt nākamajā gājienā — tas paspēj.','📐','purple',
                [
                    ['8/P5k1/8/8/8/8/6K1/8 w - - 0 1','a7a8','a8=D! Melnā karalis ir ārpus kvadrāta — nevar noķert bandinieku. Paaugstinājums!','["Vai melnā karalis paspēj noķert bandinieku?", "Saskaitiet attālumu"]'],
                ]],
            ['end-08','endgame','Lucena Position','Lucēnas pozīcija','Visbiežāk sastopamā uzvarošā pozīcija torņu galotnēs.',3,
                'Lucēnas pozīcija: jums ir tornis + bandinieks 7. rindā, pretiniekam tornis. JŪS UZVARĒJAT.\n\nTEHNIKA (\'tilta\' būvēšana):\n1. Karalis stāv PRIEKŠĀ bandiniekam\n2. Tornis stāv AIZMUGURĒ\n3. Izvediet karali no bandinieka priekšas\n4. Izmantojiet torni kā \'tiltu\' — bloķējiet pretinieka šahus pa 4. rindu\n\nŠĪ pozīcija jāzina no galvas!','♜','purple',
                [
                    ['1K1k4/1P6/8/8/8/8/8/4R3 w - - 0 1','e1e4','Te4! Tornis gatavojas būvēt \'tiltu\' — pēc karaļa izvešanas tornis bloķēs šahus pa 4. rindu.','["Tornis nostājas uz 4. rindas — kāpēc?", "Tas ir "tilta" būvēšanas sākums"]'],
                ]],
            ['end-09','endgame','Philidor Position','Filidora pozīcija','Visbiežāk sastopamā neizšķirtā pozīcija torņu galotnēs.',3,
                'Filidora pozīcija: pretiniekam ir tornis + bandinieks, jums — tikai tornis. NEIZŠĶIRTS, ja zināt tehniku.\n\nTEHNIKA:\n1. Turiet torni uz 3. RINDĀ (6. rindā, ja esat melnais)\n2. Gaidiet, kamēr pretinieks virza bandinieku uz 6. rindu\n3. TIKAI TAD pārvietojiet torni uz 1. RINDU (8. rindu) un dodiet šahus no AIZMUGURES\n\nKāpēc 3. rinda? Tā neļauj pretinieka karalim iziet PRIEKŠĀ bandiniekam.','♜','purple',
                [
                    ['8/3k4/8/3p4/8/3R4/8/3K4 w - - 0 1','d3d2','Td2! Turiet torni aizmugurē, gaidot labāko brīdi pāriet uz šahiem no 1. rindas.','["Tornis uz 3. rindas bloķē karaļa virzīšanos", "Pagaidiet — nepārvietojiet torni pārāk ātri"]'],
                ]],
            ['end-10','endgame','Two Bishops Checkmate','Mats ar diviem laidņiem','Kā matēt ar diviem laidņiem pret vientuļu karali.',3,
                'Divi laidņi VIENMĒR var matēt vientuļu karali. Tehnika ir grūtāka nekā ar torni.\n\nPRINCIPS: abi laidņi sadarbojās, kontrolējot blakus diagonāles. Kopā ar karali tie spiež pretinieka karali uz stūri.\n\nPOSMI:\n1. Centralizējiet savu karali\n2. Novietojiet laidņus BLAKUS diagonālēs (piemēram, c1-h6 un d1-a4)\n3. Pakāpeniski spiežat karali uz malu, tad uz stūri\n4. Matējiet stūrī\n\nŠĪ tehnika prasa 15-20 gājienus un praksi.','♝','purple',
                [
                    ['8/8/8/4k3/8/4K3/2B5/3B4 w - - 0 1','c2b3','Lb3! Abi laidņi kontrolē blakus diagonāles, ierobežojot melnā karali. Sākam spiešanu!','["Novietojiet laidņus tā, lai tie kontrolētu blakus diagonāles"]'],
                ]],
            ['mate-06','checkmate_patterns','Anastasia Mate','Anastasijas mats','Torņa un zirga sadarbība pret karali pie malas.',2,
                'Anastasijas mats: zirgs kontrolē karaļa bēgšanas laukumus, tornis dod matu pa līniju vai rindu.\n\nKLASISKĀ POZĪCIJA: karalis malā (h-līnijā), zirgs uz e7 vai f5 bloķē izejas, tornis ielaužas pa h-līniju vai 8. rindu.\n\nŠis mats bieži notiek kā KOMBINĀCIJAS beigas — pirms tam tiek upurēta figūra, lai ievilinātu karali neizdevīgā pozīcijā.','♔','coral',
                [
                    ['4k3/4p3/3NK3/8/8/8/8/7R w - - 0 1','h1h8','Th8# — tornis dod matu! Zirgs d6 un karalis e6 kontrolē visas izejas.','["Tornis var ielauzties pa h-līniju", "Zirgs un karalis bloķē visas izejas"]'],
                ]],
            ['mate-07','checkmate_patterns','Greek Gift','Grieķu dāvana','Klasiskais laidņa upuris uz h7.',2,
                'Grieķu dāvana (Lxh7+): klasiskais upuris, kur laidnis sit h7 bandinieku ar šahu, atverot ceļu uzbrukumam.\n\nNOSACĪJUMI, lai darbotos:\n1. Laidnis var sist h7 ar šahu\n2. Zirgs var lēkt uz g5 (uzbrūkot karalim un h7)\n3. Dāma var pievienoties uzbrukumam pa h-līniju\n\nJa visi 3 nosacījumi ir izpildīti — upuris parasti ir pareizs!','♔','coral',
                [
                    ['r1bq1rk1/ppp2ppp/2n1p3/3p4/3P4/3B1N2/PPP2PPP/RNBQ1RK1 w - - 0 1','d3h7','Lxh7+! Grieķu dāvana! Karalis jāpieņem upuris (Kxh7), un baltais turpina ar Zg5+ un Dh5.','["Klasiskais upuris — laidnis sit h7 ar šahu", "Pēc Kxh7 nāk Zg5+ un uzbrukums pa h-līniju"]'],
                ]],
            ['mate-08','checkmate_patterns','Boden Mate','Bodena mats','Divi laidņi krusteniskā uguns — mats pa diagonāli.',2,
                'Bodena mats: divi laidņi dod matu pa krusteniskām diagonālēm. Karalis parasti ir uz c8 vai c1, ieslodzīts savām figūrām.\n\nNOSACĪJUMI: karalim jābūt ieslodzītam SAVĀM figūrām, un abiem laidņiem jākontrolē izejas diagonāles.','♔','coral',
                [
                    ['r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','d2d4','La8? Nē — vispirms jāatver diagonāle. Bet ideja ir pareiza: meklējiet laidņu krustojumu!','["Vai abi laidņi var kontrolēt karaļa diagonāles?"]'],
                ]],
            ['mate-09','checkmate_patterns','Hook Mate','Āķa mats','Zirgs, tornis un bandinieks sadarbojās matam.',2,
                'Āķa mats: zirgs dod šahu, un karalis ir ieslodzīts starp torni (aizmugurē), bandinieku (sānos) un zirgu (priekšā).\n\nForma atgādina āķi — zirgs ir āķa gals, tornis ir kāts.','♔','coral',
                [
                    ['6rk/6pp/8/6N1/8/8/8/R5K1 w - - 0 1','a1a8','Ta8! Pēc Txa8, Zf7# — smotētais/āķa mats! Vai arī Zf7# uzreiz.','["Tornis piespiezt apmaiņu, pēc tam zirgs matē", "Alternatīvi — vai zirgs var matēt uzreiz?"]'],
                ]],
            ['mate-10','checkmate_patterns','Corridor Mate','Koridora mats','Dāma vai tornis matē karali šaurā koridorā.',1,
                'Koridora mats: karalis ir ieslodzīts starp savām figūrām vai galdiņa malu, un dāma vai tornis noslēdz pēdējo izeju.\n\nPiemērs: karalis a8, paša bandinieki b7+a7. Dāma vai tornis uz a-līniju — mats!\n\nTas ir \'pēdējās rindas\' mata variācija, bet var notikt arī pa līnijām.','♔','coral',
                [
                    ['k7/1p6/8/8/8/8/8/R5K1 w - - 0 1','a1a8','Ta8# — koridora mats! Karalis b8 ieslodzīts starp a7, b7 bandiniekiem un a-līnijas torni.','["Melnā karalis ir ieslodzīts stūrī aiz bandiniekiem"]'],
                ]],
            ['basics-11','basics','Capturing Rules','Sitiena noteikumi','Kā figūras sit — un kad to darīt.',1,
                'Katrā figūrai ir savi sitiena noteikumi:\n\nBANDINIEKS: sit pa DIAGONĀLI uz priekšu (1 laukumu). Nekad nevar sist taisni!\nZIRGS: sit uz to pašu laukumu, uz kuru kustas (L formas galapunktā)\nLAIDNIS: sit pa diagonāli (jebkurā attālumā)\nTORNIS: sit pa horizontāli vai vertikāli (jebkurā attālumā)\nDĀMA: sit jebkurā virzienā (jebkurā attālumā)\nKARALIS: sit jebkurā virzienā (1 laukumu)\n\nSITIENS NAV OBLIGĀTS (izņemot, ja nav citu likumīgu gājienu). Bet ja sitiens ir labs — dariet to!','⚔','gray',
                [
                    ['rnbqkbnr/ppp1pppp/8/3p4/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2','e4d5','exd5! Bandinieks sit pa diagonāli. Iegūstam centra bandinieku.','["Bandinieks sit pa DIAGONĀLI, ne taisni"]'],
                ]],
            ['basics-12','basics','Castling Rules','Rokādes noteikumi','Viss par rokādi — kad var, kad nevar, un kāpēc tā ir svarīga.',1,
                'ROKĀDE — vienīgais gājiens, kur 2 figūras kustas vienlaicīgi.\n\nĪSĀ ROKĀDE (O-O): Karalis e1→g1, Tornis h1→f1\nGARĀ ROKĀDE (O-O-O): Karalis e1→c1, Tornis a1→d1\n\nNEVAR ROKĒT, JA:\n1. Karalis vai attiecīgais tornis jau ir gājis\n2. Starp karali un torni ir figūras\n3. Karalis ir ŠAHĀ\n4. Karalis iet CAUR laukumu, kas ir šahā\n5. Karalis nonāk LAUKUMĀ, kas ir šahā\n\nSVARĪGI: rokāde NEATCEĻ iepriekšējo šahu. Ja jūs bijāt šahā pagājušajā gājienā, bet tagad vairs neesat — JŪS VARAT rokēt.','🏰','gray',
                [
                    ['r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq - 4 4','e1g1','O-O! Īsā rokāde. Karalis drošībā, tornis aktīvs. Vienmēr rokējiet ātri!','["Vai starp karali un torni ir tukšs?", "Rokāde — obligāts gājiens!"]'],
                ]],

        // Total: 64 lessons
        ];

        foreach ($lessons as $idx => [$slug,$cat,$title,$titleLv,$desc,$diff,$theory,$icon,$color,$puzzles]) {
            $lesson = Lesson::create([
                'slug' => $slug, 'category' => $cat,
                'title' => $title, 'title_lv' => $titleLv,
                'description_lv' => $desc, 'difficulty' => $diff,
                'theory_lv' => $theory, 'icon' => $icon, 'color' => $color,
                'sort_order' => $idx,
            ]);

            foreach ($puzzles as $pi => [$fen, $move, $expl, $hints]) {
                LessonPuzzle::create([
                    'lesson_id' => $lesson->id,
                    'fen' => $fen, 'correct_move' => $move,
                    'explanation_lv' => $expl, 'hints_lv' => $hints,
                    'sort_order' => $pi,
                ]);
            }
        }

        $this->command->info('Seeded '.Lesson::count().' lessons with '.LessonPuzzle::count().' puzzles.');
    }
}
