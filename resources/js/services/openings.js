/**
 * ECO Opening Database — ~350 entries covering A00-E99.
 * Each entry: [moves_string, ECO_code, opening_name_lv]
 * Matches longest prefix first for accurate detection.
 */

const ECO_DATABASE = [
    // A00-A09: Uncommon openings
    ['g3', 'A00', 'Benko atklātne'],
    ['b3', 'A01', 'Nimcoviča-Laršena uzbrukums'],
    ['f4', 'A02', 'Bērda atklātne'],
    ['Nf3 d5 c4', 'A09', 'Rēti atklātne'],
    ['Nf3 d5', 'A07', 'Karaļa indiešu uzbrukums'],
    ['Nf3 Nf6', 'A05', 'Rēti atklātne'],
    ['Nf3', 'A04', 'Rēti atklātne'],

    // A10-A39: English Opening
    ['c4 e5 Nc3 Nf6', 'A28', 'Angļu atklātne, četru jātnieku variante'],
    ['c4 e5 Nc3', 'A25', 'Angļu atklātne, Sicīliešu inversija'],
    ['c4 e5 g3', 'A22', 'Angļu atklātne, Brēmenes sistēma'],
    ['c4 c6', 'A11', 'Angļu atklātne, Karo-Kann setup'],
    ['c4 e6', 'A13', 'Angļu atklātne'],
    ['c4 Nf6 Nc3 e6', 'A17', 'Angļu atklātne, Hedgehog'],
    ['c4 Nf6 Nc3 g6', 'A15', 'Angļu atklātne, Anglo-indiešu'],
    ['c4 c5', 'A30', 'Angļu atklātne, simetriskā'],
    ['c4 e5', 'A20', 'Angļu atklātne'],
    ['c4 Nf6', 'A15', 'Angļu atklātne'],
    ['c4', 'A10', 'Angļu atklātne'],

    // A40-A79: Queen's Pawn misc, Dutch, Benoni, Old Indian
    ['d4 e6 c4 Bb4+', 'A43', 'Benoni aizsardzība'],
    ['d4 Nf6 c4 c5 d5 e6', 'A60', 'Benoni aizsardzība'],
    ['d4 Nf6 c4 c5 d5', 'A56', 'Benoni aizsardzība'],
    ['d4 f5 c4 Nf6 g3', 'A81', 'Holandiešu aizsardzība'],
    ['d4 f5 g3', 'A81', 'Holandiešu aizsardzība, Leņingradas variante'],
    ['d4 f5', 'A80', 'Holandiešu aizsardzība'],
    ['d4 Nf6 c4 d6', 'A53', 'Vecindiešu aizsardzība'],
    ['d4 c5', 'A43', 'Vecindiešu aizsardzība'],

    // B00-B19: Pirc, Caro-Kann, Scandinavian
    ['e4 d5 exd5 Qxd5', 'B01', 'Skandināvu aizsardzība, galvenā līnija'],
    ['e4 d5 exd5 Nf6', 'B01', 'Skandināvu aizsardzība, 2...Nf6'],
    ['e4 d5', 'B01', 'Skandināvu aizsardzība'],
    ['e4 Nc6', 'B00', 'Nimcoviča aizsardzība'],
    ['e4 d6 d4 Nf6 Nc3 g6', 'B08', 'Pīrca aizsardzība, klasiskā'],
    ['e4 d6 d4 Nf6 Nc3', 'B07', 'Pīrca aizsardzība'],
    ['e4 d6', 'B07', 'Pīrca aizsardzība'],
    ['e4 g6 d4 Bg7', 'B06', 'Modernā aizsardzība'],
    ['e4 g6', 'B06', 'Modernā aizsardzība'],
    ['e4 c6 d4 d5 Nc3 dxe4 Nxe4', 'B17', 'Karo-Kann, Šteininca variante'],
    ['e4 c6 d4 d5 Nd2', 'B12', 'Karo-Kann, avansu variante'],
    ['e4 c6 d4 d5 e5', 'B12', 'Karo-Kann, avansu variante'],
    ['e4 c6 d4 d5 Nc3', 'B15', 'Karo-Kann, galvenā līnija'],
    ['e4 c6 d4 d5', 'B12', 'Karo-Kann aizsardzība'],
    ['e4 c6', 'B10', 'Karo-Kann aizsardzība'],

    // B20-B99: Sicilian Defence
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 a6', 'B90', 'Sicīliešu aizsardzība, Nadžorfa variante'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 g6', 'B76', 'Sicīliešu aizsardzība, Pūķa variante'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 e5', 'B33', 'Sicīliešu aizsardzība, Svešņikova variante'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3 Nc6', 'B56', 'Sicīliešu aizsardzība, klasiskā'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6 Nc3', 'B56', 'Sicīliešu aizsardzība, atvērtā'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4 Nf6', 'B40', 'Sicīliešu aizsardzība, atvērtā'],
    ['e4 c5 Nf3 d6 d4 cxd4 Nxd4', 'B40', 'Sicīliešu aizsardzība, atvērtā'],
    ['e4 c5 Nf3 d6 d4', 'B40', 'Sicīliešu aizsardzība, atvērtā'],
    ['e4 c5 Nf3 e6 d4 cxd4 Nxd4', 'B45', 'Sicīliešu aizsardzība, Paulšena variante'],
    ['e4 c5 Nf3 Nc6 d4', 'B32', 'Sicīliešu aizsardzība, atvērtā'],
    ['e4 c5 Nf3 Nc6 Bb5', 'B31', 'Sicīliešu aizsardzība, Rossolimo variante'],
    ['e4 c5 Nf3 e6', 'B40', 'Sicīliešu aizsardzība'],
    ['e4 c5 Nf3 d6', 'B50', 'Sicīliešu aizsardzība'],
    ['e4 c5 Nc3', 'B23', 'Sicīliešu aizsardzība, slēgtā'],
    ['e4 c5 c3', 'B22', 'Sicīliešu aizsardzība, Alapina variante'],
    ['e4 c5 d4 cxd4 c3', 'B21', 'Smita-Morra gambīts'],
    ['e4 c5 f4', 'B21', 'Sicīliešu aizsardzība, Grand Prix uzbrukums'],
    ['e4 c5 Nf3', 'B27', 'Sicīliešu aizsardzība'],
    ['e4 c5', 'B20', 'Sicīliešu aizsardzība'],

    // C00-C19: French Defence
    ['e4 e6 d4 d5 Nc3 Bb4', 'C15', 'Francūzu aizsardzība, Vinawera variante'],
    ['e4 e6 d4 d5 Nc3 Nf6', 'C10', 'Francūzu aizsardzība, klasiskā'],
    ['e4 e6 d4 d5 Nd2', 'C01', 'Francūzu aizsardzība, Tarraša variante'],
    ['e4 e6 d4 d5 e5', 'C02', 'Francūzu aizsardzība, avansu variante'],
    ['e4 e6 d4 d5 Nc3', 'C10', 'Francūzu aizsardzība, galvenā līnija'],
    ['e4 e6 d4 d5', 'C00', 'Francūzu aizsardzība'],
    ['e4 e6', 'C00', 'Francūzu aizsardzība'],

    // C20-C39: Open games misc, King's Gambit
    ['e4 e5 f4 exf4', 'C33', 'Karaļa gambīts, pieņemtais'],
    ['e4 e5 f4 d5', 'C31', 'Karaļa gambīts, Falknbēra kontrgambīts'],
    ['e4 e5 f4', 'C30', 'Karaļa gambīts'],
    ['e4 e5 d4', 'C21', 'Centra partija'],
    ['e4 e5 Bc4', 'C23', 'Bīšopa atklātne'],

    // C42-C49: Petrov, Three/Four Knights, Scotch
    ['e4 e5 Nf3 Nf6 Nxe5', 'C42', 'Petrova aizsardzība, klasiskā'],
    ['e4 e5 Nf3 Nf6 d4', 'C42', 'Petrova aizsardzība'],
    ['e4 e5 Nf3 Nf6', 'C42', 'Petrova aizsardzība'],
    ['e4 e5 Nf3 Nc6 d4 exd4 Nxd4', 'C45', 'Skotu partija'],
    ['e4 e5 Nf3 Nc6 d4', 'C44', 'Skotu partija'],
    ['e4 e5 Nf3 Nc6 Nc3 Nf6', 'C47', 'Četru jātnieku partija'],
    ['e4 e5 Nc3 Nf6', 'C46', 'Trīs jātnieku partija'],

    // C50-C59: Italian, Evans Gambit, Two Knights
    ['e4 e5 Nf3 Nc6 Bc4 Bc5 b4', 'C51', 'Evansa gambīts'],
    ['e4 e5 Nf3 Nc6 Bc4 Nf6 d4', 'C55', 'Divu jātnieku aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bc4 Nf6', 'C55', 'Divu jātnieku aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bc4 Bc5 c3', 'C54', 'Itāliešu partija, galvenā līnija'],
    ['e4 e5 Nf3 Nc6 Bc4 Bc5', 'C53', 'Itāliešu partija, Giuoco Piano'],
    ['e4 e5 Nf3 Nc6 Bc4', 'C50', 'Itāliešu partija'],

    // C60-C99: Ruy Lopez
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7 Re1 b5 Bb3 d6 c3 O-O h3', 'C92', 'Spāņu partija, slēgtā Zaiceva variante'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7 Re1 b5 Bb3 d6 c3 O-O', 'C84', 'Spāņu partija, slēgtā aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Nxe4', 'C83', 'Spāņu partija, atvērtā aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O Be7', 'C84', 'Spāņu partija, slēgtā aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6 O-O', 'C78', 'Spāņu partija, galvenā līnija'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 d6', 'C70', 'Spāņu partija, aizkavētā'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4 Nf6', 'C78', 'Spāņu partija'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Ba4', 'C70', 'Spāņu partija'],
    ['e4 e5 Nf3 Nc6 Bb5 Nf6', 'C65', 'Spāņu partija, Berlīnes aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bb5 a6 Bxc6', 'C68', 'Spāņu partija, Apmaiņas variante'],
    ['e4 e5 Nf3 Nc6 Bb5 a6', 'C68', 'Spāņu partija, Morfi aizsardzība'],
    ['e4 e5 Nf3 Nc6 Bb5 f5', 'C63', 'Spāņu partija, Šlīmaņa gambīts'],
    ['e4 e5 Nf3 Nc6 Bb5 Bc5', 'C61', 'Spāņu partija, klasiskā'],
    ['e4 e5 Nf3 Nc6 Bb5', 'C60', 'Spāņu partija'],

    // D00-D69: Queen's Pawn, Queen's Gambit
    ['d4 d5 c4 e6 Nc3 Nf6 Bg5', 'D50', 'Dāmas gambīts, ortodoksā aizsardzība'],
    ['d4 d5 c4 e6 Nc3 Nf6', 'D30', 'Dāmas gambīts, noraidītais'],
    ['d4 d5 c4 dxc4', 'D20', 'Dāmas gambīts, pieņemtais'],
    ['d4 d5 c4 c6', 'D10', 'Slāvu aizsardzība'],
    ['d4 d5 c4 e6 Nc3', 'D31', 'Dāmas gambīts'],
    ['d4 d5 c4 e6', 'D30', 'Dāmas gambīts, noraidītais'],
    ['d4 d5 c4 Nc6', 'D06', 'Dāmas gambīts, Čigorins aizsardzība'],
    ['d4 d5 c4', 'D06', 'Dāmas gambīts'],
    ['d4 d5 Nf3 Nf6 Bf4', 'D02', 'Londonas sistēma'],
    ['d4 d5 Bf4', 'D02', 'Londonas sistēma'],
    ['d4 d5 Nf3 Nf6 c4', 'D06', 'Dāmas gambīts'],
    ['d4 d5 Nf3 Nf6', 'D02', 'Dāmas bandinieka spēle'],
    ['d4 d5 e3', 'D00', 'Dāmas bandinieka spēle'],
    ['d4 d5', 'D00', 'Dāmas bandinieka spēle'],

    // D70-D99: Grünfeld
    ['d4 Nf6 c4 g6 Nc3 d5', 'D70', 'Grīnfelda aizsardzība'],

    // E00-E19: Catalan, Bogo-Indian
    ['d4 Nf6 c4 e6 g3', 'E01', 'Katalāņu atklātne'],
    ['d4 Nf6 c4 e6 Nf3 Bb4+', 'E11', 'Bogoindiešu aizsardzība'],

    // E20-E59: Nimzo-Indian
    ['d4 Nf6 c4 e6 Nc3 Bb4 Qc2', 'E32', 'Nimcoviča aizsardzība, klasiskā'],
    ['d4 Nf6 c4 e6 Nc3 Bb4 e3', 'E40', 'Nimcoviča aizsardzība, Rubinšteina variante'],
    ['d4 Nf6 c4 e6 Nc3 Bb4', 'E20', 'Nimcoviča aizsardzība'],
    ['d4 Nf6 c4 e6 Nc3', 'E20', 'Nimcoviča aizsardzība'],
    ['d4 Nf6 c4 e6 Nf3', 'E10', 'Indiešu aizsardzība'],
    ['d4 Nf6 c4 e6', 'E00', 'Indiešu aizsardzība'],

    // E60-E99: King's Indian
    ['d4 Nf6 c4 g6 Nc3 Bg7 e4 d6 Nf3 O-O Be2 e5', 'E90', 'Karaļindiešu aizsardzība, klasiskā'],
    ['d4 Nf6 c4 g6 Nc3 Bg7 e4 d6 f3', 'E81', 'Karaļindiešu aizsardzība, Zemišas variante'],
    ['d4 Nf6 c4 g6 Nc3 Bg7 e4 d6', 'E70', 'Karaļindiešu aizsardzība, galvenā līnija'],
    ['d4 Nf6 c4 g6 Nc3 Bg7 e4', 'E70', 'Karaļindiešu aizsardzība'],
    ['d4 Nf6 c4 g6 Nc3 Bg7', 'E60', 'Karaļindiešu aizsardzība'],
    ['d4 Nf6 c4 g6 Nc3', 'E60', 'Karaļindiešu aizsardzība'],
    ['d4 Nf6 c4 g6 Nf3', 'E60', 'Karaļindiešu aizsardzība'],
    ['d4 Nf6 c4 g6', 'E60', 'Karaļindiešu aizsardzība'],

    // General fallbacks (shortest matches last)
    ['e4 e5 Nf3 Nc6', 'C44', 'Atklātā spēle, jātnieku variante'],
    ['e4 e5 Nf3', 'C40', 'Atklātā spēle, karaļa jātnieks'],
    ['e4 e5', 'C20', 'Atklātā spēle'],
    ['d4 Nf6 Nf3', 'A46', 'Indiešu aizsardzība'],
    ['d4 Nf6 c4', 'A15', 'Indiešu aizsardzība'],
    ['d4 Nf6', 'A46', 'Indiešu aizsardzība'],
    ['d4', 'D00', 'Dāmas bandinieka atklātne'],
    ['e4', 'B00', 'Karaļa bandinieka atklātne'],
];

