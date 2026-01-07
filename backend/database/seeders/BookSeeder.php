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

        $inserted = 0;
        $skipped = 0;

        // 既存のISBNを取得（重複チェック用）
        $existingIsbns = BookRecord::whereNotNull('isbn')
            ->pluck('isbn')
            ->flip()
            ->toArray();

        // CSVファイルを読み込み
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->error("CSVファイルを開けません: {$csvPath}");

            return;
        }

        // ヘッダー行を読み飛ばし
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            $this->command->error('CSVファイルのヘッダーが読み込めません');

            return;
        }

        // トランザクション内で投入
        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 7) {
                    $skipped++;

                    continue;
                }

                /** @var array<string> $validHeaders */
                $validHeaders = array_filter($headers, fn ($h): bool => $h !== null);
                $data = array_combine($validHeaders, $row);

                // ISBNの重複チェック
                $isbn = $data['isbn'] ?? null;
                if ($isbn && isset($existingIsbns[$isbn])) {
                    $skipped++;

                    continue;
                }

                // レコードを作成
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

                // 投入したISBNを記録（重複防止）
                if ($isbn) {
                    $existingIsbns[$isbn] = true;
                }

                $inserted++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BookSeeder failed: '.$e->getMessage());
            throw $e;
        } finally {
            fclose($handle);
        }

        $this->command->info("{$inserted}件の蔵書データを投入しました。");
        if ($skipped > 0) {
            $this->command->info("{$skipped}件をスキップしました（重複ISBNまたは不正行）。");
        }
    }
}
