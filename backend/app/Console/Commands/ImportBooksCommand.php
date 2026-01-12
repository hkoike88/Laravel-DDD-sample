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

        $handle = $this->openCsvFile($filePath);
        if ($handle === null) {
            return Command::FAILURE;
        }

        $headers = $this->readHeaders($handle);
        if ($headers === null) {
            fclose($handle);

            return Command::FAILURE;
        }

        $result = $this->processRows($handle, $headers, $isDryRun, $skipDuplicates);
        fclose($handle);

        $this->outputReport($filePath, $result['totalRows'], $result['successCount'], $isDryRun);

        return Command::SUCCESS;
    }

    /**
     * CSVファイルを開く
     *
     * @return resource|null ファイルハンドル（失敗時はnull）
     */
    private function openCsvFile(string $filePath)
    {
        if (! file_exists($filePath)) {
            $this->error("ファイルが見つかりません: {$filePath}");

            return null;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("ファイルを開けません: {$filePath}");

            return null;
        }

        return $handle;
    }

    /**
     * ヘッダー行を読み込む
     *
     * @param  resource  $handle  ファイルハンドル
     * @return array<string>|null ヘッダー配列（失敗時はnull）
     */
    private function readHeaders($handle): ?array
    {
        $headers = fgetcsv($handle);
        if ($headers === false) {
            $this->error('CSVファイルのヘッダーが読み込めません');

            return null;
        }

        /** @var array<string> */
        return array_filter($headers, fn ($h): bool => $h !== null);
    }

    /**
     * CSVの全行を処理
     *
     * @param  resource  $handle  ファイルハンドル
     * @param  array<string>  $headers  ヘッダー配列
     * @return array{totalRows: int, successCount: int} 処理結果
     */
    private function processRows($handle, array $headers, bool $isDryRun, bool $skipDuplicates): array
    {
        $existingIsbns = $this->getExistingIsbns();
        $totalRows = 0;
        $successCount = 0;
        $batchData = [];
        $lineNumber = 1; // ヘッダー行

        $this->info('蔵書データをインポート中...');

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $totalRows++;

            $processedData = $this->processRow($row, $headers, $lineNumber, $existingIsbns, $skipDuplicates);
            if ($processedData === null) {
                continue;
            }

            $batchData[] = $processedData['record'];
            if ($processedData['isbn']) {
                $existingIsbns[$processedData['isbn']] = true;
            }

            $successCount = $this->flushBatchIfNeeded($batchData, $successCount, $isDryRun);
        }

        $successCount = $this->flushRemainingBatch($batchData, $successCount, $isDryRun);
        $this->newLine();

        return ['totalRows' => $totalRows, 'successCount' => $successCount];
    }

    /**
     * 既存ISBNリストを取得
     *
     * @return array<string, bool>
     */
    private function getExistingIsbns(): array
    {
        /** @var array<string, bool> */
        return BookRecord::whereNotNull('isbn')
            ->pluck('isbn')
            ->flip()
            ->toArray();
    }

    /**
     * 1行を処理
     *
     * @param  array<int, string|null>  $row  CSV行データ
     * @param  array<string>  $headers  ヘッダー配列
     * @param  array<string, bool>  $existingIsbns  既存ISBN
     * @return array{record: array<string, mixed>, isbn: string}|null レコードデータ（スキップ時はnull）
     */
    private function processRow(array $row, array $headers, int $lineNumber, array $existingIsbns, bool $skipDuplicates): ?array
    {
        if (count($row) < count($headers)) {
            $this->addSkippedRow($lineNumber, '不正な行形式', '');

            return null;
        }

        $stringData = $this->convertRowToData($row, $headers);

        $validationResult = $this->validateRow($stringData, $lineNumber, $existingIsbns, $skipDuplicates);
        if ($validationResult !== null) {
            $this->addSkippedRow($lineNumber, $validationResult['reason'], $validationResult['value']);

            return null;
        }

        $isbn = $stringData['isbn'] ?? '';

        return [
            'record' => $this->createRecord($stringData),
            'isbn' => $isbn,
        ];
    }

    /**
     * CSV行をデータ配列に変換
     *
     * @param  array<int, string|null>  $row  CSV行データ
     * @param  array<string>  $headers  ヘッダー配列
     * @return array<string, string>
     */
    private function convertRowToData(array $row, array $headers): array
    {
        $data = array_combine($headers, $row);

        /** @var array<string, string> $stringData */
        $stringData = array_map(fn ($v): string => (string) $v, $data);

        return $stringData;
    }

    /**
     * レコードデータを作成
     *
     * @param  array<string, string>  $data  行データ
     * @return array<string, mixed>
     */
    private function createRecord(array $data): array
    {
        $isbn = $data['isbn'] ?? '';

        return [
            'id' => (string) new Ulid,
            'title' => $data['title'],
            'author' => $data['author'] ?: null,
            'isbn' => $isbn ?: null,
            'publisher' => $data['publisher'] ?: null,
            'published_year' => $data['published_year'] ? (int) $data['published_year'] : null,
            'genre' => $data['genre'] ?: null,
            'status' => $data['status'] ?: 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * バッチサイズに達していればフラッシュ
     *
     * @param  array<array<string, mixed>>  $batchData  バッチデータ（参照渡し）
     */
    private function flushBatchIfNeeded(array &$batchData, int $successCount, bool $isDryRun): int
    {
        if (count($batchData) >= self::BATCH_SIZE) {
            if (! $isDryRun) {
                $this->insertBatch($batchData);
            }
            $successCount += count($batchData);
            $batchData = [];
            $this->output->write("\r  処理済: {$successCount}件");
        }

        return $successCount;
    }

    /**
     * 残りのバッチをフラッシュ
     *
     * @param  array<array<string, mixed>>  $batchData  バッチデータ
     */
    private function flushRemainingBatch(array $batchData, int $successCount, bool $isDryRun): int
    {
        if (! empty($batchData)) {
            if (! $isDryRun) {
                $this->insertBatch($batchData);
            }
            $successCount += count($batchData);
        }

        return $successCount;
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
        return $this->validateTitle($data)
            ?? $this->validateAuthor($data)
            ?? $this->validateIsbn($data, $existingIsbns, $skipDuplicates)
            ?? $this->validatePublishedYear($data)
            ?? $this->validateStatus($data);
    }

    /**
     * タイトルをバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @return array{reason: string, value: string}|null
     */
    private function validateTitle(array $data): ?array
    {
        if (empty($data['title'])) {
            return ['reason' => 'タイトル未入力', 'value' => ''];
        }

        if (mb_strlen($data['title']) > 255) {
            return ['reason' => 'タイトルが長すぎます', 'value' => mb_substr($data['title'], 0, 20).'...'];
        }

        return null;
    }

    /**
     * 著者をバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @return array{reason: string, value: string}|null
     */
    private function validateAuthor(array $data): ?array
    {
        if (! empty($data['author']) && mb_strlen($data['author']) > 255) {
            return ['reason' => '著者名が長すぎます', 'value' => mb_substr($data['author'], 0, 20).'...'];
        }

        return null;
    }

    /**
     * ISBNをバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @param  array<string, bool>  $existingIsbns  既存ISBN
     * @return array{reason: string, value: string}|null
     */
    private function validateIsbn(array $data, array $existingIsbns, bool $skipDuplicates): ?array
    {
        $isbnRaw = $data['isbn'] ?? '';
        if ($isbnRaw === '') {
            return null;
        }

        $isbn = preg_replace('/[^0-9]/', '', $isbnRaw);
        if ($isbn === null || strlen($isbn) !== 13) {
            return ['reason' => 'ISBN形式エラー（13桁必要）', 'value' => $isbnRaw];
        }

        if (! $this->validateIsbn13CheckDigit($isbn)) {
            return ['reason' => 'ISBN形式エラー', 'value' => $isbn];
        }

        if (isset($existingIsbns[$isbn])) {
            $reason = $skipDuplicates ? '重複ISBN' : '重複ISBN（エラー）';

            return ['reason' => $reason, 'value' => $isbn];
        }

        return null;
    }

    /**
     * 出版年をバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @return array{reason: string, value: string}|null
     */
    private function validatePublishedYear(array $data): ?array
    {
        if (empty($data['published_year'])) {
            return null;
        }

        $year = (int) $data['published_year'];
        $currentYear = (int) date('Y');

        if ($year < -2000 || $year > $currentYear) {
            return ['reason' => '無効な出版年', 'value' => $data['published_year']];
        }

        return null;
    }

    /**
     * ステータスをバリデーション
     *
     * @param  array<string, string>  $data  行データ
     * @return array{reason: string, value: string}|null
     */
    private function validateStatus(array $data): ?array
    {
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
