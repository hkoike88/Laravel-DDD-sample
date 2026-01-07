# バックエンド API 設計標準

## 概要

本プロジェクトのバックエンドにおける API 設計標準を定める。
RESTful 設計原則に基づき、フロントエンドとの一貫性のある連携を実現する。

---

## 基本方針

- **リソース指向**: URL はリソースを表し、HTTP メソッドで操作を表現
- **一貫性**: 命名規則、レスポンス形式を統一
- **自己記述的**: レスポンスに必要な情報を含め、クライアントの追加リクエストを削減
- **バージョニング**: API の後方互換性を維持しつつ進化可能に
- **セキュリティ**: 認証・認可・レート制限を適切に実装

---

## エンドポイント設計

### URL 命名規約

| ルール | 説明 | 例 |
|--------|------|-----|
| リソース名は複数形 | コレクションを表す | `/books`, `/users` |
| スネークケース | 単語の区切りはアンダースコア | `/book_loans` |
| 小文字のみ | 大文字は使用しない | `/api/v1/books` |
| 動詞は使わない | HTTP メソッドで表現 | ✗ `/getBooks` → ✓ `/books` |
| ネストは2階層まで | 深すぎるネストは避ける | `/books/{id}/loans` |

### URL 構造

```
https://{host}/api/v{version}/{resource}/{id}/{sub-resource}
```

**例:**
```
GET  https://api.example.com/api/v1/books
GET  https://api.example.com/api/v1/books/01HXYZ123
GET  https://api.example.com/api/v1/books/01HXYZ123/loans
```

### HTTP メソッド

| メソッド | 用途 | 冪等性 | 安全性 | レスポンス |
|----------|------|--------|--------|-----------|
| GET | リソース取得 | Yes | Yes | 200 OK |
| POST | リソース作成 | No | No | 201 Created |
| PUT | リソース全体更新 | Yes | No | 200 OK |
| PATCH | リソース部分更新 | Yes | No | 200 OK |
| DELETE | リソース削除 | Yes | No | 204 No Content |

### CRUD エンドポイント

```
# 基本的な CRUD 操作
GET    /api/v1/books              # 一覧取得
GET    /api/v1/books/{id}         # 詳細取得
POST   /api/v1/books              # 新規作成
PUT    /api/v1/books/{id}         # 全体更新（全フィールド必須）
PATCH  /api/v1/books/{id}         # 部分更新（変更フィールドのみ）
DELETE /api/v1/books/{id}         # 削除
```

### ネストしたリソース

```
# 親リソースに従属するリソース
GET    /api/v1/books/{bookId}/loans           # 書籍の貸出履歴
POST   /api/v1/books/{bookId}/loans           # 書籍の貸出作成
GET    /api/v1/users/{userId}/reservations    # ユーザーの予約一覧
```

### アクション（RPC 的な操作）

リソースの状態変更など、CRUD に当てはまらない操作は POST + 動詞で表現。

```
# 状態変更アクション
POST   /api/v1/loans/{id}/return          # 返却処理
POST   /api/v1/orders/{id}/cancel         # キャンセル
POST   /api/v1/orders/{id}/confirm        # 確定
POST   /api/v1/users/{id}/activate        # 有効化
POST   /api/v1/users/{id}/deactivate      # 無効化

# バルク操作
POST   /api/v1/books/bulk_delete          # 一括削除
POST   /api/v1/books/bulk_update          # 一括更新
```

---

## リクエスト設計

### リクエストヘッダー

| ヘッダー | 必須 | 説明 | 例 |
|---------|------|------|-----|
| Content-Type | Yes（POST/PUT/PATCH） | リクエストボディの形式 | `application/json` |
| Accept | No | 期待するレスポンス形式 | `application/json` |
| Authorization | 条件付き | 認証トークン | `Bearer {token}` |
| X-Request-Id | No | リクエスト追跡ID | ULID 形式 |
| Accept-Language | No | 希望する言語 | `ja`, `en` |

### リクエストボディ

```json
// POST /api/v1/books
{
  "isbn": "9784123456789",
  "title": "サンプル書籍",
  "author": "山田太郎",
  "publisher": "サンプル出版",
  "published_at": "2025-01-15"
}
```

**ルール:**
- プロパティ名はスネークケース
- 日付は ISO 8601 形式（`YYYY-MM-DD` または `YYYY-MM-DDTHH:mm:ssZ`）
- 金額は整数（円単位、小数点なし）
- 真偽値は `true` / `false`

### クエリパラメータ

