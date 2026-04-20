<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GameMoveRepositoryInterface;
use App\Contracts\GameRepositoryInterface;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Interface → concrete class bindings.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        GameRepositoryInterface::class     => GameRepository::class,
        GameMoveRepositoryInterface::class => GameMoveRepository::class,
    ];
}
