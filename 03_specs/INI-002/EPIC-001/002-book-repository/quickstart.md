# Quickstart: 蔵書リポジトリ

**Date**: 2025-12-24
**Feature**: 002-book-repository

---

## 概要

このドキュメントでは、蔵書リポジトリの使用方法を説明します。

---

## 前提条件

- PHP 8.3 以上
- Laravel 12.x
- MySQL 8.0
- 001-book-entity-design が実装済み

---

## 基本的な使い方

### 1. 蔵書の保存

```php
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Packages\Domain\Book\Domain\Repositories\BookRepositoryInterface;

class RegisterBookHandler
{
    public function __construct(
        private BookRepositoryInterface $bookRepository,
    ) {}

    public function handle(RegisterBookCommand $command): void
    {
        // 新規蔵書を作成
        $book = Book::create(
            id: BookId::generate(),
            title: $command->title,
            author: $command->author,
            isbn: $command->isbn !== null
                ? ISBN::fromString($command->isbn)
                : null,
            publisher: $command->publisher,
            publishedYear: $command->publishedYear,
            genre: $command->genre,
        );

        // 蔵書を保存
        $this->bookRepository->save($book);
    }
}
```

### 2. ID による蔵書の取得

```php
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\Exceptions\BookNotFoundException;

// 蔵書を取得（存在しない場合は例外）
try {
    $book = $this->bookRepository->find(
        BookId::fromString('01HQXGZG3QZJXVWB5XPNKXYZ01')
    );
    echo "タイトル: " . $book->title();
} catch (BookNotFoundException $e) {
    echo "蔵書が見つかりません";
}

// 蔵書を取得（存在しない場合は null）
$book = $this->bookRepository->findOrNull(
    BookId::fromString('01HQXGZG3QZJXVWB5XPNKXYZ01')
);

if ($book !== null) {
    echo "タイトル: " . $book->title();
} else {
    echo "蔵書が見つかりません";
}
```

### 3. ISBN による蔵書の検索

```php
use Packages\Domain\Book\Domain\ValueObjects\ISBN;

// 同一 ISBN を持つ蔵書を全て取得（複本対応）
$isbn = ISBN::fromString('978-4-7981-2196-3');
$books = $this->bookRepository->findByIsbn($isbn);

echo count($books) . " 冊の蔵書が見つかりました\n";

foreach ($books as $book) {
    echo "- " . $book->title() . " (ID: " . $book->id()->value() . ")\n";
}
```

### 4. 条件による蔵書の検索

```php
use Packages\Domain\Book\Application\DTO\BookSearchCriteria;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

// 検索条件を作成
$criteria = new BookSearchCriteria(
    title: 'ドメイン駆動',      // タイトル部分一致
    author: null,               // 著者（指定なし）
    publisher: null,            // 出版社（指定なし）
    genre: '技術書',            // ジャンル完全一致
    status: BookStatus::available(),  // ステータス完全一致
    publishedYearFrom: 2010,    // 出版年（From）
    publishedYearTo: 2024,      // 出版年（To）
    page: 1,                    // ページ番号
    pageSize: 20,               // ページサイズ
    sortField: 'title',         // ソートフィールド
    sortDirection: 'asc',       // ソート方向
);

// 検索実行
$collection = $this->bookRepository->search($criteria);

// 結果を表示
echo "検索結果: {$collection->totalCount} 件\n";
echo "ページ: {$collection->currentPage} / {$collection->totalPages}\n";

foreach ($collection->items as $book) {
    echo "- {$book->title()} ({$book->publishedYear()}年)\n";
}

// 次のページがあるか確認
if ($collection->hasNextPage()) {
    echo "次のページがあります";
}
```

### 5. 蔵書の更新

```php
// 蔵書を取得
$book = $this->bookRepository->find($bookId);

// 状態を変更（貸出）
$book->borrow();

// 変更を保存
$this->bookRepository->save($book);
```

### 6. 蔵書の削除

```php
// 蔵書を削除
$this->bookRepository->delete($bookId);

// 存在しない ID を指定してもエラーにならない（冪等性）
$this->bookRepository->delete(BookId::fromString('nonexistent'));
```

### 7. 件数の取得

```php
// 利用可能な蔵書の件数を取得
$criteria = new BookSearchCriteria(
    status: BookStatus::available(),
);
$count = $this->bookRepository->count($criteria);

echo "利用可能な蔵書: {$count} 件";
```

