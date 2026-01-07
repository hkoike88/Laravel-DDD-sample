<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\Domain\Staff\Application\Repositories\PasswordHistoryRepository;
use Packages\Domain\Staff\Domain\Repositories\PasswordHistoryRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // パスワード履歴リポジトリのバインディング
        // @feature 001-security-preparation
        $this->app->bind(
            PasswordHistoryRepositoryInterface::class,
            PasswordHistoryRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