| 用途 | パラメータ | 例 |
|------|-----------|-----|
| ページネーション | `page`, `per_page` | `?page=2&per_page=20` |
| ソート | `sort`, `order` | `?sort=created_at&order=desc` |
| フィルタリング | フィールド名 | `?status=available&author=山田` |
| 検索 | `q`, `search` | `?q=プログラミング` |
| フィールド選択 | `fields` | `?fields=id,title,author` |
| 関連リソース | `include` | `?include=author,publisher` |

---

## レスポンス設計

### 成功レスポンス

#### 単一リソース

```json
// GET /api/v1/books/01HXYZ123
// Status: 200 OK
{
  "data": {
    "id": "01HXYZ123456789ABCDEF",
    "isbn": "9784123456789",
    "title": "サンプル書籍",
    "author": "山田太郎",
    "publisher": "サンプル出版",
    "published_at": "2025-01-15",
    "status": "available",
    "created_at": "2025-12-25T10:30:00+09:00",
    "updated_at": "2025-12-25T10:30:00+09:00"
  }
}
```

#### コレクション（ページネーション付き）

```json
// GET /api/v1/books?page=1&per_page=20
// Status: 200 OK
{
  "data": [
    {
      "id": "01HXYZ123456789ABCDEF",
      "isbn": "9784123456789",
      "title": "サンプル書籍1",
      "author": "山田太郎",
      "status": "available"
    },
    {
      "id": "01HXYZ987654321FEDCBA",
      "isbn": "9784987654321",
      "title": "サンプル書籍2",
      "author": "佐藤花子",
      "status": "on_loan"
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/v1/books?page=1",
    "last": "/api/v1/books?page=8",
    "prev": null,
    "next": "/api/v1/books?page=2"
  }
}
```

#### リソース作成

```json
// POST /api/v1/books
// Status: 201 Created
// Location: /api/v1/books/01HXYZ123456789ABCDEF
{
  "data": {
    "id": "01HXYZ123456789ABCDEF",
    "isbn": "9784123456789",
    "title": "サンプル書籍",
    "author": "山田太郎",
    "created_at": "2025-12-25T10:30:00+09:00"
  }
}
```

#### リソース削除

```
// DELETE /api/v1/books/01HXYZ123
// Status: 204 No Content
// （レスポンスボディなし）
```

#### アクション実行

```json
// POST /api/v1/loans/01HXYZ123/return
// Status: 200 OK
{
  "data": {
    "id": "01HXYZ123456789ABCDEF",
    "book_id": "01HXYZBOOK12345",
    "user_id": "01HXYZUSER12345",
    "status": "returned",
    "returned_at": "2025-12-25T10:30:00+09:00"
  },
  "message": "返却処理が完了しました"
}
```

### エラーレスポンス

#### 基本形式

```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "ユーザー向けエラーメッセージ",
    "details": []
  }
}
```

#### バリデーションエラー

```json
// Status: 422 Unprocessable Entity
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力内容に誤りがあります",
    "details": [
      {
        "field": "isbn",
        "code": "REQUIRED",
        "message": "ISBNは必須です"
      },
      {
        "field": "title",
        "code": "MAX_LENGTH",
        "message": "タイトルは255文字以内で入力してください"
      }
    ]
  }
}
```

#### 認証エラー

```json
// Status: 401 Unauthorized
{
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "メールアドレスまたはパスワードが正しくありません"
  }
}
```

#### 認可エラー

```json
// Status: 403 Forbidden
{
  "error": {
    "code": "AUTHZ_PERMISSION_DENIED",
    "message": "この操作を実行する権限がありません"
  }
}
```

#### リソース未発見

```json
// Status: 404 Not Found
{
  "error": {
    "code": "RESOURCE_NOT_FOUND",
    "message": "指定されたリソースが見つかりません"
  }
}
```

#### ビジネスルールエラー

```json
// Status: 400 Bad Request
{
  "error": {
    "code": "BUSINESS_BOOK_NOT_AVAILABLE",
    "message": "この書籍は現在貸出中のため予約できません"
  }
}
```

#### サーバーエラー

```json
// Status: 500 Internal Server Error
{
  "error": {
    "code": "SYSTEM_INTERNAL_ERROR",
    "message": "システムエラーが発生しました。しばらく経ってから再度お試しください"
  }
}
```

---

## HTTP ステータスコード

### 成功系（2xx）

| コード | 名称 | 用途 |
|--------|------|------|
| 200 | OK | 取得・更新成功 |
| 201 | Created | 作成成功 |
| 204 | No Content | 削除成功（レスポンスボディなし） |

