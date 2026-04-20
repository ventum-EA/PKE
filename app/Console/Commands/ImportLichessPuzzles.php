<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PuzzleBank;
use Illuminate\Console\Command;

class ImportLichessPuzzles extends Command
{
    protected $signature = 'puzzles:import
        {file : Path to the Lichess puzzle CSV file}
        {--limit=2000 : Maximum puzzles to import}
        {--min-rating=800 : Minimum puzzle rating}
        {--max-rating=2200 : Maximum puzzle rating}
        {--min-popularity=70 : Minimum popularity score}';

    protected $description = 'Import puzzles from the Lichess puzzle database CSV (download from https://database.lichess.org/lichess_db_puzzle.csv.zst)';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $minRating = (int) $this->option('min-rating');
        $maxRating = (int) $this->option('max-rating');
        $minPop = (int) $this->option('min-popularity');

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Cannot open file: {$file}");
            return self::FAILURE;
        }

        // Skip header
        $header = fgetcsv($handle);
        $this->info("Reading CSV... Columns: " . implode(', ', $header));

        $imported = 0;
        $skipped = 0;
        $batch = [];
        $batchSize = 100;

        $bar = $this->output->createProgressBar($limit);
        $bar->start();

        while (($row = fgetcsv($handle)) !== false && $imported < $limit) {
            // Columns: PuzzleId, FEN, Moves, Rating, RatingDeviation, Popularity, NbPlays, Themes, GameUrl, OpeningTags
            if (count($row) < 8) { $skipped++; continue; }

            [$puzzleId, $fen, $moves, $rating, $rd, $popularity, $nbPlays, $themes] = $row;
            $openingTags = $row[9] ?? null;

            $rating = (int) $rating;
            $popularity = (int) $popularity;

            // Filter
            if ($rating < $minRating || $rating > $maxRating) { $skipped++; continue; }
            if ($popularity < $minPop) { $skipped++; continue; }

            // Determine difficulty from rating
            $difficulty = match (true) {
                $rating < 1200 => 1,
                $rating < 1800 => 2,
                default        => 3,
            };

            // Clean themes: "fork pin mateIn2" → "fork,pin,mateIn2"
            $themesCleaned = str_replace(' ', ',', trim($themes));

            $batch[] = [
                'source_id'    => $puzzleId,
                'fen'          => $fen,
                'solution'     => $moves,
                'rating'       => $rating,
                'themes'       => substr($themesCleaned, 0, 200),
                'opening_tags' => $openingTags ? substr($openingTags, 0, 200) : null,
                'difficulty'   => $difficulty,
                'popularity'   => $popularity,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];

            if (count($batch) >= $batchSize) {
                PuzzleBank::upsert($batch, ['source_id'], [
                    'fen', 'solution', 'rating', 'themes', 'opening_tags', 'difficulty', 'popularity',
                ]);
                $imported += count($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        // Flush remaining batch
        if (!empty($batch)) {
            PuzzleBank::upsert($batch, ['source_id'], [
                'fen', 'solution', 'rating', 'themes', 'opening_tags', 'difficulty', 'popularity',
            ]);
            $imported += count($batch);
            $bar->advance(count($batch));
        }

        fclose($handle);
        $bar->finish();

        $this->newLine(2);
        $this->info("Done! Imported: {$imported}, Skipped: {$skipped}");
        $this->info("Total puzzles in bank: " . PuzzleBank::count());

        return self::SUCCESS;
    }
}
