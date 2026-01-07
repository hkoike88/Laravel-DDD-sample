# Research: 蔵書登録機能

**Feature Branch**: `001-book-registration`
**Date**: 2026-01-06

## 既存実装の分析

### 実装済みコンポーネント

| レイヤー | コンポーネント | ステータス | パス |
|---------|--------------|----------|------|
| Domain | Book エンティティ | 実装済み | `backend/packages/Domain/Book/Domain/Model/Book.php` |
| Domain | ISBN Value Object | 実装済み | `backend/packages/Domain/Book/Domain/ValueObjects/ISBN.php` |
| Domain | BookRepositoryInterface | 実装済み | `backend/packages/Domain/Book/Domain/Repositories/BookRepositoryInterface.php` |
| Application | CreateBookCommand | 実装済み | `backend/packages/Domain/Book/Application/UseCases/Commands/CreateBook/` |
| Application | EloquentBookRepository | 実装済み | `backend/packages/Domain/Book/Application/Repositories/EloquentBookRepository.php` |
| Presentation | BookController.store() | 実装済み | `backend/packages/Domain/Book/Presentation/HTTP/Controllers/BookController.php` |
| Presentation | CreateBookRequest | 実装済み | `backend/packages/Domain/Book/Presentation/HTTP/Requests/CreateBookRequest.php` |
| Infrastructure | books テーブル | 実装済み | `backend/database/migrations/2025_12_24_120000_create_books_table.php` |
| Frontend | Book 型定義 | 実装済み | `frontend/src/features/books/types/book.ts` |
| Frontend | 蔵書検索ページ | 実装済み | `frontend/src/features/books/pages/BookSearchPage.tsx` |

### 未実装・要拡張コンポーネント

| コンポーネント | 理由 | 対応 |
|--------------|------|------|
| 登録者・登録日時 | FR-010: 登録操作を実行した職員と登録日時を記録 | マイグレーション追加、エンティティ拡張 |
| ISBN重複チェックAPI | FR-007: リアルタイム重複チェック | 専用エンドポイント追加 |
| 認証ガード | 仕様: 認証済み職員のみアクセス可能 | ミドルウェア追加、authorize() 修正 |
| フロントエンド登録画面 | 完全に新規 | 登録フォーム、バリデーション、API連携 |

## 技術決定

### 1. 登録者の記録方法

**Decision**: `registered_by` カラムを books テーブルに追加し、Staff ULID を記録

**Rationale**:
- Laravel Sanctum で認証済みユーザー（Staff）を取得可能
- 外部キー制約で参照整合性を保証
- 監査要件に対応（いつ・誰が登録したかを追跡可能）

**Alternatives considered**:
- セッションに依存した記録 → 却下: 永続化に不適切
- イベントソーシング → 却下: 現時点ではオーバーエンジニアリング

### 2. ISBN重複チェックのAPI設計

**Decision**: `GET /api/books/check-isbn?isbn={isbn}` エンドポイントを追加

**Rationale**:
- フロンエンドからフォーカス移動時にリアルタイムチェック可能
- 既存の `findByIsbn()` メソッドを活用
- レスポンスは `{ "exists": boolean, "count": number }` 形式

**Alternatives considered**:
- POST リクエスト → 却下: 読み取り専用のためGETが適切
- 登録APIで重複チェック → 却下: リアルタイムUX要件を満たさない

### 3. フロントエンドフォームバリデーション

**Decision**: React Hook Form + Zod でクライアントサイドバリデーション

**Rationale**:
- 既存プロジェクトで採用済みの技術スタック
- バックエンドのバリデーションルールと一貫性を保つ
- リアルタイムバリデーションでUX向上

**Alternatives considered**:
- バックエンドのみでバリデーション → 却下: UX要件（即時フィードバック）を満たさない

### 4. 登録完了後の遷移

**Decision**: 登録完了後、確認画面（登録詳細表示）に遷移

**Rationale**:
- FR-008: 登録完了後、登録内容の確認画面を表示
- User Story 4 の受け入れ基準に合致
- 「続けて登録」ボタンで連続登録をサポート

### 5. バリデーションルールの調整

**Decision**: 仕様に合わせてバリデーションルールを調整

| 項目 | 現状 | 仕様要件 | 対応 |
|------|------|---------|------|
| タイトル最大長 | 500文字 | 200文字（FR-003） | 修正必要 |
| 著者名最大長 | 200文字 | 100文字（FR-004） | 修正必要 |
| 出版社最大長 | 200文字 | 100文字（FR-011） | 修正必要 |
| 出版年範囲 | 1〜現在年+5 | 1000〜現在年+1（FR-012） | 修正必要 |

## 依存関係の確認

### 前提条件

| 依存 | ステータス | 備考 |
|------|----------|------|
| EPIC-001 蔵書検索 | 完了 | Book エンティティ、リポジトリ実装済み |
| 職員認証機能 | 完了 | Laravel Sanctum 実装済み |

### 外部ライブラリ

フロントエンドの既存依存関係で対応可能:
- `@tanstack/react-query`: サーバー状態管理
- `react-hook-form`: フォーム管理
- `zod`: バリデーション
- `axios`: API通信

## リスク・考慮事項

1. **データマイグレーション**: `registered_by` カラム追加時、既存データは NULL を許容
2. **後方互換性**: 既存の登録APIを拡張するため、フロントエンド未対応時も動作継続
3. **パフォーマンス**: ISBN重複チェックAPIは高頻度呼び出しが予想されるため、インデックス活用を確認済み
