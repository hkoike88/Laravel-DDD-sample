<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;

/**
 * 蔵書ランダムデータ生成コマンド
 *
 * BookFactoryを使用してランダムな蔵書データを生成する。
 * 大量データでのテストを可能にする。
 */
class GenerateBooksCommand extends Command
{
    /**
     * コマンドのシグネチャ
     *
     * @var string
     */
    protected $signature = 'book:generate
        {count=100 : 生成件数}
        {--status= : 状態を指定（available/borrowed/reserved）}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'ランダムな蔵書データを生成';

    /**
     * 最大生成件数
     */
    private const MAX_COUNT = 10000;

    /**
     * 有効なステータス値
     *
     * @var array<string>
     */
    private const VALID_STATUSES = ['available', 'borrowed', 'reserved'];

    /**
     * コマンドを実行
     */
    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $status = $this->option('status');

        // 件数バリデーション
        if ($count <= 0) {
            $this->error('件数は1以上を指定してください。');

            return Command::FAILURE;
        }

        if ($count > self::MAX_COUNT) {
            $this->error('件数は最大'.self::MAX_COUNT.'件までです。');

            return Command::FAILURE;
        }

        // ステータスバリデーション
        if ($status !== null && ! in_array($status, self::VALID_STATUSES, true)) {
            $this->error("無効なステータス値です: {$status}");
            $this->error('有効な値: '.implode(', ', self::VALID_STATUSES));

            return Command::FAILURE;
        }

        $this->info('蔵書データを生成中...');

        // ファクトリを取得
        $factory = BookRecord::factory();

        // ステータス指定がある場合
        if ($status !== null) {
            $factory = match ($status) {
                'available' => $factory->available(),
                'borrowed' => $factory->borrowed(),
                'reserved' => $factory->reserved(),
            };
        }

        // データ生成
        $factory->count($count)->create();

        // 結果サマリー出力
        $this->outputSummary($count, $status);

        return Command::SUCCESS;
    }

    /**
     * 生成結果サマリーを出力
     *
     * @param  int  $count  生成件数
     * @param  string|null  $status  指定されたステータス
     */
    private function outputSummary(int $count, ?string $status): void
    {
        $this->info("{$count}件の蔵書データを生成しました。");

        if ($status !== null) {
            // 状態指定時は全て同じ状態
            $this->line("  - {$status}: {$count}件");
        } else {
            // 状態別件数を表示
            $available = BookRecord::where('status', 'available')->count();
            $borrowed = BookRecord::where('status', 'borrowed')->count();
            $reserved = BookRecord::where('status', 'reserved')->count();

            $this->line("  - available: {$available}件");
            $this->line("  - borrowed: {$borrowed}件");
            $this->line("  - reserved: {$reserved}件");
        }
    }
}
