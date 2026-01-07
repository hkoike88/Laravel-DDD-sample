# Research: 職員エンティティの設計

**Branch**: `001-staff-entity-design` | **Date**: 2025-12-25

## 1. ULID 生成ライブラリ

**Decision**: symfony/uid を使用

**Rationale**:
- プロジェクトで既に採用済み（Book ドメインで実績あり）
- Symfony 公式パッケージで長期サポートが期待できる
- Laravel との親和性が高く、シリアライズ・デシリアライズが容易
- タイムスタンプソート可能な ULID を簡単に生成可能

**Alternatives considered**:
- `robinvdvleuten/ulid`: シンプルだが機能が限定的
- `tuupola/ulid`: 依存が少ないが採用実績なし
- PHP 8.2 native random_bytes + 手動生成: 車輪の再発明

## 2. パスワードハッシュ化

**Decision**: Laravel の `Hash` ファサードを Infrastructure 層で使用、bcrypt アルゴリズム

**Rationale**:
- Laravel 標準機能で追加依存なし
- bcrypt は OWASP 推奨のパスワードハッシュアルゴリズム
- コスト係数（デフォルト 12）は現在のハードウェアで 0.1〜0.5 秒の検証時間
- Password 値オブジェクト自体は Laravel に依存しない設計

**Alternatives considered**:
- Argon2id: PHP 7.2+ でサポート、Laravel もサポート。メモリ使用量を調整可能だが、bcrypt の実績と互換性を優先
- scrypt: Laravel 非標準、追加ライブラリが必要

**Implementation Pattern**:
```
Domain層: Password値オブジェクト（ハッシュ済み文字列を保持、検証ロジックなし）
Infrastructure層: パスワードハッシュ化サービス（Laravel Hash使用）
Application層: 登録時にハッシュ化してからエンティティ生成
```

## 3. メールアドレス検証

**Decision**: PHP の `filter_var()` と `FILTER_VALIDATE_EMAIL` を使用

**Rationale**:
- PHP 標準機能で外部依存なし
- RFC 5322 に準拠した妥当な検証
- 過度に厳格な検証は正当なメールアドレスを拒否するリスクがある
- 小文字正規化は `mb_strtolower()` で実現

**Alternatives considered**:
- `egulias/email-validator`: より厳密な RFC 準拠だが、実用上 `filter_var` で十分
- 正規表現: 保守性が低く、エッジケースで問題が発生しやすい

## 4. アカウントロック状態管理

**Decision**: Staff エンティティ内に状態管理メソッドを実装

**Rationale**:
- ロック状態は職員の属性であり、エンティティに含めるのが自然
- ロック閾値は認証ユースケース側で判断（本エンティティはロック操作のみ提供）
- 状態遷移ロジックをドメイン層に集約することでビジネスルールが明確化

**Implementation Methods**:
- `lock(): void` - アカウントをロック（ロック日時を記録）
- `unlock(): void` - ロック解除（失敗回数をリセット）
- `incrementFailedLoginAttempts(): void` - 失敗回数を増加
- `resetFailedLoginAttempts(): void` - 失敗回数をリセット
- `isLocked(): bool` - ロック状態を判定

## 5. リポジトリパターン

**Decision**: 既存の Book ドメインパターンを踏襲

**Rationale**:
- プロジェクト全体で一貫したアーキテクチャ
- チームメンバーの学習コスト削減
- テスト容易性の確保（モック可能なインターフェース）

**Repository Methods**:
- `find(StaffId $id): Staff` - ID 検索（見つからない場合は例外）
- `findOrNull(StaffId $id): ?Staff` - ID 検索（見つからない場合は null）
- `findByEmail(Email $email): ?Staff` - メール検索
- `save(Staff $staff): void` - 保存（新規・更新）
- `delete(StaffId $id): void` - 削除

## 6. テスト戦略

**Decision**: Pest フレームワークを使用、Unit テスト中心

**Rationale**:
- プロジェクト標準テストフレームワーク
- Domain 層は純粋な PHP なので高速な Unit テストが可能
- Repository の Integration テストは Feature テストで実施

**Test Coverage Targets**:
- Domain Model: 90%+
- Value Objects: 95%+（すべてのバリデーションケース）
- Repository: 80%+（主要操作）

## 7. 例外設計

**Decision**: ドメイン固有の例外クラスを定義

**Rationale**:
- 例外の種類を明確にすることでエラーハンドリングが容易
- Presentation 層での適切な HTTP ステータスコードマッピングが可能
- ドメイン層の独立性を保持

**Exception Classes**:
- `InvalidEmailException` - メール形式エラー（400 Bad Request）
- `InvalidPasswordException` - パスワード制約エラー（400 Bad Request）
- `StaffNotFoundException` - 職員未検出（404 Not Found）
- `DuplicateEmailException` - メール重複（409 Conflict）

## 8. マイグレーション設計

**Decision**: 標準的な Laravel マイグレーション

**Rationale**:
- プロジェクト標準に準拠
- インデックス設計で検索性能を確保

**Indexes**:
- `id` (PRIMARY KEY): ULID、主キー検索用
- `email` (UNIQUE): メール検索・重複チェック用
- `is_locked` + `locked_at` (INDEX): ロック状態フィルタリング用（オプション、後続の管理機能で必要な場合）

## まとめ

すべての技術選定が完了し、NEEDS CLARIFICATION は解消されました。既存の Book ドメインパターンを踏襲することで、一貫性のある実装が可能です。
