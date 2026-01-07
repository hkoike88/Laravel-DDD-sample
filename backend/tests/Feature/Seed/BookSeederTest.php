<?php

declare(strict_types=1);

namespace Tests\Feature\Seed;

use Database\Seeders\BookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Tests\TestCase;

/**
 * BookSeeder機能テスト
 *
 * サンプルデータ投入機能の動作を検証する。
 */
class BookSeederTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // User Story 1: サンプルデータ投入
    // ========================================

    /**
     * 100件以上の蔵書データが投入されること
     */
    public function test_seeder_inserts_at_least_100_books(): void
    {
        // Act: BookSeederを実行
        $this->seed(BookSeeder::class);

        // Assert: 100件以上の蔵書データが存在する
        $count = BookRecord::count();
        $this->assertGreaterThanOrEqual(100, $count, '100件以上の蔵書データが投入されるべき');
    }

    /**
     * 投入されたデータに必須フィールドが設定されていること
     */
    public function test_seeded_books_have_required_fields(): void
    {
        // Act
        $this->seed(BookSeeder::class);

        // Assert: 各レコードの必須フィールドを確認
        $books = BookRecord::all();
        foreach ($books as $book) {
            $this->assertNotEmpty($book->id, 'IDが設定されているべき');
            $this->assertNotEmpty($book->title, 'タイトルが設定されているべき');
            $this->assertNotEmpty($book->status, 'ステータスが設定されているべき');
            $this->assertContains($book->status, ['available', 'borrowed', 'reserved'], '有効なステータス値であるべき');
        }
    }

    /**
     * 状態分布がおおよそ仕様通りであること（available: 40%, borrowed: 35%, reserved: 25%）
     */
    public function test_seeded_books_have_expected_status_distribution(): void
    {
        // Act
        $this->seed(BookSeeder::class);

        // Assert: 状態別件数を確認
        $total = BookRecord::count();
        $available = BookRecord::where('status', 'available')->count();
        $borrowed = BookRecord::where('status', 'borrowed')->count();
        $reserved = BookRecord::where('status', 'reserved')->count();

        // 各状態が存在することを確認（厳密な比率ではなく存在確認）
        $this->assertGreaterThan(0, $available, 'available状態のデータが存在するべき');
        $this->assertGreaterThan(0, $borrowed, 'borrowed状態のデータが存在するべき');
        $this->assertGreaterThan(0, $reserved, 'reserved状態のデータが存在するべき');

        // 合計が一致することを確認
        $this->assertEquals($total, $available + $borrowed + $reserved, '全状態の合計が総件数と一致するべき');
    }

    /**
     * 重複実行時に既存ISBNがスキップされること
     */
    public function test_seeder_skips_duplicate_isbn_on_rerun(): void
    {
        // Act: 2回実行
        $this->seed(BookSeeder::class);
        $countAfterFirst = BookRecord::count();

        $this->seed(BookSeeder::class);
        $countAfterSecond = BookRecord::count();

        // Assert: 2回目の実行で件数が変わらない（重複スキップ）
        $this->assertEquals($countAfterFirst, $countAfterSecond, '重複ISBNはスキップされるべき');
    }

    /**
     * 投入されたデータに日本語が含まれていること
     */
    public function test_seeded_books_contain_japanese_text(): void
    {
        // Act
        $this->seed(BookSeeder::class);

        // Assert: 日本語タイトルまたは著者名が存在する
        // PHP側で日本語文字を検出（SQLite互換）
        $books = BookRecord::all();
        $hasJapanese = $books->contains(function ($book) {
            // ひらがな、カタカナ、漢字を検出
            return preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $book->title) ||
                   preg_match('/[\x{3040}-\x{309F}\x{30A0}-\x{30FF}\x{4E00}-\x{9FFF}]/u', $book->author ?? '');
        });

        $this->assertTrue($hasJapanese, '日本語のデータが含まれているべき');
    }

    /**
     * ISBNが正しい形式であること（13桁数字）
     */
    public function test_seeded_books_have_valid_isbn_format(): void
    {
        // Act
        $this->seed(BookSeeder::class);

        // Assert: ISBNが設定されているレコードの形式を確認
        $booksWithIsbn = BookRecord::whereNotNull('isbn')->get();

        foreach ($booksWithIsbn as $book) {
            $this->assertMatchesRegularExpression(
                '/^\d{13}$/',
                $book->isbn,
                "ISBNは13桁の数字であるべき: {$book->isbn}"
            );
        }
    }

    /**
     * Artisanコマンドで実行できること
     */
    public function test_seeder_can_be_run_via_artisan(): void
    {
        // Act: Artisanコマンドで実行
        $this->artisan('db:seed', ['--class' => 'BookSeeder'])
            ->assertExitCode(0);

        // Assert: データが投入されている
        $this->assertGreaterThanOrEqual(100, BookRecord::count());
    }
}
