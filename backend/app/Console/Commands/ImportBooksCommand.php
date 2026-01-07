<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Symfony\Component\Uid\Ulid;

/**
 * 蔵書CSVインポートコマンド
 *
 * CSVファイルから蔵書データをインポートする。
 * バリデーションを行い、不正な行はスキップしてレポートを出力する。
 */
class ImportBooksCommand extends Command
{
    /**
     * コマンドのシグネチャ
     *
     * @var string
     */
    protected $signature = 'import:books
        {file : CSVファイルパス}
        {--dry-run : バリデーションのみ実行}
        {--skip-duplicates : 重複ISBNをスキップ（デフォルト有効）}
        {--no-skip-duplicates : 重複ISBNでエラー}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'CSVファイルから蔵書データをインポート';

    /**
     * バッチサイズ
     */
    private const BATCH_SIZE = 100;

    /**
     * 有効なステータス値
     *
     * @var array<string>
     */
    private const VALID_STATUSES = ['available', 'borrowed', 'reserved'];

    /**
     * スキップされた行の詳細
     *
     * @var array<array{line: int, reason: string, value: string}>
     */
    private array $skippedRows = [];

    /**
     * コマンドを実行
     */
    public function handle(): int
    {
        $filePath = $this->argument('file');
        $isDryRun = $this->option('dry-run');
        $skipDuplicates = ! $this->option('no-skip-duplicates');

        // ファイルの存在確認
        if (! file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");

            return Command::FAILURE;
        }

        // ファイルを開く
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("ファイルを開けません: {$filePath}");

            return Command::FAILURE;
        }

        // ヘッダー行を読み込み
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $this->error('CSVファイルのヘッダーが読み込めません');

