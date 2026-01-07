<?php

declare(strict_types=1);

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Symfony\Component\Uid\Ulid;
use Tests\TestCase;

/**
 * 蔵書検索API機能テスト
 *
 * GET /api/books エンドポイントのテストケースを網羅する。
 */
class SearchBooksTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の蔵書データを作成
     *
     * @param  array<string, mixed>  $attributes  属性
     * @return BookRecord 作成した蔵書レコード
     */
    private function createBook(array $attributes = []): BookRecord
    {
        return BookRecord::create(array_merge([
            'id' => (string) new Ulid,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => null,
            'publisher' => 'テスト出版社',
            'published_year' => 2024,
            'genre' => 'テスト',
            'status' => 'available',
        ], $attributes));
    }

    // ========================================
    // User Story 1: キーワード検索
    // ========================================

    /**
     * T015: タイトルの部分一致検索ができること
     */
    public function test_can_search_books_by_title(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石']);
        $this->createBook(['title' => '三四郎', 'author' => '夏目漱石']);

        // Act: タイトルで検索
        $response = $this->getJson('/api/books?title=猫');

        // Assert: 「猫」を含むタイトルのみ返される
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    /**
     * T016: 著者名の部分一致検索ができること
     */
    public function test_can_search_books_by_author(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石']);
        $this->createBook(['title' => '羅生門', 'author' => '芥川龍之介']);

        // Act: 著者名で検索
        $response = $this->getJson('/api/books?author=夏目');

        // Assert: 「夏目」を含む著者の書籍のみ返される
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * T017: タイトルと著者名の複合検索ができること（AND条件）
     */
    public function test_can_search_books_by_title_and_author(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        $this->createBook(['title' => '猫の事務所', 'author' => '宮沢賢治']);
        $this->createBook(['title' => '坊っちゃん', 'author' => '夏目漱石']);

        // Act: タイトルと著者名の両方で検索
        $response = $this->getJson('/api/books?title=猫&author=夏目');

        // Assert: 両条件を満たす書籍のみ返される
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である')
            ->assertJsonPath('data.0.author', '夏目漱石');
    }

    // ========================================
    // User Story 2: ISBN検索
    // ========================================

    /**
     * T022: ISBN-13での完全一致検索ができること
     */
    public function test_can_search_books_by_isbn13(): void
    {
        // Arrange: テストデータを作成
        $this->createBook([
            'title' => '吾輩は猫である',
            'isbn' => '9784003101018',
        ]);
        $this->createBook([
            'title' => '坊っちゃん',
            'isbn' => '9784101010014',
        ]);

        // Act: ISBN-13で検索
        $response = $this->getJson('/api/books?isbn=9784003101018');

        // Assert: 完全一致する書籍のみ返される
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    /**
     * T023: ISBN-10での完全一致検索ができること
     */
    public function test_can_search_books_by_isbn10(): void
    {
        // Arrange: テストデータを作成
        $this->createBook([
            'title' => '吾輩は猫である',
            'isbn' => '4003101014',
        ]);

        // Act: ISBN-10で検索
        $response = $this->getJson('/api/books?isbn=4003101014');

        // Assert: 完全一致する書籍のみ返される
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    /**
     * T024: 存在しないISBNで検索すると空の結果が返ること
     */
    public function test_search_by_nonexistent_isbn_returns_empty(): void
    {
        // Arrange: テストデータを作成
        $this->createBook([
            'title' => '吾輩は猫である',
            'isbn' => '9784003101018',
        ]);

        // Act: 存在しないISBNで検索
        $response = $this->getJson('/api/books?isbn=9999999999999');

        // Assert: 空の結果が返る
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    // ========================================
    // User Story 3: ページネーション
    // ========================================

    /**
     * T029: デフォルトのページネーション（page=1, per_page=20）が適用されること
     */
    public function test_default_pagination(): void
    {
        // Arrange: 25件のテストデータを作成
        for ($i = 1; $i <= 25; $i++) {
            $this->createBook(['title' => "テスト書籍{$i}"]);
        }

        // Act: タイトル検索でリクエスト
        $response = $this->getJson('/api/books?title=テスト');

        // Assert: デフォルトで20件返される
        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);
    }

    /**
     * T030: カスタムページとper_pageでページネーションができること
     */
    public function test_custom_pagination(): void
    {
        // Arrange: 100件のテストデータを作成
        for ($i = 1; $i <= 100; $i++) {
            $this->createBook(['title' => sprintf('テスト書籍%03d', $i)]);
        }

        // Act: page=3, per_page=20でリクエスト
        $response = $this->getJson('/api/books?title=テスト&page=3&per_page=20');

        // Assert: 41〜60件目が返される
        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.page', 3)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 100)
            ->assertJsonPath('meta.last_page', 5);
    }

    /**
     * T031: 総ページ数を超えるページを指定すると空の結果が返ること
     */
    public function test_page_exceeds_total_returns_empty(): void
    {
        // Arrange: 10件のテストデータを作成
        for ($i = 1; $i <= 10; $i++) {
            $this->createBook(['title' => "テスト書籍{$i}"]);
        }

        // Act: 存在しないページを指定
        $response = $this->getJson('/api/books?title=テスト&page=100');

        // Assert: 空の結果が返る
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 10);
    }

    /**
     * T032: per_pageの最大値（100）が適用されること
     */
    public function test_per_page_max_limit(): void
    {
        // Arrange: 150件のテストデータを作成
        for ($i = 1; $i <= 150; $i++) {
            $this->createBook(['title' => "テスト書籍{$i}"]);
        }

        // Act: per_page=100でリクエスト
        $response = $this->getJson('/api/books?title=テスト&per_page=100');

        // Assert: 最大100件返される
        $response->assertStatus(200)
            ->assertJsonCount(100, 'data')
            ->assertJsonPath('meta.per_page', 100);
    }

    /**
     * T033: 無効なper_page値でバリデーションエラーが返ること
     */
    public function test_invalid_per_page_returns_validation_error(): void
    {
        // Act: per_pageに101を指定（最大値超過）
        $response = $this->getJson('/api/books?title=test&per_page=101');

        // Assert: バリデーションエラー
        $response->assertStatus(422);
    }

    // ========================================
    // User Story 4: 検索条件なしでの全件検索
    // ========================================

    /**
     * 検索条件なしでリクエストすると全件検索として結果が返ること
     */
    public function test_no_params_returns_all_books(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である']);
        $this->createBook(['title' => '坊っちゃん']);

        // Act: パラメータなしでリクエスト
        $response = $this->getJson('/api/books');

        // Assert: 全件検索として結果が返る
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);
    }

    /**
     * 空文字のパラメータでリクエストすると全件検索として結果が返ること
     */
    public function test_empty_string_params_returns_all_books(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である']);

        // Act: 空文字のパラメータでリクエスト
        $response = $this->getJson('/api/books?title=&author=&isbn=');

        // Assert: 全件検索として結果が返る
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }

    /**
     * T038: 検索条件ありでデータベースが空の場合、空の配列が返ること
     */
    public function test_empty_database_with_criteria_returns_empty_array(): void
    {
        // Act: 検索条件付きでリクエスト（データなし）
        $response = $this->getJson('/api/books?title=test');

        // Assert: 空の結果が返る
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    // ========================================
    // Phase 7: エッジケース
    // ========================================

    /**
     * T041: SQLインジェクション攻撃が防止されること
     */
    public function test_sql_injection_prevention(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => 'テスト書籍']);

        // Act: SQLインジェクションを試みる
        $response = $this->getJson('/api/books?title='.urlencode("'; DROP TABLE books; --"));

        // Assert: エラーなく空の結果が返る（テーブルは削除されない）
        $response->assertStatus(200);

        // テーブルが存在することを確認
        $this->assertDatabaseHas('books', ['title' => 'テスト書籍']);
    }

    /**
     * T042: 長い検索文字列でバリデーションエラーが返ること
     */
    public function test_long_search_string_validation(): void
    {
        // Act: 256文字のタイトルで検索
        $longTitle = str_repeat('あ', 256);
        $response = $this->getJson('/api/books?title='.urlencode($longTitle));

        // Assert: バリデーションエラー
        $response->assertStatus(422);
    }

    // ========================================
    // レスポンス構造の検証
    // ========================================

    /**
     * レスポンスが期待される構造を持つこと
     */
    public function test_response_has_expected_structure(): void
    {
        // Arrange: テストデータを作成
        $this->createBook([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784003101018',
            'publisher' => '岩波書店',
            'published_year' => 1905,
            'genre' => '文学',
            'status' => 'available',
        ]);

        // Act
        $response = $this->getJson('/api/books?title=猫');

        // Assert: レスポンス構造を検証
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'author',
                        'isbn',
                        'publisher',
                        'published_year',
                        'genre',
                        'status',
                    ],
                ],
                'meta' => [
                    'total',
                    'page',
                    'per_page',
                    'last_page',
                ],
            ]);
    }

    // ========================================
    // 検索統計ログテスト
    // ========================================

    /**
     * T026: 検索実行時に統計ログが記録されること
     */
    public function test_search_creates_log_entry(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        // Act: 検索を実行
        $response = $this->getJson('/api/books?title=猫&author=夏目');
        $response->assertStatus(200);

        // Assert: ログが記録されている
        $this->assertDatabaseHas('search_logs', [
            'title_keyword' => '猫',
            'author_keyword' => '夏目',
            'isbn_keyword' => null,
            'result_count' => 1,
        ]);
    }

    /**
     * 検索ログにはユーザー識別情報が含まれないこと（匿名）
     */
    public function test_search_log_is_anonymous(): void
    {
        // Arrange: テストデータを作成
        $this->createBook(['title' => 'テスト書籍']);

        // Act: 検索を実行
        $response = $this->getJson('/api/books?title=テスト');
        $response->assertStatus(200);

        // Assert: ログにはユーザーIDカラムが存在しない（匿名）
        $log = \App\Models\SearchLog::latest('searched_at')->first();
        $this->assertNotNull($log);
        $this->assertEquals('テスト', $log->title_keyword);
        // ユーザー識別情報が存在しないことを確認
        $this->assertFalse(isset($log->user_id));
        $this->assertFalse(isset($log->ip_address));
    }

    /**
     * 検索結果0件でもログが記録されること
     */
    public function test_search_log_recorded_even_with_no_results(): void
    {
        // Act: 該当なしの検索を実行
        $response = $this->getJson('/api/books?isbn=9999999999999');
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Assert: ログが記録されている
        $this->assertDatabaseHas('search_logs', [
            'isbn_keyword' => '9999999999999',
            'result_count' => 0,
        ]);
    }
}
