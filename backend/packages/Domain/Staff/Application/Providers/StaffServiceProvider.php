<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Application\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\Domain\Staff\Application\Repositories\EloquentStaffRepository;
use Packages\Domain\Staff\Application\UseCases\Commands\CreateStaff\CreateStaffHandler;
use Packages\Domain\Staff\Application\UseCases\Queries\GetStaffList\GetStaffListHandler;
use Packages\Domain\Staff\Domain\Repositories\StaffRepositoryInterface;
use Packages\Domain\Staff\Domain\Services\PasswordGenerator;
use Packages\Domain\Staff\Infrastructure\AuditLog\StaffAuditLogger;

/**
 * Staff ドメインサービスプロバイダー
 *
 * Staff ドメインの依存関係を Laravel コンテナに登録する。
 */
class StaffServiceProvider extends ServiceProvider
{
    /**
     * サービスの登録
     */
    public function register(): void
    {
        // リポジトリ
        $this->app->bind(
            StaffRepositoryInterface::class,
            EloquentStaffRepository::class
        );

        // ドメインサービス
        $this->app->singleton(PasswordGenerator::class);
        $this->app->singleton(StaffAuditLogger::class);

        // ユースケースハンドラー（007-staff-account-create）
        $this->app->bind(CreateStaffHandler::class, function ($app) {
            return new CreateStaffHandler(
                $app->make(StaffRepositoryInterface::class),
                $app->make(PasswordGenerator::class),
                $app->make(StaffAuditLogger::class),
            );
        });

        $this->app->bind(GetStaffListHandler::class, function ($app) {
            return new GetStaffListHandler(
                $app->make(StaffRepositoryInterface::class),
            );
        });
    }

    /**
     * サービスの起動処理
     */
    public function boot(): void
    {
        // 必要に応じてルート登録等を追加
    }
}