---

## 検索条件の詳細

### BookSearchCriteria のパラメータ

| パラメータ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| title | ?string | null | タイトル（部分一致） |
| author | ?string | null | 著者（部分一致） |
| publisher | ?string | null | 出版社（部分一致） |
| genre | ?string | null | ジャンル（完全一致） |
| status | ?BookStatus | null | ステータス（完全一致） |
| publishedYearFrom | ?int | null | 出版年（From） |
| publishedYearTo | ?int | null | 出版年（To） |
| page | int | 1 | ページ番号（1始まり） |
| pageSize | int | 20 | ページサイズ（1〜100） |
| sortField | string | 'title' | ソートフィールド |
| sortDirection | string | 'asc' | ソート方向（asc/desc） |

### ソート可能なフィールド

- `title`: タイトル
- `author`: 著者
- `published_year`: 出版年
- `created_at`: 登録日時

---

## エラーハンドリング

### BookNotFoundException

```php
use Packages\Domain\Book\Domain\Exceptions\BookNotFoundException;

try {
    $book = $this->bookRepository->find($bookId);
} catch (BookNotFoundException $e) {
    // 蔵書が見つからない場合の処理
    Log::warning($e->getMessage());
    throw new NotFoundHttpException('蔵書が見つかりません');
}
```

### InvalidArgumentException（検索条件）

```php
use InvalidArgumentException;

try {
    $criteria = new BookSearchCriteria(
        pageSize: 200,  // 最大100を超えている
    );
} catch (InvalidArgumentException $e) {
    // バリデーションエラー
    echo $e->getMessage();
    // "ページサイズは1〜100の範囲である必要があります"
}
```

---

## テストの実行

```bash
# 統合テストを実行
php artisan test tests/Integration/Domain/Book/Repositories/

# 全テストを実行
php artisan test tests/Unit/Domain/Book tests/Integration/Domain/Book

# カバレッジレポート付き
php artisan test tests/Integration/Domain/Book --coverage
```

---

## ディレクトリ構成

```
backend/packages/Domain/Book/
├── Domain/
│   ├── Model/
│   │   └── Book.php                    # 蔵書エンティティ
│   ├── ValueObjects/
│   │   ├── BookId.php
│   │   ├── ISBN.php
│   │   └── BookStatus.php
│   ├── Repositories/
│   │   └── BookRepositoryInterface.php # リポジトリインターフェース
│   └── Exceptions/
│       ├── BookNotFoundException.php   # 新規
│       ├── InvalidISBNException.php
│       ├── InvalidBookStatusTransitionException.php
│       └── EmptyBookTitleException.php
├── Application/
│   ├── DTO/
│   │   ├── BookSearchCriteria.php      # 検索条件
│   │   └── BookCollection.php          # 検索結果
│   ├── Repositories/
│   │   └── EloquentBookRepository.php  # リポジトリ実装
│   └── Providers/
│       └── BookServiceProvider.php
└── Infrastructure/
    └── EloquentModels/
        └── BookRecord.php              # Eloquent モデル
```

---

## よくあるエラーと対処法

### ISBN の検索で結果が空

**原因**: 検索対象の ISBN がデータベースと異なる形式

```php
// NG - ハイフンあり形式で保存されている場合
$isbn = ISBN::fromString('978-4-7981-2196-3');

// OK - ISBN は内部で正規化されるため、どちらでも同じ結果
$isbn = ISBN::fromString('978-4-7981-2196-3');
$isbn = ISBN::fromString('9784798121963');
// どちらも value() は '9784798121963' を返す
```

### ページネーションで空の結果

**原因**: ページ番号が総ページ数を超えている

```php
// 総ページ数を確認
$collection = $this->bookRepository->search($criteria);
if ($criteria->page > $collection->totalPages) {
    // ページ番号が無効
}
```

### 検索結果が期待と異なる

**原因**: 検索条件が AND で結合されている

```php
// タイトルに「ドメイン」を含み、かつ著者が「エヴァンス」の蔵書
$criteria = new BookSearchCriteria(
    title: 'ドメイン',
    author: 'エヴァンス',
);
// 両方の条件を満たす蔵書のみが返される
```

---

## 次のステップ

1. **UseCase 実装**: 蔵書登録、検索、更新のユースケースを作成
2. **API 実装**: Controller と FormRequest を作成して REST API を公開
3. **貸出機能**: 003-lending-feature で貸出・返却機能を実装
