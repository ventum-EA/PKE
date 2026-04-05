<?php

namespace App\Repositories;

use App\Models\TrainingSession;
use Illuminate\Database\ConnectionInterface;

class TrainingSessionRepository
{
    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function store(array $data): TrainingSession
    {
        return TrainingSession::create($data);
    }

    public function update(TrainingSession $session, array $data): bool
    {
        return $session->update($data);
    }

    public function findById(int $id): TrainingSession
    {
        return TrainingSession::findOrFail($id);
    }

    public function getUserProgress(int $userId): array
    {
        $stats = $this->db->table('training_sessions')
            ->where('user_id', $userId)
            ->select(
                'category',
                $this->db->raw('count(*) as total'),
                $this->db->raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('category')
            ->get()
            ->toArray();

        $trend = $this->db->table('training_sessions')
            ->where('user_id', $userId)
            ->whereNotNull('is_correct')
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                $this->db->raw('DATE(created_at) as date'),
                $this->db->raw('count(*) as total'),
                $this->db->raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'by_category' => $stats,
            'trend' => $trend,
        ];
    }
}
