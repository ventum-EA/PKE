// Annotate screenshots with arrows, labels, and highlight boxes for PKE user guide (Section 5)
// Run AFTER take-screenshots.spec.js:
//   npm install sharp
//   node tests/e2e/annotate-screenshots.mjs

import sharp from "sharp";
import { mkdirSync, existsSync } from "fs";
import { join } from "path";

const SRC = join(process.cwd(), "screenshots");
const OUT = join(process.cwd(), "screenshots", "annotated");
if (!existsSync(OUT)) mkdirSync(OUT, { recursive: true });

// ── SVG helpers ───────────────────────────────────────────────────

function esc(s) {
    return s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function arrowSvg(x1, y1, x2, y2) {
    const hl = 14, a = Math.atan2(y2 - y1, x2 - x1);
    const hx1 = x2 - hl * Math.cos(a - Math.PI / 6), hy1 = y2 - hl * Math.sin(a - Math.PI / 6);
    const hx2 = x2 - hl * Math.cos(a + Math.PI / 6), hy2 = y2 - hl * Math.sin(a + Math.PI / 6);
    return `<line x1="${x1}" y1="${y1}" x2="${x2}" y2="${y2}" stroke="#FF4444" stroke-width="3" stroke-linecap="round"/>` +
           `<polygon points="${x2},${y2} ${hx1},${hy1} ${hx2},${hy2}" fill="#FF4444"/>`;
}

function labelSvg(x, y, text) {
    const cw = 7.2, pad = 8;
    const bw = text.length * cw + pad * 2, bh = 24;
    return `<rect x="${x - bw/2}" y="${y - bh/2}" width="${bw}" height="${bh}" rx="5" fill="rgba(0,0,0,0.85)" stroke="#FF4444" stroke-width="2"/>` +
           `<text x="${x}" y="${y + 1}" text-anchor="middle" dominant-baseline="middle" fill="#FFF" font-family="Arial,sans-serif" font-size="13" font-weight="bold">${esc(text)}</text>`;
}

function rectSvg(x, y, w, h) {
    return `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="4" fill="none" stroke="#FF4444" stroke-width="2.5" stroke-dasharray="8,5"/>`;
}

// ── Annotation data — coordinates are % of 1280×720 ──────────────
// Design: labels in clear dark space, arrows bridge to targets,
//         no label-on-label or label-on-content overlaps.

const A = {

    // 01 LOGIN — centered form (~35-63%x), dark sides for labels
    "01_login": [
        { arrow: [24, 49, 36, 49], label: [12, 49, "1. E-pasta lauks"] },
        { arrow: [24, 62, 36, 62], label: [12, 62, "2. Paroles lauks"] },
        { arrow: [25, 72, 36, 72], label: [13, 72, "3. Pieslēgties poga"] },
        { arrow: [72, 79, 49, 80], label: [83, 79, "4. Reģistrēties"] },
        { arrow: [72, 87, 63, 80], label: [83, 87, "5. Valodas izvēle"] },
    ],

    // 02 REGISTER — centered form, dark sides for labels
    "02_register": [
        { arrow: [24, 33, 34, 33], label: [12, 33, "1. Lietotājvārds"] },
        { arrow: [24, 46, 34, 46], label: [12, 46, "2. E-pasts"] },
        { arrow: [24, 58, 34, 58], label: [12, 58, "3. Parole"] },
        { arrow: [24, 72, 34, 72], label: [12, 72, "4. Apstiprināt"] },
        { arrow: [74, 84, 66, 84], label: [84, 84, "5. Reģistrēties"] },
        { arrow: [74, 93, 56, 92], label: [84, 93, "6. Pieslēgties"] },
    ],

    // 03 WELCOME MODAL — overlay dialog, dark areas on sides
    "03_welcome_modal": [
        { rect: [35, 44, 29, 28], arrow: [26, 58, 35, 58], label: [16, 58, "1. Iespēju saraksts"] },
        { rect: [35, 76, 10, 11], arrow: [26, 81, 35, 81], label: [16, 81, "2. Apgūt šahu"] },
        { rect: [46, 76, 9, 11],  arrow: [74, 78, 55, 81], label: [83, 76, "3. Sākt ekskursiju"] },
        { rect: [56, 76, 9, 11],  arrow: [74, 86, 64, 82], label: [83, 86, "4. Izlaist"] },
    ],

    // 04 DASHBOARD — labels in gaps between sections
    "04_dashboard": [
        { rect: [0, 0, 100, 7],   label: [50, 10, "1. Navigācijas josla"] },
        { rect: [3, 27, 94, 16],  label: [50, 24, "2. Ātrās darbības"] },
        { rect: [3, 50, 94, 16],  label: [50, 47, "3. Statistikas kartes"] },
        { rect: [3, 72, 47, 26],  label: [26, 69, "4. Kļūdu sadalījums"] },
        { rect: [52, 72, 45, 26], label: [74, 69, "5. Progresa grafiks"] },
    ],

    // 05 DAILY PUZZLE — board left, info panels right
    "05_daily_puzzle": [
        { rect: [5, 36, 35, 60],  label: [22, 33, "1. Šaha galdiņš"] },
        { rect: [42, 28, 53, 10], label: [68, 25, "2. Statistika"] },
        { rect: [42, 42, 53, 13], label: [68, 39, "3. Līderu saraksts"] },
        { rect: [42, 59, 53, 12], label: [68, 56, "4. Vēsture"] },
        { arrow: [90, 14, 90, 17], label: [90, 11, "5. Dienu sērija"] },
    ],

    // 06 GAMES LIST — cards grid
    "06_games": [
        { rect: [63, 15, 35, 7],  label: [80, 10, "1. Darbību pogas"] },
        { rect: [3, 24, 33, 5],   label: [19, 22, "2. Statistika"] },
        { rect: [3, 32, 94, 28],  label: [50, 62, "3. Partiju kartes"] },
    ],

    // 07 UPLOAD GAME — modal dialog, dark sides
    "07_upload_game": [
        { rect: [30, 22, 40, 22], arrow: [21, 33, 29, 33], label: [12, 33, "1. PGN ievade"] },
        { rect: [30, 46, 40, 10], arrow: [21, 51, 29, 51], label: [12, 51, "2. Spēlētāji"] },
        { rect: [30, 58, 40, 10], arrow: [21, 63, 29, 63], label: [12, 63, "3. Metadati"] },
        { rect: [30, 71, 40, 10], arrow: [21, 76, 29, 76], label: [12, 76, "4. Atklātne"] },
        { rect: [30, 84, 40, 8],  arrow: [79, 88, 71, 88], label: [87, 88, "5. Saglabāt"] },
    ],

    // 08 GAME VIEW — analysis modal, labels below modal
    "08_game_view": [
        { rect: [7, 19, 26, 60],  label: [20, 86, "1. Galdiņš un navigācija"] },
        { rect: [55, 22, 35, 35], label: [72, 86, "2. Analīzes panelis"] },
        { rect: [75, 9, 18, 5],   label: [84, 17, "3. Eksports (PDF/PNG)"] },
    ],

    // 09 PLAY VS STOCKFISH — board left, settings right
    "09_play": [
        { rect: [5, 27, 38, 69],  label: [24, 24, "1. Šaha galdiņš"] },
        { rect: [46, 29, 48, 48], label: [70, 26, "2. Spēles iestatījumi"] },
        { rect: [46, 87, 48, 10], label: [70, 84, "3. Gājienu saraksts"] },
    ],

    // 10 TRAINING — mode cards + weakness + analyzed games
    "10_training": [
        { rect: [10, 28, 20, 22], label: [20, 25, "1. Taktiskās"] },
        { rect: [31, 28, 20, 22], label: [41, 25, "2. Pozicionālās"] },
        { rect: [52, 28, 19, 22], label: [61, 25, "3. Atklātnes"] },
        { rect: [72, 28, 19, 22], label: [81, 25, "4. Galotnes"] },
        { rect: [10, 54, 80, 16], label: [50, 51, "5. Vājāko atklātņu treniņš"] },
        { rect: [10, 74, 80, 22], label: [50, 71, "6. Analizētu partiju treniņš"] },
    ],

    // 11 OPENINGS — left panel, right detail area
    "11_openings": [
        { rect: [3, 27, 16, 6],   label: [11, 24, "1. Kategorijas"] },
        { rect: [3, 38, 25, 6],   label: [15, 36, "2. Meklēšana"] },
        { rect: [3, 46, 25, 50],  label: [15, 44, "3. Atklātņu saraksts"] },
        { rect: [30, 25, 66, 52], label: [63, 22, "4. Detaļu panelis"] },
    ],

    // 12 LESSONS — recommended + 2×2 category grid
    "12_lessons": [
        { rect: [10, 24, 80, 12], label: [50, 21, "1. Ieteicamā nodarbība"] },
        { rect: [10, 39, 39, 22], label: [30, 37, "2. Pamati"] },
        { rect: [51, 39, 39, 22], label: [70, 37, "3. Taktika"] },
        { rect: [10, 64, 39, 22], label: [30, 62, "4. Stratēģija"] },
        { rect: [51, 64, 39, 22], label: [70, 62, "5. Atklātnes"] },
    ],

    // 13 PUZZLES — board left, info right
    "13_puzzles": [
        { rect: [3, 25, 26, 49],  label: [16, 22, "1. Uzdevuma galdiņš"] },
        { rect: [3, 77, 26, 9],   label: [16, 88, "2. Navigācija"] },
        { rect: [33, 29, 37, 28], label: [51, 27, "3. Uzdevuma info"] },
        { rect: [33, 60, 37, 36], label: [51, 58, "4. Uzdevumu saraksts"] },
        { rect: [83, 16, 14, 6],  label: [90, 13, "5. Progress"] },
    ],

    // 14 ACHIEVEMENTS — progress + filter + cards
    "14_achievements": [
        { rect: [10, 27, 80, 16], label: [50, 24, "1. Kopējais progress"] },
        { rect: [10, 49, 60, 6],  label: [40, 47, "2. Kategoriju filtri"] },
        { rect: [10, 58, 39, 36], label: [30, 56, "3. Sasnieguma karte"] },
        { rect: [51, 58, 39, 36], label: [70, 56, "4. Sasnieguma karte"] },
    ],

    // 15 MULTIPLAYER — tabs + sections
    "15_multiplayer": [
        { rect: [15, 27, 70, 7],  label: [50, 24, "1. Cilnes"] },
        { rect: [15, 37, 70, 18], label: [50, 35, "2. Laika kontrole"] },
        { rect: [15, 59, 70, 15], label: [50, 57, "3. Ātrā spēle"] },
        { rect: [15, 80, 70, 17], label: [50, 78, "4. Uzaicināt draugu"] },
    ],

    // 16 FRIENDS — tab + add friend + list
    "16_friends": [
        { rect: [15, 27, 70, 7],  label: [50, 24, "1. Draugi cilne"] },
        { rect: [15, 37, 70, 16], label: [50, 35, "2. Pievienot draugu"] },
        { arrow: [87, 47, 82, 47], label: [92, 42, "3. Sūtīt"] },
        { rect: [15, 55, 70, 28], label: [50, 53, "4. Draugu saraksts"] },
    ],

    // 17 PROFILE — card + elo + account, labels on left side
    "17_profile": [
        { rect: [20, 24, 60, 21], arrow: [17, 34, 20, 34], label: [8, 34, "1. Profila karte"] },
        { rect: [20, 49, 60, 20], arrow: [17, 59, 20, 59], label: [8, 59, "2. ELO vēsture"] },
        { rect: [20, 72, 60, 25], arrow: [17, 84, 20, 84], label: [8, 84, "3. Konta dati"] },
    ],

    // 18 SETTINGS — password + preferences (scrolled)
    "18_settings": [
        { rect: [20, 10, 60, 58], arrow: [17, 35, 20, 35], label: [8, 35, "1. Mainīt paroli"] },
        { arrow: [72, 27, 63, 27], label: [80, 27, "Pašreizējā"] },
        { arrow: [72, 40, 63, 40], label: [80, 40, "Jaunā parole"] },
        { arrow: [72, 53, 63, 53], label: [80, 53, "Apstiprināt"] },
        { rect: [20, 72, 60, 25], arrow: [17, 84, 20, 84], label: [8, 84, "2. Iestatījumi"] },
    ],

    // 19 ADMIN OVERVIEW — tabs + stats + charts
    "19_admin_overview": [
        { rect: [3, 27, 44, 5],   label: [25, 24, "1. Administrācijas cilnes"] },
        { rect: [3, 37, 94, 17],  label: [50, 34, "2. Statistikas kartes"] },
        { rect: [3, 58, 47, 39],  label: [26, 56, "3. Rezultātu sadalījums"] },
        { rect: [52, 58, 45, 39], label: [74, 56, "4. Populārākās atklātnes"] },
    ],

    // 20 ADMIN USERS — search + table
    "20_admin_users": [
        { rect: [3, 37, 93, 7],   label: [50, 34, "1. Meklēšana"] },
        { rect: [3, 46, 93, 51],  label: [25, 44, "2. Lietotāju tabula"] },
        { arrow: [87, 56, 84, 54], label: [91, 60, "3. Darbības"] },
    ],

    // 21 ADMIN GAMES — filters + table
    "21_admin_games": [
        { rect: [3, 37, 93, 10],  label: [50, 34, "1. Meklēšana un filtri"] },
        { rect: [3, 49, 93, 49],  label: [25, 47, "2. Partiju tabula"] },
        { arrow: [93, 56, 91, 54], label: [93, 60, "3. Dzēst"] },
    ],

    // 22 ADMIN ANALYTICS — stats + charts
    "22_admin_analytics": [
        { rect: [3, 37, 94, 16],  label: [50, 34, "1. Analītikas kartes"] },
        { rect: [3, 56, 94, 28],  label: [50, 53, "2. Partijas dienā"] },
        { rect: [3, 87, 94, 12],  label: [50, 85, "3. Aktīvi spēlētāji"] },
    ],

    // 23 ADMIN AUDIT — filters + log
    "23_admin_audit": [
        { rect: [3, 37, 93, 10],  label: [50, 34, "1. Meklēšana un filtri"] },
        { rect: [3, 49, 93, 49],  label: [25, 47, "2. Audita ieraksti"] },
    ],
};

// ── Build SVG overlay ─────────────────────────────────────────────

function buildSvg(w, h, annots) {
    let els = "";
    for (const a of annots) {
        if (a.rect) {
            const [xp, yp, wp, hp] = a.rect;
            els += rectSvg((xp/100)*w, (yp/100)*h, (wp/100)*w, (hp/100)*h);
        }
        if (a.arrow) {
            const [x1, y1, x2, y2] = a.arrow;
            els += arrowSvg((x1/100)*w, (y1/100)*h, (x2/100)*w, (y2/100)*h);
        }
        if (a.label) {
            const [xp, yp, text] = a.label;
            els += labelSvg((xp/100)*w, (yp/100)*h, text);
        }
    }
    return `<svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${els}</svg>`;
}

// ── Process each screenshot ───────────────────────────────────────

async function annotate(name) {
    const src = join(SRC, `${name}.png`);
    try {
        const meta = await sharp(src).metadata();
        const w = meta.width, h = meta.height;
        const annots = A[name];
        if (!annots) {
            console.log(`  ⏭ ${name} — no annotations`);
            await sharp(src).toFile(join(OUT, `${name}.png`));
            return;
        }
        const svg = Buffer.from(buildSvg(w, h, annots));
        await sharp(src)
            .composite([{ input: svg, top: 0, left: 0 }])
            .toFile(join(OUT, `${name}.png`));
        console.log(`  ✔ ${name} — ${annots.length} annotations`);
    } catch (e) {
        console.log(`  ✗ ${name} — ${e.message}`);
    }
}

console.log("Annotating screenshots for PKE user guide...\n");
for (const name of Object.keys(A)) await annotate(name);
console.log(`\nDone! → ${OUT}`);
