<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Server-side Stockfish engine wrapper.
 * Communicates with the stockfish binary via proc_open for deep positional analysis.
 * Used by AnalyzeGameJob for accurate server-side analysis.
 *
 * Install: apt-get install stockfish (or download from stockfishchess.org)
 * The binary path is configurable via config/stockfish.php (backed by STOCKFISH_PATH env variable).
 */
class StockfishService
{
    private string $binaryPath;
    private int $defaultDepth;
    private int $timeout;

    public function __construct()
    {
        $this->binaryPath = (string) config('stockfish.binary_path', '/usr/games/stockfish');
        $this->defaultDepth = (int) config('stockfish.default_depth', 18);
        $this->timeout = (int) config('stockfish.timeout', 30);
    }

    /**
     * Analyze a single position and return the evaluation.
     *
     * @return array{eval: float, bestMove: string, pv: array, depth: int, mateIn: ?int}
     *
     * @throws \InvalidArgumentException If FEN is malformed or contains unsafe characters
     */
    public function analyzePosition(string $fen, int $depth = null): array
    {
        $this->validateFen($fen);
        $depth = $depth ?? $this->defaultDepth;

        $commands = [
            'uci',
            'isready',
            "position fen {$fen}",
            "go depth {$depth}",
        ];

        $output = $this->runEngine($commands);

        return $this->parseAnalysis($output);
    }

    /**
     * Analyze multiple positions (full game).
     * Returns array of analysis results, one per position.
     */
    public function analyzePositions(array $fens, int $depth = null): array
    {
        $depth = $depth ?? $this->defaultDepth;
        $results = [];

        foreach ($fens as $fen) {
            try {
                $results[] = $this->analyzePosition($fen, $depth);
            } catch (\Exception $e) {
                Log::warning("Stockfish analysis failed for FEN: {$fen}", ['error' => $e->getMessage()]);
                $results[] = [
                    'eval' => 0,
                    'bestMove' => null,
                    'pv' => [],
                    'depth' => 0,
                    'mateIn' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * Check if Stockfish binary is available.
     */
    public function isAvailable(): bool
    {
        return file_exists($this->binaryPath) && is_executable($this->binaryPath);
    }

    /**
     * Validate a FEN string to ensure it's well-formed and doesn't contain
     * characters that could be interpreted as UCI commands.
     *
     * @throws \InvalidArgumentException
     */
    private function validateFen(string $fen): void
    {
        // FEN must not contain newlines (could inject UCI commands)
        if (preg_match('/[\r\n]/', $fen)) {
            throw new \InvalidArgumentException('FEN contains invalid newline characters');
        }

        // FEN should only contain valid characters: pieces, digits, slashes,
        // spaces, color, castling, en-passant square, and move counters
        if (!preg_match('/^[rnbqkpRNBQKP1-8\/]+ [wb] [KQkq-]+ [a-h1-8-]+ \d+ \d+$/', trim($fen))) {
            throw new \InvalidArgumentException('FEN format is invalid');
        }

        // FEN must have exactly 8 ranks
        $parts = explode(' ', trim($fen));
        $ranks = explode('/', $parts[0]);
        if (count($ranks) !== 8) {
            throw new \InvalidArgumentException('FEN must contain exactly 8 ranks');
        }
    }

    private function runEngine(array $commands): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException("Stockfish binary not found at: {$this->binaryPath}");
        }

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($this->binaryPath, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start Stockfish process');
        }

        // Send commands
        $input = implode("\n", $commands) . "\n";
        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        // Read output with timeout
        stream_set_timeout($pipes[1], $this->timeout);
        $output = '';
        $startTime = time();

        while (!feof($pipes[1]) && (time() - $startTime) < $this->timeout) {
            $line = fgets($pipes[1]);
            if ($line === false) break;
            $output .= $line;

            // Stop reading after bestmove
            if (str_starts_with(trim($line), 'bestmove')) break;
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $output;
    }

    private function parseAnalysis(string $output): array
    {
        $lines = explode("\n", $output);
        $result = [
            'eval' => 0.0,
            'bestMove' => null,
            'pv' => [],
            'depth' => 0,
            'mateIn' => null,
        ];

        foreach ($lines as $line) {
            $line = trim($line);

            // Parse info lines (keep last one = deepest)
            if (str_starts_with($line, 'info') && str_contains($line, 'score')) {
                if (preg_match('/depth (\d+)/', $line, $dm)) {
                    $result['depth'] = (int) $dm[1];
                }

                if (preg_match('/score cp (-?\d+)/', $line, $sm)) {
                    $result['eval'] = (int) $sm[1] / 100;
                } elseif (preg_match('/score mate (-?\d+)/', $line, $mm)) {
                    $mateIn = (int) $mm[1];
                    $result['mateIn'] = abs($mateIn);
                    $result['eval'] = $mateIn > 0 ? 100.0 : -100.0;
                }

                if (preg_match('/pv (.+)/', $line, $pv)) {
                    $result['pv'] = explode(' ', trim($pv[1]));
                }
            }

            // Parse bestmove
            if (str_starts_with($line, 'bestmove')) {
                $parts = explode(' ', $line);
                $result['bestMove'] = $parts[1] ?? null;
            }
        }

        return $result;
    }
}
