import { describe, it, expect } from "vitest";
import {
    parsePgn,
    classifyEvalDiff,
    categorizeError,
    generateExplanation,
    getLegalMoves,
    isLegalMove,
    makeMove,
} from "@/services/chess";

describe("services/chess.js", () => {
    describe("parsePgn", () => {
        it("parses a simple PGN into moves with SAN, UCI, and FENs", () => {
            const pgn = "1. e4 e5 2. Nf3 Nc6 3. Bb5 a6";
            const { moves } = parsePgn(pgn);

            expect(moves).toHaveLength(6);
            expect(moves[0].san).toBe("e4");
            expect(moves[0].color).toBe("white");
            expect(moves[0].uci).toBe("e2e4");
            expect(moves[0].fen_before).toContain("rnbqkbnr");
            expect(moves[1].san).toBe("e5");
            expect(moves[1].color).toBe("black");
            expect(moves[2].san).toBe("Nf3");
        });

        it("extracts headers when present", () => {
            const pgn = `[Event "Test"]\n[White "Alice"]\n[Black "Bob"]\n\n1. e4 e5 *`;
            const { headers } = parsePgn(pgn);
            expect(headers.White).toBe("Alice");
            expect(headers.Black).toBe("Bob");
        });

        it("numbers moves correctly across full moves", () => {
            const { moves } = parsePgn("1. e4 e5 2. Nf3 Nc6");
            expect(moves[0].moveNumber).toBe(1);
            expect(moves[1].moveNumber).toBe(1);
            expect(moves[2].moveNumber).toBe(2);
            expect(moves[3].moveNumber).toBe(2);
        });
    });

    describe("classifyEvalDiff", () => {
        it("classifies equal evaluations as 'best'", () => {
            expect(classifyEvalDiff(0.3, 0.3, "white")).toBe("best");
            expect(classifyEvalDiff(0.0, 0.03, "white")).toBe("best");
        });

        it("classifies a small white drop as inaccuracy", () => {
            // White had +0.5, now +0.0 → diff 0.5 → inaccuracy
            expect(classifyEvalDiff(0.5, 0.0, "white")).toBe("inaccuracy");
        });

        it("classifies a large drop as mistake or blunder", () => {
            expect(classifyEvalDiff(1.0, -0.5, "white")).toBe("mistake");
            expect(classifyEvalDiff(1.0, -2.0, "white")).toBe("blunder");
        });

        it("respects color perspective — black cares about negative eval", () => {
            // Black was winning (-1.5), now only -0.5 → black lost 1.0 → mistake
            expect(classifyEvalDiff(-1.5, -0.5, "black")).toBe("mistake");
        });
    });

    describe("categorizeError", () => {
        it("flags early-game moves as 'opening'", () => {
            expect(categorizeError(2, 40, { san: "Nf3" })).toBe("opening");
        });

        it("flags late-game moves as 'endgame'", () => {
            expect(categorizeError(35, 40, { san: "Kf2" })).toBe("endgame");
        });

        it("flags middlegame captures/checks as 'tactical'", () => {
            expect(categorizeError(20, 40, { captured: "p", san: "Nxe5" })).toBe("tactical");
            expect(categorizeError(20, 40, { san: "Qh5+" })).toBe("tactical");
        });

        it("flags quiet middlegame moves as 'positional'", () => {
            expect(categorizeError(20, 40, { san: "Re1" })).toBe("positional");
        });
    });

    describe("generateExplanation", () => {
        it("returns null for good moves", () => {
            expect(generateExplanation("best", "tactical", "e4", "e4")).toBeNull();
            expect(generateExplanation("excellent", "tactical", "e4", "e4")).toBeNull();
            expect(generateExplanation("good", "tactical", "e4", "e4")).toBeNull();
        });

        it("generates a Latvian explanation for a tactical blunder", () => {
            const text = generateExplanation("blunder", "tactical", "Qh5??", "Nf3");
            expect(text).toBeTruthy();
            expect(text).toContain("Nf3");
            expect(text).toMatch(/taktisk/i);
        });

        it("falls back gracefully when category is unknown", () => {
            const text = generateExplanation("mistake", "unknown-cat", "e4", "d4");
            expect(text).toContain("d4");
        });
    });

    describe("getLegalMoves", () => {
        it("returns 20 legal moves from the starting position", () => {
            const moves = getLegalMoves(
                "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1"
            );
            expect(moves).toHaveLength(20);
            const es = moves.filter((m) => m.from === "e2");
            expect(es).toHaveLength(2); // e3 and e4
        });
    });

    describe("isLegalMove", () => {
        it("accepts legal moves", () => {
            const start = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
            expect(isLegalMove(start, "e2", "e4")).toBe(true);
        });

        it("rejects illegal moves", () => {
            const start = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
            expect(isLegalMove(start, "e2", "e5")).toBe(false);
        });
    });

    describe("makeMove", () => {
        it("returns the new FEN after a move", () => {
            const start = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
            const result = makeMove(start, "e2", "e4");
            expect(result).toBeTruthy();
            expect(result.fen).toContain("4P3"); // pawn on e4
            expect(result.fen.split(" ")[1]).toBe("b"); // black to move
        });

        it("returns null for illegal moves", () => {
            const start = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
            expect(makeMove(start, "e2", "e5")).toBeNull();
        });
    });
});
