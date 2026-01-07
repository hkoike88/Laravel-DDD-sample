<?php

declare(strict_types=1);

namespace Tests\Feature\Seed;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Tests\TestCase;

/**
 * GenerateBooksCommand機能テスト
 *
 * ランダムデータ生成コマンドの動作を検証する。
 */
class GenerateBooksCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系テスト
    // ========================================

    /**
     * デフォルトで100件生成されること
     */
    public function test_generates_100_books_by_default(): void
    {
        // Act
        $this->artisan('book:generate')
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 100);
    }

    /**
     * 件数を指定して生成できること
     */
    public function test_can_generate_specified_count(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 50])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 50);
    }

    /**
     * 大量データ（500件）を生成できること
     */
    public function test_can_generate_500_books(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 500])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 500);
    }

    // ========================================
    // 状態指定テスト
    // ========================================

    /**
     * --status=availableで全てavailable状態になること
     */
    public function test_generates_all_available_with_status_option(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 50, '--status' => 'available'])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 50);
        $this->assertEquals(50, BookRecord::where('status', 'available')->count());
    }

    /**
     * --status=borrowedで全てborrowed状態になること
     */
    public function test_generates_all_borrowed_with_status_option(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 50, '--status' => 'borrowed'])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 50);
        $this->assertEquals(50, BookRecord::where('status', 'borrowed')->count());
    }

    /**
     * --status=reservedで全てreserved状態になること
     */
    public function test_generates_all_reserved_with_status_option(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 50, '--status' => 'reserved'])
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseCount('books', 50);
        $this->assertEquals(50, BookRecord::where('status', 'reserved')->count());
    }

    // ========================================
    // エラー系テスト
    // ========================================

    /**
     * 最大件数（10,000件）を超えるとエラーになること
     */
    public function test_returns_error_when_count_exceeds_maximum(): void
    {
        // Act & Assert
        $this->artisan('book:generate', ['count' => 10001])
            ->assertExitCode(1);

        // データは生成されていない
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 0件指定でエラーになること
     */
    public function test_returns_error_when_count_is_zero(): void
    {
        // Act & Assert
        $this->artisan('book:generate', ['count' => 0])
            ->assertExitCode(1);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 負数指定でエラーになること
     */
    public function test_returns_error_when_count_is_negative(): void
    {
        // Act & Assert
        $this->artisan('book:generate', ['count' => -1])
            ->assertExitCode(1);

        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 無効なステータス指定でエラーになること
     */
    public function test_returns_error_for_invalid_status(): void
    {
        // Act & Assert
        $this->artisan('book:generate', ['count' => 10, '--status' => 'invalid'])
            ->assertExitCode(1);

        $this->assertDatabaseCount('books', 0);
    }

    // ========================================
    // 結果サマリーテスト
    // ========================================

    /**
     * 生成結果サマリーが出力されること
     */
    public function test_outputs_generation_summary(): void
    {
        // Act & Assert
        $this->artisan('book:generate', ['count' => 100])
            ->expectsOutputToContain('100件の蔵書データを生成しました')
            ->assertExitCode(0);
    }

    /**
     * 状態別件数が出力されること
     */
    public function test_outputs_status_breakdown(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 100])
            ->assertExitCode(0);

        // Assert: 全状態にデータが存在
        $available = BookRecord::where('status', 'available')->count();
        $borrowed = BookRecord::where('status', 'borrowed')->count();
        $reserved = BookRecord::where('status', 'reserved')->count();

        $this->assertGreaterThan(0, $available);
        $this->assertGreaterThan(0, $borrowed);
        $this->assertGreaterThan(0, $reserved);
        $this->assertEquals(100, $available + $borrowed + $reserved);
    }

    // ========================================
    // データ品質テスト
    // ========================================

    /**
     * 生成されたデータが有効なフォーマットであること
     */
    public function test_generated_data_has_valid_format(): void
    {
        // Act
        $this->artisan('book:generate', ['count' => 10])
            ->assertExitCode(0);

        // Assert
        $books = BookRecord::all();
        foreach ($books as $book) {
            // ID: ULID形式（26文字）
            $this->assertEquals(26, strlen($book->id));

            // タイトル: 空でない
            $this->assertNotEmpty($book->title);

            // ISBN: 13桁
            $this->assertMatchesRegularExpression('/^\d{13}$/', $book->isbn);

            // ステータス: 有効な値
            $this->assertContains($book->status, ['available', 'borrowed', 'reserved']);
        }
    }
}
