# Data Model: 蔵書検索画面

**Feature**: 004-book-search-ui
**Date**: 2025-12-24

## フロントエンド型定義

### Book（蔵書）

APIから取得する蔵書データの型定義。

```typescript
/**
 * 蔵書の貸出状態
 */
type BookStatus = 'available' | 'borrowed' | 'reserved';

/**
 * 蔵書エンティティ
 */
interface Book {
  /** ULID形式の蔵書ID */
  id: string;
  /** 書籍タイトル */
  title: string;
  /** 著者名（nullable） */
  author: string | null;
  /** ISBN（nullable、ハイフンなし） */
  isbn: string | null;
  /** 出版社名（nullable） */
  publisher: string | null;
  /** 出版年（nullable） */
  published_year: number | null;
  /** ジャンル（nullable） */
  genre: string | null;
  /** 貸出状態 */
  status: BookStatus;
}
```

### BookSearchParams（検索パラメータ）

検索フォームの入力値を表す型。

```typescript
/**
 * 蔵書検索パラメータ
 */
interface BookSearchParams {
  /** タイトル検索キーワード（部分一致） */
  title?: string;
  /** 著者名検索キーワード（部分一致） */
  author?: string;
  /** ISBN（完全一致） */
  isbn?: string;
  /** ページ番号（1始まり） */
  page?: number;
  /** 1ページあたりの件数 */
  per_page?: number;
}
```

### PaginationMeta（ページネーション情報）

APIレスポンスのメタ情報。

```typescript
/**
 * ページネーションメタ情報
 */
interface PaginationMeta {
  /** 総件数 */
  total: number;
  /** 現在のページ番号 */
  page: number;
  /** 1ページあたりの件数 */
  per_page: number;
  /** 総ページ数 */
  last_page: number;
}
```

### BookSearchResponse（APIレスポンス）

蔵書検索APIのレスポンス型。

```typescript
/**
 * 蔵書検索APIレスポンス
 */
interface BookSearchResponse {
  /** 検索結果の蔵書リスト */
  data: Book[];
  /** ページネーション情報 */
  meta: PaginationMeta;
}
```

## 状態管理

### 検索状態（useBookSearch hook内部）

```typescript
/**
 * 検索フック戻り値
 */
interface UseBookSearchReturn {
  /** 検索結果 */
  data: BookSearchResponse | undefined;
  /** ローディング中フラグ */
  isLoading: boolean;
  /** フェッチ中フラグ（ページ遷移時） */
  isFetching: boolean;
  /** エラーフラグ */
  isError: boolean;
  /** エラー内容 */
  error: Error | null;
  /** 検索実行関数 */
  search: (params: BookSearchParams) => void;
  /** リトライ関数 */
  refetch: () => void;
}
```

## バリデーションルール

### 検索フォームバリデーション（Zod）

```typescript
const bookSearchSchema = z.object({
  title: z.string().max(255).optional(),
  author: z.string().max(255).optional(),
  isbn: z.string().max(17).optional(),
});
```

## 状態遷移

### BookStatus

```
available（貸出可）
    ↓ 貸出処理
borrowed（貸出中）
    ↓ 返却処理
available（貸出可）

available（貸出可）
    ↓ 予約処理
reserved（予約あり）
    ↓ 貸出処理
borrowed（貸出中）
```

*注: 状態遷移は本機能のスコープ外。表示のみ。*
