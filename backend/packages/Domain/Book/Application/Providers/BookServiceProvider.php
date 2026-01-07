<?php

declare(strict_types=1);

namespace Packages\Domain\Book\Application\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\Domain\Book\Application\Repositories\EloquentBookRepository;
use Packages\Domain\Book\Application\UseCases\Commands\CreateBook\CreateBookHandler;
use Packages\Domain\Book\Application\UseCases\Queries\SearchBooks\SearchBooksHandler;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;

/**
 * 蔵書ドメインサービスプロバイダー
 *
 * 蔵書ドメインの依存関係を登録する。
 */
class BookServiceProvider extends ServiceProvider
{
    /**
     * サービスを登録
     */
    public function register(): void
    {
        // リポジトリインターフェースと具象実装のバインディング
        $this->app->bind(
            BookRepositoryInterface::class,
            EloquentBookRepository::class
        );

        // ユースケースハンドラの登録（具象クラスのため自動解決されるが明示的に登録）
        $this->app->singleton(SearchBooksHandler::class);
        $this->app->singleton(CreateBookHandler::class);
    }

    /**
     * サービスを起動
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../Presentation/routes.php');
    }
}
