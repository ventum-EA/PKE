<?php

namespace Database\Seeders;

use App\Models\Lesson;
use App\Models\LessonPuzzle;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $lessons = [
            ['basics-1','basics','Piece Values','Figūru vērtība','Iemācieties, cik vērta ir katra figūra.',1,
                "Šahā katrai figūrai ir relatīva vērtība, kas palīdz izlemt, vai apmaiņa ir izdevīga:\n\nBandinieks (♙) = 1 punkts — mazākā figūra, bet var paaugstināties!\nZirgs (♘) = 3 punkti — lēcēja figūra, labi slēgtās pozīcijās\nLaidnis (♗) = 3 punkti — diagonāļu kontrole, spēcīgs atklātās pozīcijās\nTornis (♖) = 5 punkti — kontrolē rindas un līnijas\nDāma (♕) = 9 punkti — visspēcīgākā figūra\nKaralis (♔) = nenovērtējams — ja zaudē, zaudē spēli!\n\nSvarīgs princips: nekad neapmainiet vērtīgāku figūru pret mazāk vērtīgu bez iemesla.",'♙','gray',
                [
                    ['rnbqkb1r/pppppppp/2n2n2/8/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3','d2d4','Centrs ir šaha galdiņa svarīgākā zona. Gājiens d4 kontrolē centru ar diviem bandiniekiem un atbrīvo ceļu laidnim.',['Kuru bandinieku var virzīt uz priekšu, lai kontrolētu centru?','Apsveriet d2-d4']],
                    ['rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq - 0 2','g1f3','Zirgs uz f3 attīsta figūru, uzbrūk e5 bandiniekam un kontrolē centru. Figūru attīstība atklātnē ir svarīgāka nekā bandinieku virzīšana!',['Kuru figūru var attīstīt, vienlaikus uzbrūkot pretinieka bandiniekam?']],
                ]],
            ['fork-1','fork','Knight Fork','Zirga dakša','Zirgs ir dakšas karalis — tas var uzbrukt divām figūrām vienlaikus.',1,
                "Dakša (fork) ir taktisks motīvs, kurā viena figūra vienlaikus uzbrūk divām vai vairākām pretinieka figūrām. Pretinieks var aizsargāt tikai vienu, un jūs iegūstat otru.\n\nZirgs ir ideāla dakšas figūra, jo:\n- Tas lec pāri citām figūrām\n- Tas uzbrūk \"L\" formā — grūti paredzēt\n- Dāma un karalis nevar uzbrukt zirgam, ja tas ir aizsargāts\n\nKlasiskā zirga dakša: zirgs uzbrūk karalim UN dāmai vienlaikus. Karalis JĀNOVĀC no šaha, un dāma tiek zaudēta.\n\nMeklējiet dakšas iespējas katrā partijā — tās ir visbiežākais taktiskais motīvs!",'⚔','amber',
                [
                    ['r1bqkb1r/pppppppp/2n2n2/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQ1RK1 w kq - 4 4','d2d4','Gājiens d4 uzbrūk gan e5 bandiniekam, gan c5 laidnim — bandinieka dakša!',['Kāds bandinieka gājiens uzbrūk divām melnā figūrām vienlaikus?']],
                    ['r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3','f1b5','Laidnis uz b5 piesprauž zirgu c6 — zirgs aizsargā karali un nevar kustēties.',['Vai kāda figūra var uzbrukt zirgam c6?']],
                    ['r3k2r/ppp2ppp/2nqbn2/3pp3/2B1P1b1/3P1N2/PPP2PPP/RNBQ1RK1 w kq - 0 7','c4d5','Laidnis sit uz d5 un vienlaikus uzbrūk vairākām figūrām. Melnajam nav labas atbildes.',['Apsveriet laidņa sitienu uz centru']],
                ]],
            ['fork-2','fork','Queen and Pawn Forks','Dāmas un bandinieka dakšas','Dakšas ar dāmu, laidni un bandinieku.',2,
                "Dakšas veic ne tikai zirgs! Jebkura figūra var izveidot dakšu:\n\nDāmas dakša: Dāma uzbrūk divām neaizsargātām figūrām pa diagonāli vai līniju.\n\nBandinieka dakša: Bandinieks, ejot uz priekšu, vienlaikus uzbrūk divām figūrām pa diagonāli. Īpaši spēcīgi, jo bandinieka vērtība ir vismazākā.\n\nAtcerieties: dakšas darbojas vislabāk, ja viena no apdraudētajām figūrām ir karalis (tad pretiniekam OBLIGĀTI jāreaģē uz šahu).",'⚔','amber',
                [
                    ['2r3k1/pp3ppp/8/3Nb3/8/8/PPP2PPP/R4RK1 w - - 0 1','d5f6','Zirgs uz f6+ ir šahs karalim un vienlaikus uzbrūk citai figūrai!',['Vai zirgs var dot šahu un vienlaikus uzbrukt citai figūrai?']],
                    ['r1b1k2r/ppppqppp/2n5/4P3/2B5/5Q2/PPP2PPP/RNB1K2R w KQkq - 0 1','f3f7','Dāma sit f7 ar šahu! Karalis jāpārvietojas, un dāma iegūst materiālu.',['f7 laukums ir neaizsargāts — kāda figūra var to izmantot?']],
                ]],
            ['pin-1','pin','Absolute Pin','Absolūtā piespraušana','Kad figūra nevar kustēties, jo tā aizsargā karali.',1,
                "Piespraušana (pin) ir taktisks motīvs, kurā figūra nevar (vai tai nav izdevīgi) kustēties, jo aiz tās atrodas vērtīgāka figūra.\n\nAbsolūtā piespraušana: figūra stāv starp uzbrucēju un KARALI. Tā NEDRĪKST kustēties.\n\nRelatīvā piespraušana: figūra stāv starp uzbrucēju un citu vērtīgu figūru (ne karali). Tā var kustēties, bet tad zaudē aiz tās esošo figūru.\n\nKā izmantot piespraušanu:\n1. Atrodiet piesprausto figūru\n2. Uzbrūciet tai ar bandiniekiem vai citām figūrām\n3. Pretinieks nevar aizbēgt — figūra ir \"piesista\"\n\nLaidņi un torņi ir labākās piespraušanas figūras.",'📌','blue',
                [
                    ['rn1qkbnr/ppp1pppp/8/3p4/4P1b1/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3','f1e2','Melnā laidnis g4 ir piesalis zirgu f3 pie dāmas. Le2 aizsargā zirgu un attīsta laidni.',['Zirgs f3 ir piesprausts — kā to aizsargāt?','Vai laidnis var bloķēt piespraušanas līniju?']],
                    ['rnbqk1nr/pppp1ppp/4p3/8/1b1PP3/2N5/PPP2PPP/R1BQKBNR w KQkq - 2 3','c1d2','Melnā laidnis b4 piesalis zirgu c3 pie karaļa. Ld2 aizsargā zirgu.',['Zirgs c3 ir absolūti piesprausts — atrodiet aizsardzību']],
                ]],
            ['skewer-1','skewer','Bishop and Rook Skewers','Laidņa un torņa šķēres','Uzbrukums, kas piespiež vērtīgāku figūru atkāpties.',2,
                "Šķēres (skewer) ir pretējas piespraušanai: uzbrukums vērtīgākai figūrai, kura jāpārvieto, un aiz tās atrodas mazāk vērtīga figūra.\n\nPiemērs: Tornis uzbrūk karalim pa līniju, aiz karaļa stāv dāma. Karalis OBLIGĀTI jāpārvieto, un tornis sit dāmu.\n\nAtslēga: vērtīgākajai figūrai jābūt PRIEKŠĀ, mazāk vērtīgajai — AIZMUGURĒ.\n\nŠķēres ir īpaši bīstamas galotnēs.",'🔪','red',
                [
                    ['4r1k1/5ppp/8/8/8/8/1B3PPP/4R1K1 w - - 0 1','b2g7','Laidnis uz g7 ir šahs karalim! Pēc karaļa atkāpšanās, laidnis sit torni.',['Vai laidnis var dot šahu un redzēt torni aiz karaļa?']],
                ]],
            ['discovery-1','discovery','Discovered Check','Atklātais šahs','Vienas figūras gājiens atklāj citas figūras uzbrukumu.',2,
                "Atklātais uzbrukums notiek, kad viena figūra atkāpjas no līnijas un atklāj citas figūras uzbrukumu.\n\nAtklātais šahs ir sevišķi spēcīgs: jūs pārvietojat vienu figūru (kas var uzbrukt jebkam), un vienlaikus otra figūra dod šahu karalim.\n\nMeklējiet: kur jūsu figūras stāv uz vienas līnijas ar pretinieka karali, un kāda figūra stāv pa vidu?",'💥','purple',
                [
                    ['r1bqr1k1/ppp2ppp/2n5/3np3/2B5/5N2/PPPP1PPP/RNBQR1K1 w - - 0 1','c4d5','Laidnis sit zirgu uz d5. Pēc cxd5 vai exd5, baltā tornis e1 tagad redz melnā karali pa atvērto e-līniju.',['Ko atklāj laidņa sitiens uz d5 pa e-līniju?']],
                ]],
            ['back-rank-1','back_rank','Back Rank Mate','Pēdējās rindas mats','Kad karalim nav atkāpšanās laukumu aiz bandinieku sienas.',1,
                "Pēdējās rindas mats ir viens no biežākajiem mata veidiem. Tas notiek, kad:\n1. Karalis stāv pēdējā rindā\n2. Aiz karaļa stāv paša bandinieki\n3. Karalis nevar aizbēgt uz priekšu\n4. Tornis vai dāma ielaužas pēdējā rindā — MATS!\n\nKā aizsargāties:\n- Izveidojiet \"lodziņu\" — virziet h-bandinieku vienu laukumu uz priekšu\n- Turiet torni pēdējā rindā aizsardzībai\n\nŠis motīvs ir tik bīstams, ka pieredzējuši spēlētāji gandrīz vienmēr izveido lodziņu profilaktiski.",'🏰','emerald',
                [
                    ['6k1/5ppp/8/8/8/8/5PPP/4R1K1 w - - 0 1','e1e8','Tornis ielaužas 8. rindā — MATS! Melnā karalis ieslodzīts aiz bandiniekiem.',['Melnā karalis ir ieslodzīts — kā to izmantot?']],
                    ['3r2k1/5ppp/8/8/8/8/5PPP/3RR1K1 w - - 0 1','e1e8','Tornis e1→e8 ar šahu! Pēc Txe8, Txe8# — dubulttorņu mats!',['Izmantojiet abus torņus pēdējās rindas uzbrukumam']],
                ]],
            ['sacrifice-1','sacrifice','The Decoy Sacrifice','Vilinošais upuris','Atdodiet materiālu, lai ievilinātu pretinieka figūru neizdevīgā pozīcijā.',2,
                "Upuris ir materiāla atdošana ar konkrētu mērķi.\n\nUpuru veidi:\n- Vilinošais upuris: atdodiet figūru, lai ievilinātu pretinieka karali neizdevīgā pozīcijā\n- Novirzīšanas upuris: piespiediet aizsardzības figūru atstāt svarīgu laukumu\n- Iznīcināšanas upuris: likvidējiet bandinieku, kas aizsargā karali\n\nZelta likums: pirms upurējat, pārliecinieties, ka jums ir KONKRĒTS turpinājums.",'💎','pink',
                [
                    ['r1b1k2r/pppp1ppp/2n2n2/2b1p1B1/2B1P3/3P1N2/PPP2PPP/RN1QK2R w KQkq - 0 1','g5f6','Laidnis sit zirgu f6. Ja gxf6, melnā karaļa flangs tiek atvērts.',['Vai varat sabojāt melnā bandinieku struktūru ap karali?']],
                ]],
            ['mate-1','checkmate_patterns','Scholar\'s Mate','Aizbildņa mats','Ātrākais mats spēlē — 4 gājienos.',1,
                "Aizbildņa mats ir mats 4 gājienos:\n1. e4 e5\n2. Dh5 — dāma uzbrūk f7\n3. Lc4 — laidnis arī mērķē uz f7\n4. Dxf7# — MATS!\n\nKāpēc tas darbojas? f7 laukums ir VĀJĀKAIS laukums spēles sākumā — to aizsargā TIKAI karalis.\n\nKā aizsargāties:\n- Spēlējiet 2...Nf6! — zirgs uzbrūk dāmai\n- Vai 2...g6 — bloķē dāmas diagonāli\n\nNekad necenšieties spēlēt šo pret pieredzējušiem — dāmas agrīna izveide ir vāja stratēģija.",'♔','coral',
                [
                    ['rnbqkbnr/pppp1ppp/8/4p3/2B1P3/8/PPPP1PPP/RNBQK1NR w KQkq - 0 1','d1h5','Dāma uz h5 draud Dxf7 matam! Melnajam jāreaģē uzreiz.',['Kur dāma var doties, lai draudētu f7 kopā ar laidni c4?']],
                    ['rnbqkbnr/pppp1ppp/8/4p2Q/2B1P3/8/PPPP1PPP/RNB1K1NR b KQkq - 1 2','g8f6','Zirgs uz f6 ir labākā aizsardzība! Uzbrūk dāmai un attīsta figūru.',['Kāda figūra var uzbrukt dāmai h5 un vienlaikus aizsargāt?']],
                ]],
            ['mate-2','checkmate_patterns','Smothered Mate','Smotētais mats','Klasiskie mata zīmējumi ar zirgu un torni.',2,
                "Smotētais mats: Zirgs dod matu karalim, kurš ir pilnībā iespiests savām figūrām.\n\nFilodora mats: Dāmas un laidņa kombinācija diagonālē.\n\nAnastasijas mats: Tornis un zirgs kopā — tornis kontrolē līniju, zirgs bloķē laukumus.\n\nŠie mata zīmējumi bieži parādās reālās partijās!",'♔','coral',
                [
                    ['6rk/6pp/8/6N1/8/8/8/4R1K1 w - - 0 1','e1e8','Tornis uz e8 ir šahs! Pēc Txe8, Zf7# — smotētais mats!',['Vai varat piespiest melnā torni atstāt g8?','Pēc Txe8 Txe8 — ko zirgs var izdarīt?']],
                ]],
        ];

        foreach ($lessons as $idx => [$slug,$cat,$title,$titleLv,$desc,$diff,$theory,$icon,$color,$puzzles]) {
            $lesson = Lesson::create([
                'slug' => $slug, 'category' => $cat,
                'title' => $title, 'title_lv' => $titleLv,
                'description_lv' => $desc, 'difficulty' => $diff,
                'theory_lv' => $theory, 'icon' => $icon, 'color' => $color,
                'sort_order' => $idx,
            ]);

            foreach ($puzzles as $pi => [$fen,$move,$expl,$hints]) {
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
