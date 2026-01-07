# Research: 蔵書検索画面

**Feature**: 004-book-search-ui
**Date**: 2025-12-24

## 技術スタック確認

### Decision: React + TanStack Query による実装

**Rationale**:
- プロジェクトに既に TanStack Query 5.x がインストール済み
- サーバー状態管理（検索結果のキャッシュ、ローディング状態、エラー状態）に最適
- React Hook Form + Zod でフォームバリデーションを実装

**Alternatives considered**:
- Zustand のみ: グローバル状態管理には適しているが、サーバー状態管理には TanStack Query の方が適切
- SWR: 機能的には同等だが、既に TanStack Query がインストール済み

## API連携

### Decision: 既存の蔵書検索API（GET /api/books）を利用

**Rationale**:
- 003-book-search-api で実装済みのエンドポイントを使用
- クエリパラメータ: title, author, isbn, page, per_page
- レスポンス形式: `{ data: Book[], meta: { total, page, per_page, last_page } }`

**API Response型**:
```typescript
interface Book {
  id: string;
  title: string;
  author: string | null;
  isbn: string | null;
  publisher: string | null;
  published_year: number | null;
  genre: string | null;
  status: 'available' | 'borrowed' | 'reserved';
}

interface BookSearchResponse {
  data: Book[];
  meta: {
    total: number;
    page: number;
    per_page: number;
    last_page: number;
  };
}
```

## コンポーネント設計

### Decision: Feature-based構造 + 責務分離

**Rationale**:
- `BookSearchPage`: ページ全体のレイアウトと状態管理
- `BookSearchForm`: 検索フォーム（React Hook Form使用）
- `BookSearchResults`: 検索結果テーブル表示
- `BookStatusBadge`: 状態バッジ（貸出可/貸出中/予約あり）
- `Pagination`: ページネーション

**Alternatives considered**:
- 単一コンポーネント: 再利用性とテスタビリティが低下するため却下
- Atomic Design: 今回の規模では過剰なため却下

## スタイリング

### Decision: Tailwind CSS

**Rationale**:
- プロジェクトに既にTailwind CSS 3.xがインストール済み
- ユーティリティファーストでラピッドプロトタイピングに適している
- 状態バッジの色分けはTailwindのカラーパレットを使用

**色定義**:
- 貸出可（available）: `bg-green-100 text-green-800`
- 貸出中（borrowed）: `bg-red-100 text-red-800`
- 予約あり（reserved）: `bg-yellow-100 text-yellow-800`

## エラーハンドリング

### Decision: TanStack Query のエラー状態 + トースト通知

**Rationale**:
- TanStack Query の `isError` と `error` 状態を活用
- ネットワークエラー時はユーザーフレンドリーなメッセージを表示
- リトライボタンで再検索を促す

## ローディング表示

### Decision: スケルトンUI + スピナー

**Rationale**:
- 初回読み込み時はスケルトンUIを表示
- ページ遷移時はスピナーを表示
- TanStack Query の `isLoading` と `isFetching` を使い分け

## 未解決事項

なし（すべての技術的な不明点は解決済み）
