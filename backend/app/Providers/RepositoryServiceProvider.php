<?php

namespace App\Providers;

use App\Repositories\Contracts\DashboardRepositoryInterface;
use App\Repositories\Contracts\ExamAttemptRepositoryInterface;
use App\Repositories\Contracts\ExamRepositoryInterface;
use App\Repositories\Eloquent\DashboardRepository;
use App\Repositories\Eloquent\ExamAttemptRepository;
use App\Repositories\Eloquent\ExamRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Vincula os contratos de repositório às implementações Eloquent.
     * O Laravel registra automaticamente os pares desta propriedade.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ExamRepositoryInterface::class => ExamRepository::class,
        ExamAttemptRepositoryInterface::class => ExamAttemptRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
    ];
}
