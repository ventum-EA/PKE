/**
 * Chess Piece SVG Image Loader
 *
 * Loads piece SVGs from /pieces/{style}/ and provides data URIs for
 * embedding in the ChessBoard SVG via <image> elements.
 *
 * Falls back to Unicode symbols if images fail to load.
 */

const PIECE_CODES = ['K', 'Q', 'R', 'B', 'N', 'P'];
const COLORS = ['w', 'b'];

const cache = new Map();

/**
 * Preload all piece images for a given style.
 * Returns a Map of piece key ('wK', 'bQ', etc.) → blob URL.
 */
export async function preloadPieces(style = 'cburnett') {
    const cacheKey = style;
    if (cache.has(cacheKey)) return cache.get(cacheKey);

    const pieces = new Map();
    const promises = [];

    for (const color of COLORS) {
        for (const piece of PIECE_CODES) {
            const key = `${color}${piece}`;
            const url = `/pieces/${style}/${key}.svg`;
            promises.push(
                fetch(url)
                    .then(r => {
                        if (!r.ok) throw new Error(`${r.status}`);
                        return r.blob();
                    })
                    .then(blob => {
                        pieces.set(key, URL.createObjectURL(blob));
                    })
                    .catch(() => {
                        // Piece not found — will fall back to Unicode
                    })
            );
        }
    }

    await Promise.allSettled(promises);
    cache.set(cacheKey, pieces);
    return pieces;
}

/**
 * Get the image URL for a FEN piece character.
 * e.g. 'K' → pieces.get('wK'), 'q' → pieces.get('bQ')
 */
export function getPieceImageUrl(pieces, fenChar) {
    if (!fenChar || !pieces) return null;
    const isWhite = fenChar === fenChar.toUpperCase();
    const key = `${isWhite ? 'w' : 'b'}${fenChar.toUpperCase()}`;
    return pieces.get(key) || null;
}
