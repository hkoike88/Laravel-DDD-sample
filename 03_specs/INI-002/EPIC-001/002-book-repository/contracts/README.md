# Contracts: 蔵書リポジトリ

このディレクトリには 002-book-repository 機能のインターフェース定義を格納します。

## ファイル一覧

| ファイル | 説明 |
|----------|------|
| BookRepositoryInterface.php | 蔵書リポジトリの契約定義 |

## インターフェース概要

### BookRepositoryInterface

蔵書の永続化と取得を抽象化するインターフェース。

#### メソッド一覧

| メソッド | 説明 | 戻り値 |
|----------|------|--------|
| find(BookId) | ID で蔵書を取得（必須） | Book |
| findOrNull(BookId) | ID で蔵書を取得（任意） | Book\|null |
| findByIsbn(ISBN) | ISBN で蔵書を検索 | list\<Book\> |
| search(BookSearchCriteria) | 条件で蔵書を検索 | BookCollection |
| count(BookSearchCriteria) | 条件に一致する件数を取得 | int |
| save(Book) | 蔵書を保存 | void |
| delete(BookId) | 蔵書を削除 | void |

## 実装クラス

- `EloquentBookRepository`: Eloquent ORM を使用した実装（Application 層）

## 関連 DTO

- `BookSearchCriteria`: 検索条件を表す DTO
- `BookCollection`: 検索結果コレクション

## 使用例

```php
// DI により注入されたリポジトリを使用
public function __construct(
    private BookRepositoryInterface $bookRepository,
) {}

// ID で蔵書を取得
$book = $this->bookRepository->find(BookId::fromString($id));

// 条件検索
$criteria = new BookSearchCriteria(
    title: 'ドメイン駆動',
    status: BookStatus::available(),
    pageSize: 20,
);
$collection = $this->bookRepository->search($criteria);

// 蔵書を保存
$this->bookRepository->save($book);
```
