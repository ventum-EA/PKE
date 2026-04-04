<?php

namespace Database\Seeders;

use App\Models\Opening;
use Illuminate\Database\Seeder;

class OpeningSeeder extends Seeder
{
    public function run(): void
    {
        $openings = [
            ['g3','A00','Benko Opening','Benko atklātne','A'],
            ['b3','A01','Nimzo-Larsen Attack','Nimcoviča-Laršena uzbrukums','A'],
            ['f4','A02',"Bird's Opening",'Bērda atklātne','A'],
            ['Nf3 d5 c4','A09','Réti Opening','Rēti atklātne','A'],
            ['Nf3 d5','A07',"King's Indian Attack",'Karaļa indiešu uzbrukums','A'],
            ['Nf3 Nf6','A05','Réti Opening','Rēti atklātne','A'],
            ['Nf3','A04','Réti Opening','Rēti atklātne','A'],
            ['c4 e5 Nc3 Nf6','A28','English Opening, Four Knights','Angļu atklātne, četru jātnieku variante','A'],
            ['c4 e5 Nc3','A25','English Opening, Sicilian Reversed','Angļu atklātne, Sicīliešu inversija','A'],
            ['c4 c6','A11','English Opening, Caro-Kann setup','Angļu atklātne, Karo-Kann setup','A'],
            ['c4 e6','A13','English Opening','Angļu atklātne','A'],
            ['c4 c5','A30','English Opening, Symmetrical','Angļu atklātne, simetriskā','A'],
            ['c4 e5','A20','English Opening','Angļu atklātne','A'],
            ['c4 Nf6','A15','English Opening','Angļu atklātne','A'],
            ['c4','A10','English Opening','Angļu atklātne','A'],
            ['d4 f5','A80','Dutch Defence','Holandiešu aizsardzība','A'],
            ['d4 c5','A43','Old Benoni Defence','Benoni aizsardzība','A'],
            ['d4 Nf6 c4 c5 d5','A56','Benoni Defence','Benoni aizsardzība','A'],
            ['e4 d5 exd5 Qxd5','B01','Scandinavian Defence, Main Line','Skandināvu aizsardzība, galvenā līnija','B'],
            ['e4 d5 exd5 Nf6','B01','Scandinavian Defence, 2...Nf6','Skandināvu aizsardzība, 2...Nf6','B'],
            ['e4 d5','B01','Scandinavian Defence','Skandināvu aizsardzība','B'],
            ['e4 d6 d4 Nf6 Nc3 g6','B08','Pirc Defence, Classical','Pīrca aizsardzība, klasiskā','B'],
            ['e4 d6 d4 Nf6 Nc3','B07','Pirc Defence','Pīrca aizsardzība','B'],
            ['e4 d6','B07','Pirc Defence','Pīrca aizsardzība','B'],
            ['e4 g6','B06','Modern Defence','Modernā aizsardzība','B'],
            ['e4 c6 d4 d5 Nc3 dxe4 Nxe4','B17','Caro-Kann, Steinitz','Karo-Kann, Šteininca variante','B'],
            ['e4 c6 d4 d5 e5','B12','Caro-Kann, Advance','Karo-Kann, avansu variante','B'],
            ['e4 c6 d4 d5 Nc3','B15','Caro-Kann, Main Line','Karo-Kann, galvenā līnija','B'],
            ['e4 c6 d4 d5','B12','Caro-Kann Defence','Karo-Kann aizsardzība','B'],
            ['e4 c6','B10','Caro-Kann Defence','Karo-Kann aizsardzība','B'],
            ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 a6','B90','Sicilian, Najdorf','Sicīliešu aizsardzība, Nadžorfa variante','B'],
            ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 g6','B76','Sicilian, Dragon','Sicīliešu aizsardzība, Pūķa variante','B'],
            ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 e5','B33','Sicilian, Sveshnikov','Sicīliešu aizsardzība, Svešņikova variante','B'],
            ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3','B56','Sicilian, Open','Sicīliešu aizsardzība, atvērtā','B'],
            ['e4 c5 Nf3 Nc6 Bb5','B31','Sicilian, Rossolimo','Sicīliešu aizsardzība, Rossolimo variante','B'],
            ['e4 c5 Nc3','B23','Sicilian, Closed','Sicīliešu aizsardzība, slēgtā','B'],
            ['e4 c5 c3','B22','Sicilian, Alapin','Sicīliešu aizsardzība, Alapina variante','B'],
            ['e4 c5 Nf3 d6','B50','Sicilian Defence','Sicīliešu aizsardzība','B'],
            ['e4 c5 Nf3','B27','Sicilian Defence','Sicīliešu aizsardzība','B'],
            ['e4 c5','B20','Sicilian Defence','Sicīliešu aizsardzība','B'],
            ['e4 e6 d4 d5 Nc3 Bb4','C15','French, Winawer','Francūzu aizsardzība, Vinawera variante','C'],
            ['e4 e6 d4 d5 Nc3 Nf6','C10','French, Classical','Francūzu aizsardzība, klasiskā','C'],
            ['e4 e6 d4 d5 Nd2','C01','French, Tarrasch','Francūzu aizsardzība, Tarraša variante','C'],
            ['e4 e6 d4 d5 e5','C02','French, Advance','Francūzu aizsardzība, avansu variante','C'],
            ['e4 e6 d4 d5','C00','French Defence','Francūzu aizsardzība','C'],
            ['e4 e6','C00','French Defence','Francūzu aizsardzība','C'],
            ['e4 e5 f4','C30',"King's Gambit",'Karaļa gambīts','C'],
            ['e4 e5 Nf3 Nf6','C42',"Petrov's Defence",'Petrova aizsardzība','C'],
            ['e4 e5 Nf3 Nc6 d4','C44','Scotch Game','Skotu partija','C'],
            ['e4 e5 Nf3 Nc6 Bc4 Bc5 b4','C51','Evans Gambit','Evansa gambīts','C'],
            ['e4 e5 Nf3 Nc6 Bc4 Nf6','C55','Two Knights Defence','Divu jātnieku aizsardzība','C'],
            ['e4 e5 Nf3 Nc6 Bc4 Bc5','C53','Italian, Giuoco Piano','Itāliešu partija, Giuoco Piano','C'],
            ['e4 e5 Nf3 Nc6 Bc4','C50','Italian Game','Itāliešu partija','C'],
            ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7','C84','Ruy Lopez, Closed','Spāņu partija, slēgtā aizsardzība','C'],
            ['e4 e5 Nf3 Nc6 Bb5 Nf6','C65','Ruy Lopez, Berlin','Spāņu partija, Berlīnes aizsardzība','C'],
            ['e4 e5 Nf3 Nc6 Bb5 a6 Bxc6','C68','Ruy Lopez, Exchange','Spāņu partija, Apmaiņas variante','C'],
            ['e4 e5 Nf3 Nc6 Bb5 a6','C68','Ruy Lopez, Morphy','Spāņu partija, Morfi aizsardzība','C'],
            ['e4 e5 Nf3 Nc6 Bb5','C60','Ruy Lopez','Spāņu partija','C'],
            ['e4 e5 Nf3 Nc6','C44','Open Game, Knights','Atklātā spēle, jātnieku variante','C'],
            ['e4 e5 Nf3','C40','Open Game','Atklātā spēle','C'],
            ['e4 e5','C20','Open Game','Atklātā spēle','C'],
            ['d4 d5 c4 e6 Nc3 Nf6 Bg5','D50',"Queen's Gambit, Orthodox","Dāmas gambīts, ortodoksā aizsardzība",'D'],
            ['d4 d5 c4 dxc4','D20',"Queen's Gambit Accepted",'Dāmas gambīts, pieņemtais','D'],
            ['d4 d5 c4 c6','D10','Slav Defence','Slāvu aizsardzība','D'],
            ['d4 d5 c4 e6','D30',"Queen's Gambit Declined",'Dāmas gambīts, noraidītais','D'],
            ['d4 d5 c4','D06',"Queen's Gambit",'Dāmas gambīts','D'],
            ['d4 d5 Bf4','D02','London System','Londonas sistēma','D'],
            ['d4 d5 Nf3 Nf6','D02',"Queen's Pawn Game",'Dāmas bandinieka spēle','D'],
            ['d4 d5','D00',"Queen's Pawn Game",'Dāmas bandinieka spēle','D'],
            ['d4 Nf6 c4 g6 Nc3 d5','D70','Grünfeld Defence','Grīnfelda aizsardzība','D'],
            ['d4 Nf6 c4 e6 g3','E01','Catalan Opening','Katalāņu atklātne','E'],
            ['d4 Nf6 c4 e6 Nc3 Bb4','E20','Nimzo-Indian Defence','Nimcoviča aizsardzība','E'],
            ['d4 Nf6 c4 e6','E00','Indian Defence','Indiešu aizsardzība','E'],
            ['d4 Nf6 c4 g6 Nc3 Bg7 e4 d6','E70',"King's Indian, Main Line",'Karaļindiešu aizsardzība, galvenā līnija','E'],
            ['d4 Nf6 c4 g6 Nc3 Bg7','E60',"King's Indian Defence",'Karaļindiešu aizsardzība','E'],
            ['d4 Nf6 c4 g6','E60',"King's Indian Defence",'Karaļindiešu aizsardzība','E'],
            ['d4 Nf6 c4','A15','Indian Defence','Indiešu aizsardzība','E'],
            ['d4 Nf6','A46','Indian Defence','Indiešu aizsardzība','E'],
        ];

        // Explanations for major openings
        $explanations = [
            'B20' => ['Sicīliešu aizsardzība ir melnā populārākais atbildes gājiens uz 1.e4. Melnais cīnās par centra kontroli asimetriski — ar c-bandinieku.',
                ['Melnais rada asimetrisku spēli','c5 kontrolē d4 laukumu','Melnais uzbrūk dāmas flangā, baltais — karaļa flangā','Rada sarežģītas, taktiskas pozīcijas'],
                [['e4','Baltais aizņem centru un atver diagonāles laidnim un dāmai.'],['c5','Melnais uzreiz izaicina d4 laukumu! Ja baltais spēlēs d4, melnais apmainīs flanga bandinieku pret centra bandinieku.']]],
            'C60' => ['Spāņu partija ir viena no vecākajām un stratēģiski bagātākajām atklātnēm. Baltais izveido spiedienu uz e5 caur zirgu c6.',
                ['Laidnis b5 izveido spiedienu uz zirgu c6, kas aizsargā e5','Baltais tiecas iegūt ilgtermiņa pozicionālu pārsvaru','Bieži rodas sarežģītas vidussagrā manēvru spēles'],
                [['e4','Klasiskais karaļa bandinieka gājiens — kontrolē centru.'],['e5','Melnais atbild simetriski.'],['Nf3','Uzbrūk e5 un attīsta figūru.'],['Nc6','Aizsargā e5 un attīsta figūru.'],['Bb5','Spāņu laidnis! Piesalis zirgu c6, kas aizsargā e5. Draud ilgtermiņa spiedienu.']]],
            'C50' => ['Itāliešu partija ir dabiska atklātne, kur laidnis kontrolē centru un mērķē uz f7 — melnā vājāko punktu.',
                ['Laidnis c4 kontrolē d5 un mērķē uz f7','f7 ir vājākais laukums spēles sākumā — to aizsargā TIKAI karalis','Baltais bieži spēlē d3 vai d4, veidojot centru'],
                [['e4','Centra kontrole.'],['e5','Simetriskā atbilde.'],['Nf3','Uzbrūk e5 un attīsta.'],['Nc6','Aizsargā e5.'],['Bc4','Itāliešu laidnis! Kontrolē d5 un mērķē uz f7.']]],
            'C00' => ['Francūzu aizsardzība ir stabila, pozicionāla atbilde uz 1.e4. Melnais ļauj baltajam aizņemt centru, bet pēc tam to apstrīd ar d5.',
                ['Melnais vispirms spēlē e6, sagatavojot d5','Rada slēgtākas pozīcijas','Melnā laidnis c8 bieži ir problēma — iesprostots aiz e6'],
                [['e4','Centra kontrole.'],['e6','Francūzu aizsardzība! Sagatavo d5, kas tieši izaicina e4.']]],
            'B10' => ['Karo-Kann aizsardzība ir viena no stabilākajām atbildēm uz 1.e4. c6 sagatavo d5 bez laidņa c8 bloķēšanas.',
                ['c6 sagatavo d5 bez laidņa bloķēšanas (atšķirībā no Francūzu e6)','Melnais iegūst stabilu bandinieku struktūru','Rada pozicionālas spēles'],
                [['e4','Centra kontrole.'],['c6','Sagatavo d5! Atšķirībā no e6, c6 neaizslēdz laidni c8.']]],
            'D06' => ['Dāmas gambīts ir viena no vecākajām atklātnēm. Baltais piedāvā c4 bandinieku, lai iegūtu centra kontroli.',
                ['c4 izaicina melnā d5','Ja melnais pieņem (dxc4), baltais iegūst centru','Šis nav īsts gambīts — baltais gandrīz vienmēr atgūst bandinieku'],
                [['d4','Centra kontrole ar dāmas bandinieku.'],['d5','Melnais arī aizņem centru.'],['c4','Dāmas gambīts! Piedāvā c-bandinieku. Ja dxc4, baltais iegūst brīvu d4 un var spēlēt e4.']]],
            'D02' => ['Londonas sistēma ir universāla baltā atklātne, ko var spēlēt pret gandrīz jebko. Vienkārša, bet efektīva.',
                ['Baltais vienmēr spēlē d4, Bf4, e3, Nf3, c3','Laidnis f4 ir atslēgas gājiens','Vienkārša plāna sistēma — nav jāmācās daudz teorijas'],
                [['d4','Centra kontrole.'],['d5','Melnais arī aizņem centru.'],['Bf4','Londonas sistēmas pazīme! Laidnis iziet PIRMS e3, jo pēc e3 tam nebūtu ceļa ārā.']]],
            'E60' => ['Karaļindiešu aizsardzība ir agresīva melnā sistēma ar fianchetto laidni g7.',
                ['Melnais fianchetto laidni uz g7','Pēc rokādes melnais spēlē e5 vai c5','Rada asas, taktiskas pozīcijas','Fišera un Kasparova mīļākā aizsardzība'],
                [['d4','Centra kontrole.'],['Nf6','Indiešu aizsardzība — saglabā elastību.'],['c4','Baltais pastiprina centru.'],['g6','Karaļindiešu pazīme! Sagatavo Lg7 fianchetto.']]],
            'B01' => ['Skandināvu aizsardzība ir vienkārša un tieša — melnais uzreiz izaicina e4.',
                ['Melnais tūlīt nojauc baltā centra bandinieku','Ja 2...Dxd5, dāma tiek uzbrukta ar Nc3','Vienkārša atklātne, bet melnais parasti nedaudz pasīvāks'],
                [['e4','Centra kontrole.'],['d5','Skandināvu aizsardzība! Uzreiz izaicina e4 — vistiešākā atbilde.']]],
        ];

        $sortOrder = 0;
        foreach ($openings as [$moves, $eco, $name, $nameLv, $cat]) {
            $expl = $explanations[$eco] ?? null;
            Opening::create([
                'eco' => $eco,
                'category' => $cat,
                'name' => $name,
                'name_lv' => $nameLv,
                'moves' => $moves,
                'summary_lv' => $expl[0] ?? null,
                'ideas_lv' => $expl[1] ?? null,
                'move_explanations_lv' => $expl ? array_map(fn($m) => ['move' => $m[0], 'text' => $m[1]], $expl[2]) : null,
                'sort_order' => $sortOrder++,
            ]);
        }

        $this->command->info('Seeded ' . Opening::count() . ' openings.');
    }
}
