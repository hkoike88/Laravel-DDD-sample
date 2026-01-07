# Quickstart: 蔵書登録機能

**Feature Branch**: `001-book-registration`
**Date**: 2026-01-06

## 概要

このドキュメントは、蔵書登録機能の実装を開始するための手順とガイドラインを提供します。

## 前提条件

- [ ] Docker 環境が起動していること
- [ ] EPIC-001（蔵書検索）が完了していること
- [ ] 職員認証機能が動作していること

## 開発環境のセットアップ

```bash
# プロジェクトルートで Docker を起動
docker compose up -d

# バックエンドのマイグレーション実行
docker compose exec backend php artisan migrate

# フロントエンドの依存関係インストール
docker compose exec frontend npm install
```

## 実装順序

### Phase 1: バックエンド基盤（優先度: P1）

1. **マイグレーション追加**
   - `registered_by`, `registered_at` カラムを books テーブルに追加
   - 外部キー制約の設定

2. **CreateBookRequest の修正**
   - バリデーションルールを仕様に合わせて調整
   - 認証チェック（authorize メソッド）の修正

3. **CreateBookHandler の拡張**
   - 登録者ID と登録日時の記録
   - 認証ユーザーの取得

4. **BookResource の更新**
   - `registered_by`, `registered_at` フィールドの追加

### Phase 2: ISBN重複チェックAPI（優先度: P2）

5. **CheckIsbnRequest の作成**
   - ISBN バリデーション
   - 認証チェック

6. **BookController.checkIsbn() の実装**
   - 既存の `findByIsbn()` メソッドを活用
   - レスポンス形式の定義

7. **ルーティング追加**
   - `GET /api/books/check-isbn`

### Phase 3: フロントエンド登録画面（優先度: P1-P2）

8. **型定義の追加**
   - `CreateBookInput` 型
   - `IsbnCheckResponse` 型

9. **API サービスの実装**
   - `createBook()` 関数
   - `checkIsbnDuplicate()` 関数

10. **登録フォームコンポーネント**
    - React Hook Form + Zod によるバリデーション
    - ISBN リアルタイムチェック（debounce 付き）
    - エラーハンドリング

11. **登録確認画面**
    - 登録完了後の詳細表示
    - 「続けて登録」ボタン

12. **ルーティング設定**
    - `/books/new` - 登録画面
    - `/books/:id/complete` - 登録完了確認画面

### Phase 4: テスト（全優先度並行）

13. **バックエンドテスト**
    - CreateBookHandler のユニットテスト
    - CheckIsbn エンドポイントのフィーチャーテスト
    - 認証ガードのテスト

14. **フロントエンドテスト**
    - フォームバリデーションのテスト
    - ISBN重複チェックのテスト
    - API 連携のモックテスト

## ディレクトリ構造

### バックエンド

```
backend/
├── database/migrations/
│   └── 2026_01_06_000000_add_registration_columns_to_books_table.php  # NEW
└── packages/Domain/Book/
    ├── Application/
    │   └── UseCases/Commands/CreateBook/
    │       ├── CreateBookCommand.php       # MODIFY
    │       └── CreateBookHandler.php       # MODIFY
    ├── Domain/Model/
    │   └── Book.php                        # MODIFY (属性追加)
    ├── Infrastructure/EloquentModels/
    │   └── BookRecord.php                  # MODIFY
    └── Presentation/HTTP/
        ├── Controllers/
        │   └── BookController.php          # MODIFY (checkIsbn 追加)
        ├── Requests/
        │   ├── CreateBookRequest.php       # MODIFY
        │   └── CheckIsbnRequest.php        # NEW
        ├── Resources/
        │   └── BookResource.php            # MODIFY
        └── routes.php                      # MODIFY
```

### フロントエンド

```
frontend/src/features/books/
├── api/
│   └── bookApi.ts                     # MODIFY
├── components/
│   ├── BookRegistrationForm.tsx       # NEW
│   ├── BookRegistrationForm.test.tsx  # NEW
│   └── IsbnDuplicateWarning.tsx       # NEW
├── hooks/
│   ├── useBookRegistration.ts         # NEW
│   └── useIsbnCheck.ts                # NEW
├── pages/
│   ├── BookRegistrationPage.tsx       # NEW
│   ├── BookRegistrationPage.test.tsx  # NEW
│   ├── BookCompletePage.tsx           # NEW
│   └── BookCompletePage.test.tsx      # NEW
├── schemas/
│   └── bookRegistration.ts            # NEW (Zod スキーマ)
└── types/
    └── book.ts                        # MODIFY
```

## API エンドポイント

| メソッド | パス | 説明 | 認証 |
|---------|------|------|------|
| POST | `/api/books` | 蔵書登録 | 必須 |
| GET | `/api/books/check-isbn` | ISBN重複チェック | 必須 |
| GET | `/api/books/{id}` | 蔵書詳細取得 | 不要 |

## テスト実行

```bash
# バックエンドテスト
docker compose exec backend ./vendor/bin/pest

# フロントエンドテスト
docker compose exec frontend npm test

# 特定のテストファイルのみ実行
docker compose exec backend ./vendor/bin/pest --filter="CreateBook"
docker compose exec frontend npm test -- BookRegistration
```

## 確認ポイント

### 機能確認

- [ ] タイトル必須バリデーションが動作する
- [ ] ISBN形式バリデーションが動作する
- [ ] ISBN重複時に警告が表示される
- [ ] 登録完了後、確認画面に遷移する
- [ ] 認証なしでアクセスすると401エラーになる

### 非機能確認

- [ ] 登録操作が1分以内に完了できる（SC-001）
- [ ] 登録した図書が3秒以内に検索結果に表示される（SC-002）