### クライアントエラー（4xx）

| コード | 名称 | 用途 |
|--------|------|------|
| 400 | Bad Request | リクエスト不正、ビジネスルールエラー |
| 401 | Unauthorized | 認証エラー |
| 403 | Forbidden | 認可エラー（権限なし） |
| 404 | Not Found | リソース未発見 |
| 405 | Method Not Allowed | 許可されていないメソッド |
| 409 | Conflict | 競合（重複など） |
| 422 | Unprocessable Entity | バリデーションエラー |
| 429 | Too Many Requests | レート制限超過 |

### サーバーエラー（5xx）

| コード | 名称 | 用途 |
|--------|------|------|
| 500 | Internal Server Error | サーバー内部エラー |
| 502 | Bad Gateway | 外部サービスエラー |
| 503 | Service Unavailable | サービス一時停止 |
| 504 | Gateway Timeout | 外部サービスタイムアウト |

---

## ページネーション

### リクエストパラメータ

| パラメータ | 型 | デフォルト | 最大値 | 説明 |
|-----------|-----|-----------|--------|------|
| page | integer | 1 | - | ページ番号（1始まり） |
| per_page | integer | 20 | 100 | 1ページあたりの件数 |

### レスポンス形式

```json
{
  "data": [...],
  "meta": {
    "total": 150,        // 総件数
    "per_page": 20,      // 1ページあたりの件数
    "current_page": 1,   // 現在のページ
    "last_page": 8,      // 最終ページ
    "from": 1,           // 開始位置
    "to": 20             // 終了位置
  },
  "links": {
    "first": "/api/v1/books?page=1",
    "last": "/api/v1/books?page=8",
    "prev": null,
    "next": "/api/v1/books?page=2"
  }
}
```

### Laravel 実装

```php
// Controller
public function index(Request $request): JsonResponse
{
    $perPage = min($request->input('per_page', 20), 100);

    $books = BookRecord::query()
        ->orderByDesc('created_at')
        ->paginate($perPage);

    return BookResource::collection($books)->response();
}

// Resource Collection
final class BookCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
```

---

## フィルタリング・ソート

### フィルタリング

```
# 単一条件
GET /api/v1/books?status=available

# 複数条件（AND）
GET /api/v1/books?status=available&author=山田

# 範囲指定
GET /api/v1/books?published_at_from=2024-01-01&published_at_to=2024-12-31

# 部分一致（検索）
GET /api/v1/books?title_like=プログラミング

# 複数値（OR）
GET /api/v1/books?status[]=available&status[]=reserved
```

### ソート

```
# 単一ソート
GET /api/v1/books?sort=created_at&order=desc

# 複数ソート
GET /api/v1/books?sort=author,title&order=asc,asc

# ソート指定形式（代替）
GET /api/v1/books?sort=-created_at,title  # - はDESC
```

### Laravel 実装

```php
public function index(IndexBookRequest $request): JsonResponse
{
    $query = BookRecord::query();

    // フィルタリング
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    if ($request->filled('author')) {
        $query->where('author', 'like', '%' . $request->input('author') . '%');
    }

    if ($request->filled('published_at_from')) {
        $query->where('published_at', '>=', $request->input('published_at_from'));
    }

    // ソート
    $sortField = $request->input('sort', 'created_at');
    $sortOrder = $request->input('order', 'desc');

    $allowedSorts = ['created_at', 'title', 'author', 'published_at'];
    if (in_array($sortField, $allowedSorts, true)) {
        $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
    }

    return BookResource::collection($query->paginate())->response();
}
```

---

## API バージョニング

### バージョニング戦略

**採用方式:** URL パスバージョニング

```
/api/v1/books
/api/v2/books
```

**理由:**
- 明確で分かりやすい
- キャッシュしやすい
- デバッグしやすい

### バージョンアップ方針

| 変更種別 | バージョン | 例 |
|---------|-----------|-----|
| 後方互換のある追加 | 不要 | 新しいフィールド追加 |
| 後方互換のない変更 | 必要 | フィールド削除、型変更 |
| 新機能追加 | 不要 | 新エンドポイント追加 |

### 非推奨化プロセス

1. **告知**: 新バージョンリリースの3ヶ月前に非推奨を告知
2. **警告**: レスポンスヘッダーで非推奨を通知
3. **移行期間**: 最低6ヶ月間は旧バージョンを維持
4. **廃止**: 移行期間終了後に旧バージョンを廃止

```
# 非推奨警告ヘッダー
Deprecation: true
Sunset: Sat, 01 Jul 2025 00:00:00 GMT
Link: </api/v2/books>; rel="successor-version"
```

