<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Symfony\Component\Uid\Ulid;

/**
 * 蔵書サンプルデータシーダー
 *
 * CSVファイルから日本の古典文学を中心としたサンプル蔵書データを投入する。
 * 重複ISBNはスキップして継続する。
 */
class BookSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * サンプルCSVファイルのパス
     */
    private const CSV_PATH = 'storage/app/sample_books.csv';

    /**
     * シーダーを実行
     */
    public function run(): void
    {
        $csvPath = base_path(self::CSV_PATH);

        if (! file_exists($csvPath)) {
            $this->command->error("CSVファイルが見つかりません: {$csvPath}");

            return;
        }

        $this->command->info('蔵書データを投入中...');

        $handle = $this->openCsvFile($csvPath);
        if ($handle === null) {
            return;
        }

        $headers = $this->readHeaders($handle);
        if ($headers === null) {
            fclose($handle);

            return;
        }

        $result = $this->importData($handle, $headers);
        fclose($handle);

        $this->outputResult($result['inserted'], $result['skipped']);
    }

    /**
     * CSVファイルを開く
     *
     * @return resource|null ファイルハンドル（失敗時はnull）
     */
    private function openCsvFile(string $csvPath)
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("CSVファイルを開けません: {$csvPath}");

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
            $this->command->error('CSVファイルのヘッダーが読み込めません');

            return null;
        }

        /** @var array<string> */
        return array_filter($headers, fn ($h): bool => $h !== null);
    }

    /**
     * データをインポート
     *
     * @param  resource  $handle  ファイルハンドル
     * @param  array<string>  $headers  ヘッダー配列
     * @return array{inserted: int, skipped: int} インポート結果
     */
    private function importData($handle, array $headers): array
    {
        $existingIsbns = $this->getExistingIsbns();
        $inserted = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $result = $this->processRow($row, $headers, $existingIsbns);

                if ($result === null) {
                    $skipped++;

                    continue;
                }

                $this->createBook($result['data']);
                if ($result['isbn']) {
                    $existingIsbns[$result['isbn']] = true;
                }
                $inserted++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BookSeeder failed: '.$e->getMessage());
            throw $e;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
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
     * @return array{data: array<string, string|null>, isbn: string|null}|null 処理結果（スキップ時はnull）
     */
    private function processRow(array $row, array $headers, array $existingIsbns): ?array
    {
        if (count($row) < 7) {
            return null;
        }

        $data = array_combine($headers, $row);

        /** @var string|null $isbn */
        $isbn = $data['isbn'] ?? null;
        if ($isbn !== null && $isbn !== '' && isset($existingIsbns[$isbn])) {
            return null;
        }

        return [
            'data' => $data,
            'isbn' => $isbn,
        ];
    }

    /**
     * 蔵書レコードを作成
     *
     * @param  array<string, string|null>  $data  データ配列
     */
    private function createBook(array $data): void
    {
        $isbn = $data['isbn'] ?? null;

        BookRecord::create([
            'id' => (string) new Ulid,
            'title' => $data['title'],
            'author' => $data['author'] ?: null,
            'isbn' => $isbn ?: null,
            'publisher' => $data['publisher'] ?: null,
            'published_year' => $data['published_year'] ? (int) $data['published_year'] : null,
            'genre' => $data['genre'] ?: null,
            'status' => $data['status'] ?: 'available',
        ]);
    }

    /**
     * 結果を出力
     */
    private function outputResult(int $inserted, int $skipped): void
    {
        $this->command->info("{$inserted}件の蔵書データを投入しました。");
        if ($skipped > 0) {
            $this->command->info("{$skipped}件をスキップしました（重複ISBNまたは不正行）。");
        }
    }
}
