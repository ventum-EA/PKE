<?php

namespace App\Http\Controllers;

use App\Models\Opening;
use App\Models\UserOpeningProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpeningController extends Controller
{
    /**
     * GET /api/openings — all openings grouped by category
     */
    public function index(Request $request): JsonResponse
    {
        $query = Opening::orderBy('sort_order');

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_lv', 'like', "%{$search}%")
                  ->orWhere('eco', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $openings = $query->get();

        // Attach user progress if authenticated
        if ($user = $request->user()) {
            $progress = UserOpeningProgress::where('user_id', $user->id)
                ->pluck('times_practiced', 'opening_id');
            $openings->each(function ($o) use ($progress) {
                $o->user_practiced = $progress[$o->id] ?? 0;
            });
        }

        return response()->json([
            'openings' => $openings,
            'categories' => [
                'A' => ['label' => 'A — Flangu atklātnes', 'desc' => 'Angļu, Rēti, Holandiešu, Benoni u.c.'],
                'B' => ['label' => 'B — Puslīdz atvērtās', 'desc' => 'Sicīliešu, Karo-Kann, Pīrca u.c.'],
                'C' => ['label' => 'C — Atvērtās spēles', 'desc' => 'Spāņu, Itāliešu, Francūzu u.c.'],
                'D' => ['label' => 'D — Slēgtās spēles', 'desc' => 'Dāmas gambīts, Slāvu, Londonas u.c.'],
                'E' => ['label' => 'E — Indiešu aizsardzības', 'desc' => 'Nimcoviča, Karaļindiešu u.c.'],
            ],
        ]);
    }

    /**
     * GET /api/openings/{id} — single opening with explanation
     */
    public function show(Opening $opening): JsonResponse
    {
        return response()->json(['opening' => $opening]);
    }

    /**
     * POST /api/openings/{id}/progress — track practice completion
     */
    public function trackProgress(Request $request, Opening $opening): JsonResponse
    {
        $data = $request->validate([
            'color' => 'required|in:white,black',
            'completed' => 'boolean',
        ]);

        $progress = UserOpeningProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'opening_id' => $opening->id],
            [
                'times_practiced' => \DB::raw('times_practiced + 1'),
                'practiced_as_' . $data['color'] => true,
                'completed' => $data['completed'] ?? false,
                'last_practiced_at' => now(),
            ]
        );

        return response()->json(['progress' => $progress]);
    }
}