### Laravel 実装

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::apiResource('books', V1\BookController::class);
});

Route::prefix('v2')->group(function () {
    Route::apiResource('books', V2\BookController::class);
});

// Middleware で非推奨警告
final class DeprecationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_starts_with($request->path(), 'api/v1')) {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Sunset', 'Sat, 01 Jul 2025 00:00:00 GMT');
        }

        return $response;
    }
}
```

---

## レート制限

### 制限値

| エンドポイント | 制限 | 単位 |
|---------------|------|------|
| 一般 API | 60 | リクエスト/分 |
| 認証 API | 5 | リクエスト/分 |
| 検索 API | 30 | リクエスト/分 |
| ファイルアップロード | 10 | リクエスト/分 |

### レスポンスヘッダー

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1703500800
```

### 制限超過時のレスポンス

```json
// Status: 429 Too Many Requests
{
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "リクエスト制限を超えました。しばらく経ってから再度お試しください",
    "retry_after": 60
  }
}
```

### Laravel 実装

```php
// app/Providers/RouteServiceProvider.php
protected function configureRateLimiting(): void
{
    // 一般 API
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip());
    });

    // 認証 API
    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    // 検索 API
    RateLimiter::for('search', function (Request $request) {
        return Limit::perMinute(30)
            ->by($request->user()?->id ?: $request->ip());
    });
}

// routes/api.php
Route::middleware(['throttle:auth'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::apiResource('books', BookController::class);
});
```

---

## 認証

### 認証方式

| 方式 | 用途 | ヘッダー |
|------|------|---------|
| Cookie（Sanctum） | SPA フロントエンド | Cookie: laravel_session |
| Bearer Token | モバイルアプリ / 外部連携 | Authorization: Bearer {token} |

### 認証フロー（SPA）

```
1. GET  /sanctum/csrf-cookie    # CSRF トークン取得
2. POST /api/auth/login         # ログイン（セッション開始）
3. GET  /api/auth/user          # ユーザー情報取得
4. POST /api/auth/logout        # ログアウト
```

### 未認証時のレスポンス

```json
// Status: 401 Unauthorized
{
  "error": {
    "code": "AUTH_UNAUTHENTICATED",
    "message": "認証が必要です"
  }
}
```

---

## OpenAPI（Swagger）仕様

### ドキュメント生成

```bash
# L5-Swagger を使用
composer require darkaonline/l5-swagger

# ドキュメント生成
php artisan l5-swagger:generate
```

### アノテーション例

```php
/**
 * @OA\Get(
 *     path="/api/v1/books",
 *     summary="書籍一覧を取得",
 *     tags={"Books"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="ページ番号",
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="1ページあたりの件数",
 *         @OA\Schema(type="integer", default=20, maximum=100)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="成功",
 *         @OA\JsonContent(ref="#/components/schemas/BookCollection")
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="認証エラー",
 *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
 *     )
 * )
 */
public function index(Request $request): JsonResponse
{
    // ...
}
```

### スキーマ定義

```php
/**
 * @OA\Schema(
 *     schema="Book",
 *     required={"id", "isbn", "title", "author"},
 *     @OA\Property(property="id", type="string", example="01HXYZ123456789ABCDEF"),
 *     @OA\Property(property="isbn", type="string", example="9784123456789"),
 *     @OA\Property(property="title", type="string", example="サンプル書籍"),
 *     @OA\Property(property="author", type="string", example="山田太郎"),
 *     @OA\Property(property="status", type="string", enum={"available", "on_loan", "reserved"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
```

---

## Laravel 実装パターン

### Controller

```php
final class BookController extends Controller
{
    public function __construct(
        private GetBooksHandler $getBooksHandler,
        private GetBookHandler $getBookHandler,
        private CreateBookHandler $createBookHandler,
    ) {}

    /**
     * 書籍一覧を取得
     */
    public function index(IndexBookRequest $request): JsonResponse
    {
        $query = new GetBooksQuery(
            page: $request->input('page', 1),
            perPage: $request->input('per_page', 20),
            status: $request->input('status'),
            sort: $request->input('sort', 'created_at'),
            order: $request->input('order', 'desc'),
        );

        $result = $this->getBooksHandler->handle($query);

        return BookResource::collection($result)->response();
    }

    /**
     * 書籍詳細を取得
     */
    public function show(string $id): JsonResponse
    {
        $query = new GetBookQuery($id);
        $book = $this->getBookHandler->handle($query);

        return response()->json([
            'data' => new BookResource($book),
        ]);
    }

    /**
     * 書籍を作成
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $command = new CreateBookCommand(
            isbn: $request->input('isbn'),
            title: $request->input('title'),
            author: $request->input('author'),
            publisher: $request->input('publisher'),
            publishedAt: $request->input('published_at'),
        );

        $book = $this->createBookHandler->handle($command);

        return response()->json([
            'data' => new BookResource($book),
        ], 201)->header('Location', "/api/v1/books/{$book->id}");
    }
}
```

