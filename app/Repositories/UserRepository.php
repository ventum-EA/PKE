<?php

namespace App\Repositories;

use App\Models\User;
use Spatie\QueryBuilder\QueryBuilder;

class UserRepository
{
    public function findById(int $id): User
    {
        return User::findOrFail($id);
    }

    public function store(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getFilteredUsers(int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return QueryBuilder::for(User::class)
            ->allowedFilters(['name', 'email', 'role'])
            ->allowedSorts(['name', 'created_at', 'elo_rating'])
            ->defaultSort('-created_at')
            ->paginate($perPage);
    }
}