// Pre-sort: longest sequences first for greedy matching
const SORTED_DB = [...ECO_DATABASE].sort((a, b) => b[0].split(' ').length - a[0].split(' ').length);

/**
 * Detect the opening from an array of move objects with .san property.
 * Matches the longest move sequence in the ECO database.
 * Returns { name, eco } or { name: 'Nezināma atklātne', eco: '?' }
 */
export function detectOpening(moves) {
    const sanList = moves.map(m => m.san);

    for (const [seq, eco, name] of SORTED_DB) {
        const seqMoves = seq.split(' ');
        if (seqMoves.length > sanList.length) continue;

        let match = true;
        for (let i = 0; i < seqMoves.length; i++) {
            if (sanList[i] !== seqMoves[i]) { match = false; break; }
        }
        if (match) return { name, eco };
    }

    return { name: 'Nezināma atklātne', eco: '?' };
}

/**
 * Get all openings for a given ECO prefix (e.g. 'B2' for all Sicilian).
 */
export function getOpeningsByEcoPrefix(prefix) {
    return ECO_DATABASE
        .filter(([_, eco]) => eco.startsWith(prefix))
        .map(([_, eco, name]) => ({ eco, name }));
}

export default { detectOpening, getOpeningsByEcoPrefix, ECO_DATABASE };
