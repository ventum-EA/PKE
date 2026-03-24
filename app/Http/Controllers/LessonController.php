<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\UserLessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * GET /api/lessons — all lessons grouped by category with counts
     */
    public function index(Request $request): JsonResponse
    {
        $lessons = Lesson::withCount('puzzles')
            ->orderBy('sort_order')
            ->get();

        // Attach user progress if authenticated
        if ($user = $request->user()) {
            $progress = UserLessonProgress::where('user_id', $user->id)
                ->get()
                ->keyBy('lesson_id');
            $lessons->each(function ($l) use ($progress) {
                $p = $progress[$l->id] ?? null;
                $l->user_completed = $p?->completed ?? false;
                $l->user_best_score = $p?->best_score ?? 0;
            });
        }

        // Build category metadata
        $categories = $lessons->groupBy('category')->map(function ($group, $key) {
            $first = $group->first();
            return [
                'key' => $key,
                'icon' => $first->icon,
                'color' => $first->color,
                'lesson_count' => $group->count(),
                'puzzle_count' => $group->sum('puzzles_count'),
            ];
        });

        $categoryMeta = [
            'basics'   => ['title' => 'Pamati', 'desc' => 'Figūru vērtība, kontrole, attīstība'],
            'fork'     => ['title' => 'Dakša (Fork)', 'desc' => 'Viena figūra uzbrūk divām vienlaikus'],
            'pin'      => ['title' => 'Piespraušana (Pin)', 'desc' => 'Figūra nevar kustēties, jo aiz tās ir vērtīgāka'],
            'skewer'   => ['title' => 'Šķēres (Skewer)', 'desc' => 'Uzbrukums vērtīgākai figūrai, aiz kuras ir mazāk vērtīga'],
            'discovery'=> ['title' => 'Atklātais uzbrukums', 'desc' => 'Vienas figūras gājiens atver citas uzbrukuma līniju'],
            'back_rank'=> ['title' => 'Pēdējā rinda', 'desc' => 'Mats uz pēdējās rindas'],
            'sacrifice'=> ['title' => 'Upuris', 'desc' => 'Materiāla atdošana priekšrocības iegūšanai'],
            'checkmate_patterns' => ['title' => 'Mata zīmējumi', 'desc' => 'Klasiskās mattēšanas kombinācijas'],
        ];

        return response()->json([
            'lessons' => $lessons,
            'categories' => $categories->map(fn($c, $k) => array_merge($c, $categoryMeta[$k] ?? [])),
        ]);
    }

    /**
     * GET /api/lessons/{lesson} — single lesson with puzzles
     */
    public function show(Lesson $lesson): JsonResponse
    {
        $lesson->load('puzzles');
        return response()->json(['lesson' => $lesson]);
    }

    /**
     * POST /api/lessons/{lesson}/progress — track lesson completion
     */
    public function trackProgress(Request $request, Lesson $lesson): JsonResponse
    {
        $data = $request->validate([
            'puzzles_solved' => 'required|integer|min:0',
            'puzzles_total'  => 'required|integer|min:1',
        ]);

        $score = (int) round(($data['puzzles_solved'] / $data['puzzles_total']) * 100);

        $progress = UserLessonProgress::firstOrNew([
            'user_id' => $request->user()->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->puzzles_solved = $data['puzzles_solved'];
        $progress->puzzles_total = $data['puzzles_total'];
        $progress->completed = true;
        $progress->best_score = max($progress->best_score ?? 0, $score);
        $progress->last_attempted_at = now();
        $progress->save();

        return response()->json(['progress' => $progress, 'score' => $score]);
    }
}
