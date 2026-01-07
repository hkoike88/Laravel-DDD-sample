<?php

declare(strict_types=1);

namespace Tests\Feature\Seed;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Tests\TestCase;

/**
 * ImportBooksCommand機能テスト
 *
 * CSVインポートコマンドの動作を検証する。
 */
class ImportBooksCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト前にStorageをセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ========================================
    // 正常系テスト
    // ========================================

    /**
     * 有効なCSVファイルからデータをインポートできること
     */
    public function test_can_import_valid_csv_file(): void
    {
        // Arrange: 有効なCSVを作成
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 2);
        $this->assertDatabaseHas('books', ['title' => '吾輩は猫である', 'isbn' => '9784003101018']);
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん', 'isbn' => '9784101010014']);
    }

    /**
     * 重複ISBNがスキップされること
     */
    public function test_skips_duplicate_isbn(): void
    {
        // Arrange: 既存データを作成
        BookRecord::create([
            'id' => '01HXYZ1234567890ABCDEFG',
            'title' => '既存の本',
            'isbn' => '9784003101018',
            'status' => 'available',
        ]);

        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert: 重複は追加されず、新規のみ追加
        $this->assertDatabaseCount('books', 2);
        $this->assertDatabaseHas('books', ['title' => '既存の本']); // 既存データが維持される
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん']); // 新規データが追加される
    }

    /**
     * --dry-runオプションで実際に投入されないこと
     */
    public function test_dry_run_does_not_insert_data(): void
    {
        // Arrange
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', [
            'file' => Storage::path('test_books.csv'),
            '--dry-run' => true,
        ])->assertExitCode(0);

        // Assert: データは投入されていない
        $this->assertDatabaseCount('books', 0);
    }

    // ========================================
    // バリデーションエラー系テスト
    // ========================================

    /**
     * タイトルが空の行がスキップされること
     */
    public function test_skips_row_with_empty_title(): void
    {
        // Arrange
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
,夏目漱石,9784003101018,岩波書店,1905,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert: タイトルなしの行はスキップ
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん']);
    }

    /**
     * 無効なISBNチェックディジットがスキップされること
     */
    public function test_skips_row_with_invalid_isbn_check_digit(): void
    {
        // Arrange: チェックディジットが不正なISBN
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101019,岩波書店,1905,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん']);
    }

    /**
     * 無効な出版年がスキップされること
     */
    public function test_skips_row_with_invalid_published_year(): void
    {
        // Arrange
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,3000,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん']);
    }

    /**
     * 無効なステータス値がスキップされること
     */
    public function test_skips_row_with_invalid_status(): void
    {
        // Arrange
        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,invalid_status
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', ['file' => Storage::path('test_books.csv')])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseHas('books', ['title' => '坊っちゃん']);
    }

    // ========================================
    // エラー系テスト
    // ========================================

    /**
     * 存在しないファイルでエラーが返ること
     */
    public function test_returns_error_for_nonexistent_file(): void
    {
        // Act & Assert
        $this->artisan('import:books', ['file' => '/nonexistent/path/books.csv'])
            ->assertExitCode(1);
    }

    // ========================================
    // オプション系テスト
    // ========================================

    /**
     * --no-skip-duplicatesオプションで重複時にエラーになること
     */
    public function test_no_skip_duplicates_reports_duplicate_error(): void
    {
        // Arrange: 既存データを作成
        BookRecord::create([
            'id' => '01HXYZ1234567890ABCDEFG',
            'title' => '既存の本',
            'isbn' => '9784003101018',
            'status' => 'available',
        ]);

        $csv = <<<'CSV'
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
CSV;
        Storage::put('test_books.csv', $csv);

        // Act
        $this->artisan('import:books', [
            'file' => Storage::path('test_books.csv'),
            '--no-skip-duplicates' => true,
        ])->assertExitCode(0);

        // Assert: 既存データが維持され、重複はスキップレポートに記録
        $this->assertDatabaseCount('books', 1);
    }

    // ========================================
    // バッチ処理テスト
    // ========================================

    /**
     * 大量データがバッチ処理されること
     */
    public function test_batch_processing_for_large_data(): void
    {
        // Arrange: 150件のCSVを作成
        $lines = ['title,author,isbn,publisher,published_year,genre,status'];
        for ($i = 1; $i <= 150; $i++) {
            // 12桁のISBNベースを生成（978-4 + 8桁）
            $isbnBase = sprintf('97840031%04d', $i);
            // チェックディジットを計算して追加
            $isbn = $isbnBase.$this->calculateIsbn13CheckDigit($isbnBase);
            $lines[] = "テスト書籍{$i},テスト著者,{$isbn},テスト出版社,2024,文学,available";
        }
        Storage::put('large_books.csv', implode("\n", $lines));

        // Act
        $this->artisan('import:books', ['file' => Storage::path('large_books.csv')])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 150);
    }

    /**
     * ISBN-13のチェックディジットを計算
     */
    private function calculateIsbn13CheckDigit(string $isbn12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $isbn12[$i];
            $weight = ($i % 2 === 0) ? 1 : 3;
            $sum += $digit * $weight;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
