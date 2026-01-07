<?php

declare(strict_types=1);

namespace Tests\Feature\Book;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Book\Infrastructure\EloquentModels\BookRecord;
use Packages\Domain\Staff\Infrastructure\EloquentModels\StaffRecord;
use Tests\TestCase;

/**
 * 蔵書登録API機能テスト
 *
 * POST /api/books エンドポイントのテストケースを網羅する。
 */
class CreateBookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の認証済み職員
     */
    private StaffRecord $staff;

    /**
     * テストセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用職員を作成
        $this->staff = StaffRecord::create([
            'id' => '01HQXYZ000000000STAFF01',
            'email' => 'staff@example.com',
            'password' => bcrypt('password123'),
            'name' => 'テスト職員',
            'is_locked' => false,
            'failed_login_attempts' => 0,
            'locked_at' => null,
        ]);
    }

    // ========================================
    // User Story 1: 図書情報の登録
    // ========================================

    /**
     * T008: タイトルのみで蔵書登録が成功すること（201 Created）
     */
    public function test_can_create_book_with_title_only(): void
    {
        // Act: タイトルのみで登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
        ]);

        // Assert: 201 Createdとデータが返される
        $response->assertStatus(201)
            ->assertJsonPath('data.title', '吾輩は猫である')
            ->assertJsonPath('data.status', 'available');

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('books', [
            'title' => '吾輩は猫である',
            'status' => 'available',
        ]);
    }

    /**
     * T009: 全項目入力で蔵書登録が成功すること（201 Created）
     */
    public function test_can_create_book_with_all_fields(): void
    {
        // Act: 全項目を入力して登録（認証済み、ハイフンなしISBN）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784003101018',
            'publisher' => '岩波書店',
            'published_year' => 1905,
            'genre' => '文学',
        ]);

        // Assert: 201 Createdと全データが返される
        $response->assertStatus(201)
            ->assertJsonPath('data.title', '吾輩は猫である')
            ->assertJsonPath('data.author', '夏目漱石')
            ->assertJsonPath('data.isbn', '9784003101018')
            ->assertJsonPath('data.publisher', '岩波書店')
            ->assertJsonPath('data.published_year', 1905)
            ->assertJsonPath('data.genre', '文学')
            ->assertJsonPath('data.status', 'available');

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784003101018',
            'publisher' => '岩波書店',
            'published_year' => 1905,
            'genre' => '文学',
        ]);
    }

    /**
     * T010: ISBN付きで蔵書登録が成功すること（201 Created）
     */
    public function test_can_create_book_with_isbn(): void
    {
        // Act: ISBN付きで登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '坊っちゃん',
            'isbn' => '4003101014',  // ISBN-10形式
        ]);

        // Assert: 201 CreatedとISBNが返される
        $response->assertStatus(201)
            ->assertJsonPath('data.title', '坊っちゃん')
            ->assertJsonPath('data.isbn', '4003101014');

        // データベースに保存されていることを確認
        $this->assertDatabaseHas('books', [
            'title' => '坊っちゃん',
            'isbn' => '4003101014',
        ]);
    }

    // ========================================
    // User Story 2: 登録時の入力検証
    // ========================================

    /**
     * T015: タイトル未入力で422エラーが返ること
     */
    public function test_create_book_without_title_returns_validation_error(): void
    {
        // Act: タイトルなしで登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', []);

        // Assert: 422エラーとエラーメッセージ
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * T016: 不正なISBN形式で422エラーが返ること
     */
    public function test_create_book_with_invalid_isbn_returns_validation_error(): void
    {
        // Act: 不正なISBN形式で登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'isbn' => 'invalid-isbn',
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * T017: 出版年に非数値で422エラーが返ること
     */
    public function test_create_book_with_non_numeric_published_year_returns_validation_error(): void
    {
        // Act: 出版年に非数値を指定（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'published_year' => 'not-a-number',
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_year']);
    }

    /**
     * T018: タイトル文字数超過（501文字）で422エラーが返ること
     */
    public function test_create_book_with_title_exceeding_max_length_returns_validation_error(): void
    {
        // Act: 501文字のタイトルで登録（認証済み）
        $longTitle = str_repeat('あ', 501);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => $longTitle,
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * T019: 出版年範囲外（999年、現在年+2）で422エラーが返ること
     */
    public function test_create_book_with_published_year_out_of_range_returns_validation_error(): void
    {
        // Act: 出版年999で登録（認証済み）- 1000より小さい
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'published_year' => 999,
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_year']);

        // Act: 現在年+2で登録（認証済み）- 現在年+1より大きい
        $futureYear = (int) date('Y') + 2;
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '未来の本',
            'published_year' => $futureYear,
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['published_year']);
    }

    /**
     * T020: 空白のみのタイトルで422エラーが返ること
     */
    public function test_create_book_with_whitespace_only_title_returns_validation_error(): void
    {
        // Act: 空白のみのタイトルで登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '   ',
        ]);

        // Assert: 422エラー（トリム後に空文字となりrequiredエラー）
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    // ========================================
    // User Story 3: 登録結果の確認
    // ========================================

    /**
     * T024: レスポンスにidが含まれること
     */
    public function test_create_book_response_contains_id(): void
    {
        // Act: 蔵書を登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
        ]);

        // Assert: idが含まれる
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id'],
            ]);

        // IDがULID形式であることを確認（26文字）
        $id = $response->json('data.id');
        $this->assertNotEmpty($id);
        $this->assertEquals(26, strlen($id));
    }

    /**
     * T025: レスポンスに全入力項目が含まれること
     */
    public function test_create_book_response_contains_all_input_fields(): void
    {
        // Act: 全項目を入力して登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784003101018',
            'publisher' => '岩波書店',
            'published_year' => 1905,
            'genre' => '文学',
        ]);

        // Assert: 全項目が含まれる
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'publisher',
                    'published_year',
                    'genre',
                    'status',
                ],
            ]);
    }

    /**
     * T026: statusが"available"で返ること
     */
    public function test_create_book_response_status_is_available(): void
    {
        // Act: 蔵書を登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
        ]);

        // Assert: statusがavailable
        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'available');
    }

    /**
     * T027: 登録後に検索APIで発見可能なこと
     */
    public function test_created_book_is_searchable(): void
    {
        // Arrange: 蔵書を登録（認証済み）
        $createResponse = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
        ]);
        $createResponse->assertStatus(201);
        $bookId = $createResponse->json('data.id');

        // Act: 検索APIでタイトル検索
        $searchResponse = $this->getJson('/api/books?title=猫');

        // Assert: 登録した蔵書が検索結果に含まれる
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $bookId)
            ->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    // ========================================
    // 追加テスト: エッジケース
    // ========================================

    /**
     * 著者名の最大文字数（100文字）で登録できること
     */
    public function test_can_create_book_with_max_length_author(): void
    {
        // Act: 著者名100文字で登録（認証済み）
        $maxAuthor = str_repeat('あ', 100);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => 'テスト書籍',
            'author' => $maxAuthor,
        ]);

        // Assert: 登録成功
        $response->assertStatus(201)
            ->assertJsonPath('data.author', $maxAuthor);
    }

    /**
     * 著者名が101文字で422エラーが返ること
     */
    public function test_create_book_with_author_exceeding_max_length_returns_validation_error(): void
    {
        // Act: 著者名101文字で登録（認証済み）
        $longAuthor = str_repeat('あ', 101);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => 'テスト書籍',
            'author' => $longAuthor,
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['author']);
    }

    /**
     * 出版社名の最大文字数（100文字）で登録できること
     */
    public function test_can_create_book_with_max_length_publisher(): void
    {
        // Act: 出版社名100文字で登録（認証済み）
        $maxPublisher = str_repeat('あ', 100);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => 'テスト書籍',
            'publisher' => $maxPublisher,
        ]);

        // Assert: 登録成功
        $response->assertStatus(201)
            ->assertJsonPath('data.publisher', $maxPublisher);
    }

    /**
     * ジャンルの最大文字数（100文字）で登録できること
     */
    public function test_can_create_book_with_max_length_genre(): void
    {
        // Act: ジャンル100文字で登録（認証済み）
        $maxGenre = str_repeat('あ', 100);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => 'テスト書籍',
            'genre' => $maxGenre,
        ]);

        // Assert: 登録成功
        $response->assertStatus(201)
            ->assertJsonPath('data.genre', $maxGenre);
    }

    /**
     * ジャンルが101文字で422エラーが返ること
     */
    public function test_create_book_with_genre_exceeding_max_length_returns_validation_error(): void
    {
        // Act: ジャンル101文字で登録（認証済み）
        $longGenre = str_repeat('あ', 101);
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => 'テスト書籍',
            'genre' => $longGenre,
        ]);

        // Assert: 422エラー
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['genre']);
    }

    /**
     * 出版年の境界値（1000と現在年+1）で登録できること
     */
    public function test_can_create_book_with_boundary_published_year(): void
    {
        // Act: 出版年1000で登録（認証済み）
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '古代の本',
            'published_year' => 1000,
        ]);

        // Assert: 登録成功
        $response->assertStatus(201)
            ->assertJsonPath('data.published_year', 1000);

        // Act: 現在年+1で登録（認証済み）
        $maxYear = (int) date('Y') + 1;
        $response = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '予定出版の本',
            'published_year' => $maxYear,
        ]);

        // Assert: 登録成功
        $response->assertStatus(201)
            ->assertJsonPath('data.published_year', $maxYear);
    }

    /**
     * 同一ISBNで複数の蔵書が登録できること（複本対応）
     */
    public function test_can_create_multiple_books_with_same_isbn(): void
    {
        // Arrange: 同じISBNで1冊目を登録（認証済み）
        $response1 = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である（1冊目）',
            'isbn' => '9784003101018',
        ]);
        $response1->assertStatus(201);
        $bookId1 = $response1->json('data.id');

        // Act: 同じISBNで2冊目を登録（認証済み）
        $response2 = $this->actingAs($this->staff)->postJson('/api/books', [
            'title' => '吾輩は猫である（2冊目）',
            'isbn' => '9784003101018',
        ]);

        // Assert: 登録成功し、異なるIDが割り当てられる
        $response2->assertStatus(201);
        $bookId2 = $response2->json('data.id');

        $this->assertNotEquals($bookId1, $bookId2);

        // データベースに2件存在することを確認
        $this->assertEquals(2, BookRecord::where('isbn', '9784003101018')->count());
    }
}
