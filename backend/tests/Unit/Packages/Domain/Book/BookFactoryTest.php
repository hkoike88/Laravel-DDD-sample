<?php

declare(strict_types=1);

namespace Tests\Unit\Packages\Domain\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Tests\TestCase;

/**
 * BookFactoryユニットテスト
 *
 * BookFactoryのデータ生成ロジックを検証する。
 */
class BookFactoryTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 基本生成テスト
    // ========================================

    /**
     * ファクトリでBookRecordが生成できること
     */
    public function test_can_create_book_record(): void
    {
        // Act
        $book = BookRecord::factory()->create();

        // Assert
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 生成されたレコードに必須フィールドが設定されていること
     */
    public function test_generated_record_has_required_fields(): void
    {
        // Act
        $book = BookRecord::factory()->create();

        // Assert
        $this->assertNotEmpty($book->id);
        $this->assertNotEmpty($book->title);
        $this->assertNotEmpty($book->status);
    }

    /**
     * IDがULID形式（26文字）であること
     */
    public function test_id_is_ulid_format(): void
    {
        // Act
        $book = BookRecord::factory()->create();

        // Assert: ULIDは26文字
        $this->assertEquals(26, strlen($book->id));
        $this->assertMatchesRegularExpression('/^[0-9A-Z]{26}$/', $book->id);
    }

    /**
     * ISBNが13桁の数字であること
     */
    public function test_isbn_is_13_digit_number(): void
    {
        // Act
        $book = BookRecord::factory()->create();

        // Assert
        $this->assertMatchesRegularExpression('/^\d{13}$/', $book->isbn);
    }

    /**
     * ISBNのチェックディジットが正しいこと
     */
    public function test_isbn_has_valid_check_digit(): void
    {
        // Act: 複数生成して検証
        $books = BookRecord::factory()->count(10)->create();

        foreach ($books as $book) {
            $isbn = $book->isbn;

            // チェックディジット計算
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $digit = (int) $isbn[$i];
                $weight = ($i % 2 === 0) ? 1 : 3;
                $sum += $digit * $weight;
            }
            $expectedCheckDigit = (10 - ($sum % 10)) % 10;
            $actualCheckDigit = (int) $isbn[12];

            // Assert
            $this->assertEquals(
                $expectedCheckDigit,
                $actualCheckDigit,
                "ISBN {$isbn} のチェックディジットが不正"
            );
        }
    }

    // ========================================
    // 状態指定テスト
    // ========================================

    /**
     * available状態を指定できること
     */
    public function test_can_create_available_book(): void
    {
        // Act
        $book = BookRecord::factory()->available()->create();

        // Assert
        $this->assertEquals('available', $book->status);
    }

    /**
     * borrowed状態を指定できること
     */
    public function test_can_create_borrowed_book(): void
    {
        // Act
        $book = BookRecord::factory()->borrowed()->create();

        // Assert
        $this->assertEquals('borrowed', $book->status);
    }

    /**
     * reserved状態を指定できること
     */
    public function test_can_create_reserved_book(): void
    {
        // Act
        $book = BookRecord::factory()->reserved()->create();

        // Assert
        $this->assertEquals('reserved', $book->status);
    }

    // ========================================
    // 大量生成テスト
    // ========================================

    /**
     * 複数件を一度に生成できること
     */
    public function test_can_create_multiple_records(): void
    {
        // Act
        $books = BookRecord::factory()->count(50)->create();

        // Assert
        $this->assertCount(50, $books);
        $this->assertDatabaseCount('books', 50);
    }

    /**
     * 大量生成時にIDが重複しないこと
     */
    public function test_no_duplicate_ids_when_creating_many(): void
    {
        // Act: メモリ制限を考慮して30件に制限
        $books = BookRecord::factory()->count(30)->create();

        // Assert: ID重複なし
        $ids = $books->pluck('id')->toArray();
        $uniqueIds = array_unique($ids);
        $this->assertCount(30, $uniqueIds);
    }

    /**
     * 大量生成時にISBNが重複しないこと
     */
    public function test_no_duplicate_isbns_when_creating_many(): void
    {
        // Act: メモリ制限を考慮して30件に制限
        $books = BookRecord::factory()->count(30)->create();

        // Assert: ISBN重複なし
        $isbns = $books->pluck('isbn')->toArray();
        $uniqueIsbns = array_unique($isbns);
        $this->assertCount(30, $uniqueIsbns);
    }

    // ========================================
    // 属性オーバーライドテスト
    // ========================================

    /**
     * 属性をオーバーライドできること
     */
    public function test_can_override_attributes(): void
    {
        // Act
        $book = BookRecord::factory()->create([
            'title' => 'カスタムタイトル',
            'author' => 'カスタム著者',
            'publisher' => 'カスタム出版社',
        ]);

        // Assert
        $this->assertEquals('カスタムタイトル', $book->title);
        $this->assertEquals('カスタム著者', $book->author);
        $this->assertEquals('カスタム出版社', $book->publisher);
    }

    // ========================================
    // 日本語データテスト
    // ========================================

    /**
     * 出版社が日本語であること
     */
    public function test_publisher_is_japanese(): void
    {
        // Act
        $book = BookRecord::factory()->create();

        // Assert: 日本の出版社名リストに含まれる
        $japanesePublishers = [
            '岩波書店',
            '新潮社',
            '講談社',
            '文藝春秋',
            '角川書店',
            '集英社',
            '小学館',
            '中央公論新社',
            '筑摩書房',
            '光文社',
            '河出書房新社',
            '早川書房',
            'PHP研究所',
            '日本経済新聞出版',
            'ダイヤモンド社',
        ];
        $this->assertContains($book->publisher, $japanesePublishers);
    }
}