### FormRequest

```php
final class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['required', 'string', 'size:13', 'unique:books,isbn'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:100'],
            'publisher' => ['nullable', 'string', 'max:100'],
            'published_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.required' => 'ISBNは必須です',
            'isbn.size' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に登録されています',
            'title.required' => 'タイトルは必須です',
            'title.max' => 'タイトルは255文字以内で入力してください',
        ];
    }
}
```

### Resource

```php
final class BookResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'isbn' => $this->resource->isbn,
            'title' => $this->resource->title,
            'author' => $this->resource->author,
            'publisher' => $this->resource->publisher,
            'published_at' => $this->resource->publishedAt?->format('Y-m-d'),
            'status' => $this->resource->status,
            'created_at' => $this->resource->createdAt->toIso8601String(),
            'updated_at' => $this->resource->updatedAt->toIso8601String(),
        ];
    }
}
```

### 例外ハンドラー

```php
// app/Exceptions/Handler.php
public function render($request, Throwable $e): Response
{
    if ($request->expectsJson()) {
        return $this->renderJsonException($request, $e);
    }

    return parent::render($request, $e);
}

private function renderJsonException(Request $request, Throwable $e): JsonResponse
{
    return match (true) {
        $e instanceof ValidationException => response()->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => '入力内容に誤りがあります',
                'details' => $this->formatValidationErrors($e),
            ],
        ], 422),

        $e instanceof AuthenticationException => response()->json([
            'error' => [
                'code' => 'AUTH_UNAUTHENTICATED',
                'message' => '認証が必要です',
            ],
        ], 401),

        $e instanceof AuthorizationException => response()->json([
            'error' => [
                'code' => 'AUTHZ_PERMISSION_DENIED',
                'message' => 'この操作を実行する権限がありません',
            ],
        ], 403),

        $e instanceof ModelNotFoundException => response()->json([
            'error' => [
                'code' => 'RESOURCE_NOT_FOUND',
                'message' => '指定されたリソースが見つかりません',
            ],
        ], 404),

        $e instanceof DomainException => response()->json([
            'error' => [
                'code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
            ],
        ], 400),

        default => response()->json([
            'error' => [
                'code' => 'SYSTEM_INTERNAL_ERROR',
                'message' => 'システムエラーが発生しました',
            ],
        ], 500),
    };
}

private function formatValidationErrors(ValidationException $e): array
{
    $details = [];
    foreach ($e->errors() as $field => $messages) {
        foreach ($messages as $message) {
            $details[] = [
                'field' => $field,
                'message' => $message,
            ];
        }
    }
    return $details;
}
```

---

## チェックリスト

### 設計時

- [ ] URL がリソース指向になっているか
- [ ] HTTP メソッドが適切に使い分けられているか
- [ ] レスポンス形式が統一されているか
- [ ] エラーコードが定義されているか
- [ ] ページネーションが実装されているか
- [ ] 認証・認可が適切に設定されているか

### 実装時

- [ ] FormRequest でバリデーションを実装しているか
- [ ] Resource でレスポンスを整形しているか
- [ ] 例外ハンドラーでエラーレスポンスを統一しているか
- [ ] レート制限が設定されているか
- [ ] OpenAPI ドキュメントが更新されているか

### レビュー時

- [ ] エンドポイントの命名が規約に沿っているか
- [ ] ステータスコードが適切か
- [ ] エラーメッセージがユーザーフレンドリーか
- [ ] 機密情報がレスポンスに含まれていないか
- [ ] N+1 問題が発生していないか

---

## 関連ドキュメント

- [01_ArchitectureDesign.md](./01_ArchitectureDesign.md) - アーキテクチャ設計標準
- [02_CodingStandards.md](./02_CodingStandards.md) - コーディング規約
- [03_SecurityDesign.md](./03_SecurityDesign.md) - セキュリティ設計標準
- [05_LoggingDesign.md](./05_LoggingDesign.md) - ログ設計標準
- [07_ErrorHandling.md](./07_ErrorHandling.md) - エラーハンドリング設計（予定）