            return Command::FAILURE;
        }

        // 既存ISBNを取得
        $existingIsbns = BookRecord::whereNotNull('isbn')
            ->pluck('isbn')
            ->flip()
            ->toArray();

        $totalRows = 0;
        $successCount = 0;
        $batchData = [];
        $lineNumber = 1; // ヘッダー行

        $this->info('蔵書データをインポート中...');

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $totalRows++;

            // 行データが不足している場合
            if (count($row) < count($headers)) {
                $this->addSkippedRow($lineNumber, '不正な行形式', '');

                continue;
            }

            /** @var array<string> $validHeaders */
            $validHeaders = array_filter($headers, fn ($h): bool => $h !== null);
            $data = array_combine($validHeaders, $row);
            /** @var array<string, string> $stringData */
            $stringData = array_map(fn ($v): string => (string) $v, $data);

            // バリデーション
            $validationResult = $this->validateRow($stringData, $lineNumber, $existingIsbns, $skipDuplicates);
            if ($validationResult !== null) {
                $this->addSkippedRow($lineNumber, $validationResult['reason'], $validationResult['value']);

                continue;
            }

            // バッチデータに追加
            $isbn = $stringData['isbn'] ?? '';
            $batchData[] = [
                'id' => (string) new Ulid,
                'title' => $stringData['title'],
                'author' => $stringData['author'] ?: null,
                'isbn' => $isbn ?: null,
                'publisher' => $stringData['publisher'] ?: null,
                'published_year' => $stringData['published_year'] ? (int) $stringData['published_year'] : null,
                'genre' => $stringData['genre'] ?: null,
                'status' => $stringData['status'] ?: 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // ISBNを既存リストに追加
            if ($isbn) {
                $existingIsbns[$isbn] = true;
            }

            // バッチサイズに達したらインサート
            if (count($batchData) >= self::BATCH_SIZE) {
                if (! $isDryRun) {
                    $this->insertBatch($batchData);
                }
                $successCount += count($batchData);
                $batchData = [];

                // 進捗表示
                $this->output->write("\r  処理済: {$successCount}件");
            }
        }

        // 残りのバッチをインサート
        if (! empty($batchData)) {
            if (! $isDryRun) {
                $this->insertBatch($batchData);
            }
            $successCount += count($batchData);
        }

        fclose($handle);

        // 改行
        $this->newLine();

        // 結果レポート出力
        $this->outputReport($filePath, $totalRows, $successCount, $isDryRun);

        return Command::SUCCESS;
    }

    /**
     * 行データをバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @param  int  $lineNumber  行番号
     * @param  array<string, bool>  $existingIsbns  既存ISBN
     * @param  bool  $skipDuplicates  重複スキップフラグ
     * @return array{reason: string, value: string}|null エラーがあればエラー情報、なければnull
     */
    private function validateRow(array $data, int $lineNumber, array $existingIsbns, bool $skipDuplicates): ?array
    {
        // タイトル必須
        if (empty($data['title'])) {
            return ['reason' => 'タイトル未入力', 'value' => ''];
        }

        // タイトル長さ
        if (mb_strlen($data['title']) > 255) {
            return ['reason' => 'タイトルが長すぎます', 'value' => mb_substr($data['title'], 0, 20).'...'];
        }

        // 著者長さ
        if (! empty($data['author']) && mb_strlen($data['author']) > 255) {
            return ['reason' => '著者名が長すぎます', 'value' => mb_substr($data['author'], 0, 20).'...'];
        }

        // ISBN検証
        $isbnRaw = $data['isbn'] ?? '';
        if ($isbnRaw !== '') {
            // ハイフンを除去
            $isbn = preg_replace('/[^0-9]/', '', $isbnRaw);
            if ($isbn === null) {
                return ['reason' => 'ISBN形式エラー', 'value' => $isbnRaw];
            }

            // 13桁かチェック
            if (strlen($isbn) !== 13) {
                return ['reason' => 'ISBN形式エラー（13桁必要）', 'value' => $isbnRaw];
            }

            // チェックディジット検証
            if (! $this->validateIsbn13CheckDigit($isbn)) {
                return ['reason' => 'ISBN形式エラー', 'value' => $isbn];
            }

            // 重複チェック
            if (isset($existingIsbns[$isbn])) {
                if ($skipDuplicates) {
                    return ['reason' => '重複ISBN', 'value' => $isbn];
                }

                return ['reason' => '重複ISBN（エラー）', 'value' => $isbn];
            }
        }

        // 出版年検証
        if (! empty($data['published_year'])) {
            $year = (int) $data['published_year'];
            $currentYear = (int) date('Y');
            if ($year < -2000 || $year > $currentYear) {
                return ['reason' => '無効な出版年', 'value' => $data['published_year']];
            }
        }

        // ステータス検証
        if (! empty($data['status']) && ! in_array($data['status'], self::VALID_STATUSES, true)) {
            return ['reason' => '無効な状態値', 'value' => $data['status']];
        }

        return null;
    }

    /**
     * ISBN-13のチェックディジットを検証
     *
     * @param  string  $isbn  13桁のISBN
     * @return bool 有効ならtrue
     */
    private function validateIsbn13CheckDigit(string $isbn): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn[$i];
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }

        $expectedCheckDigit = (10 - ($sum % 10)) % 10;
        $actualCheckDigit = (int) $isbn[12];

        return $expectedCheckDigit === $actualCheckDigit;
    }

    /**
     * スキップ行を記録
     *
     * @param  int  $lineNumber  行番号
     * @param  string  $reason  理由
     * @param  string  $value  問題の値
     */
    private function addSkippedRow(int $lineNumber, string $reason, string $value): void
    {
        $this->skippedRows[] = [
            'line' => $lineNumber,
            'reason' => $reason,
            'value' => $value,
        ];
    }

    /**
     * バッチインサートを実行
     *
     * @param  array<array<string, mixed>>  $batchData  バッチデータ
     */
    private function insertBatch(array $batchData): void
    {
        try {
            DB::table('books')->insert($batchData);
        } catch (\Exception $e) {
            Log::error('ImportBooksCommand batch insert failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * 結果レポートを出力
     *
     * @param  string  $filePath  ファイルパス
     * @param  int  $totalRows  総行数
     * @param  int  $successCount  成功件数
     * @param  bool  $isDryRun  ドライラン
     */
    private function outputReport(string $filePath, int $totalRows, int $successCount, bool $isDryRun): void
    {
        $skippedCount = count($this->skippedRows);

        $this->newLine();
        $this->info('=== 蔵書インポート結果 ===');

        if ($isDryRun) {
            $this->warn('[ドライラン - 実際のデータ投入なし]');
        }

        $this->line("処理ファイル: {$filePath}");
        $this->line("総行数: {$totalRows}");
        $this->line("成功: {$successCount}件");
        $this->line("スキップ: {$skippedCount}件");

        if ($skippedCount > 0) {
            $this->newLine();
            $this->warn('--- スキップ詳細 ---');
            foreach ($this->skippedRows as $skipped) {
                $valueInfo = $skipped['value'] ? " ({$skipped['value']})" : '';
                $this->line("行 {$skipped['line']}: {$skipped['reason']}{$valueInfo}");
            }
        }
    }
}
