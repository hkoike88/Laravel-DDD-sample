# Quickstart: 蔵書エンティティ・Value Object

**Date**: 2025-12-24
**Feature**: 001-book-entity-design

---

## 概要

このドキュメントでは、蔵書ドメインモデルの使用方法を説明します。

---

## 前提条件

- PHP 8.3 以上
- Laravel 11.x
- composer require symfony/uid

---

## 基本的な使い方

### 1. 蔵書の作成

```php
use Packages\Domain\Book\Domain\Model\Book;
use Packages\Domain\Book\Domain\ValueObjects\BookId;
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

// 新規蔵書を作成
$book = Book::create(
    id: BookId::generate(),
    title: 'ドメイン駆動設計入門',
    author: 'エリック・エヴァンス',
    isbn: ISBN::fromString('978-4-7981-2196-3'),
    publisher: '翔泳社',
    publishedYear: 2011,
    genre: '技術書'
);

// ISBN なしの蔵書も作成可能
$oldBook = Book::create(
    id: BookId::generate(),
    title: '古典文学全集',
    author: '不詳',
);
```

### 2. 蔵書の状態確認

```php
// 利用可能か確認
if ($book->isAvailable()) {
    echo "この本は貸出可能です";
}

// 現在のステータスを取得
$status = $book->status(); // BookStatus インスタンス
echo $status->value(); // "available", "borrowed", "reserved"
```

### 3. 貸出・返却・予約

```php
// 貸出
$book->borrow();
echo $book->status()->isBorrowed(); // true

// 返却
$book->return();
echo $book->status()->isAvailable(); // true

// 予約
$book->reserve();
echo $book->status()->isReserved(); // true

// 予約者への貸出
$book->lendToReserver();
echo $book->status()->isBorrowed(); // true

// 予約キャンセル
$book->cancelReservation();
echo $book->status()->isAvailable(); // true
```

### 4. 不正な操作のエラーハンドリング

```php
use Packages\Domain\Book\Domain\Exceptions\InvalidBookStatusTransitionException;

try {
    // 既に貸出中の本を再度貸出しようとする
    $book->borrow();
    $book->borrow(); // 例外がスローされる
} catch (InvalidBookStatusTransitionException $e) {
    echo "エラー: " . $e->getMessage();
    // "borrowed 状態から borrowed への遷移は許可されていません"
}
```

---

## Value Object の使い方

### BookId

```php
use Packages\Domain\Book\Domain\ValueObjects\BookId;

// 新規 ID を生成
$id = BookId::generate();
echo $id->value(); // "01HQXGZG3QZJXVWB5XPNKXYZ01"

// 文字列から復元
$id = BookId::fromString('01HQXGZG3QZJXVWB5XPNKXYZ01');

// 等価性の比較
$id1 = BookId::generate();
$id2 = BookId::fromString($id1->value());
echo $id1->equals($id2); // true
```

### ISBN

```php
use Packages\Domain\Book\Domain\ValueObjects\ISBN;
use Packages\Domain\Book\Domain\Exceptions\InvalidISBNException;

// ISBN-13 を作成
$isbn13 = ISBN::fromString('978-4-7981-2196-3');
echo $isbn13->value();      // "9784798121963"
echo $isbn13->formatted();  // "978-4-7981-2196-3"
echo $isbn13->isISBN13();   // true

// ISBN-10 を作成
$isbn10 = ISBN::fromString('1-5586-0832-X');
echo $isbn10->value();      // "155860832X"
echo $isbn10->isISBN10();   // true

// 不正な ISBN
try {
    $invalid = ISBN::fromString('1234567890123');
} catch (InvalidISBNException $e) {
    echo "不正なISBN: " . $e->getInvalidValue();
}
```

### BookStatus

```php
use Packages\Domain\Book\Domain\ValueObjects\BookStatus;

// ファクトリメソッドで生成
$available = BookStatus::available();
$borrowed = BookStatus::borrowed();
$reserved = BookStatus::reserved();

// 文字列から復元
$status = BookStatus::from('borrowed');

// 状態遷移の可否を確認
if ($status->canReturn()) {
    echo "返却可能です";
}

// 状態の比較
echo $available->equals(BookStatus::available()); // true
```

---

## テストの実行

```bash
# 全 Unit テストを実行
php artisan test --testsuite=Unit

# 蔵書ドメインのテストのみ実行
php artisan test tests/Unit/Domain/Book

# カバレッジレポート付き
php artisan test tests/Unit/Domain/Book --coverage
```

---

## ディレクトリ構成

```
backend/packages/Domain/Book/
├── Domain/
│   ├── Model/
│   │   └── Book.php
│   ├── ValueObjects/
│   │   ├── BookId.php
│   │   ├── ISBN.php
│   │   └── BookStatus.php
│   ├── Repositories/
│   │   └── BookRepositoryInterface.php
│   └── Exceptions/
│       ├── InvalidISBNException.php
│       ├── InvalidBookStatusTransitionException.php
│       └── EmptyBookTitleException.php
└── Application/
    └── Providers/
        └── BookServiceProvider.php
```

---

## よくあるエラーと対処法

### EmptyBookTitleException

**原因**: タイトルが空または空白のみ

```php
// NG
Book::create(id: $id, title: '');
Book::create(id: $id, title: '   ');

// OK
Book::create(id: $id, title: 'サンプル書籍');
```

### InvalidISBNException

**原因**: ISBN の形式が不正、またはチェックディジットが一致しない

```php
// NG - 桁数不足
ISBN::fromString('123');

// NG - チェックディジット不正
ISBN::fromString('978-4-7981-2196-0'); // 正しくは -3

// OK
ISBN::fromString('978-4-7981-2196-3');
ISBN::fromString('9784798121963'); // ハイフンなしも可
```

### InvalidBookStatusTransitionException

**原因**: 現在の状態から許可されていない操作を実行しようとした

```php
// NG - available から return は不可
$book = Book::create(...); // status = available
$book->return(); // 例外

// OK - borrowed から return は可
$book->borrow();
$book->return();
```

---

## 次のステップ

1. **リポジトリ実装**: `EloquentBookRepository` を作成してデータベース永続化
2. **UseCase 実装**: 貸出・返却・予約のユースケースハンドラを作成
3. **API 実装**: Controller と FormRequest を作成して REST API を公開
