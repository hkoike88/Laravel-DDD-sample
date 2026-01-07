# Research: セキュリティ対策準備

**Feature**: 001-security-preparation
**Date**: 2026-01-06

## 調査結果サマリー

既存のプロジェクト構成とセキュリティ標準ドキュメントを調査し、実装方針を確定した。

---

## 1. 既存実装の確認

### 1.1 セッション管理

**Decision**: 既存の `config/session.php` はセキュリティ要件を**部分的に満たしている**

**現状の設定**:
- `driver`: database（要件を満たす）
- `lifetime`: 30分（アイドルタイムアウト要件を満たす）
- `encrypt`: true（セッションデータ暗号化要件を満たす）
- `secure`: true（Cookie Secure 属性要件を満たす）
- `http_only`: true（Cookie HttpOnly 属性要件を満たす）
- `same_site`: lax（Cookie SameSite 属性要件を満たす）

**未実装の要件**:
- 絶対タイムアウト（8時間）: カスタムミドルウェアが必要
- 同時ログイン制御: 新規実装が必要
- セッション開始時刻の記録: カスタム実装が必要

### 1.2 パスワードハッシュ

**Decision**: Laravel デフォルトの bcrypt を使用し、cost を 12 に設定

**Rationale**:
- Laravel 11 のデフォルト hashing driver は bcrypt
- `config/hashing.php` が存在しないため、新規作成が必要
- bcrypt cost=12 は NIST SP 800-63B 準拠

### 1.3 Staff エンティティ

**Decision**: 既存の Staff エンティティを活用

**現状**:
- `packages/Domain/Staff/Domain/Model/Staff.php` に実装済み
- アカウントロック機能（`isLocked`, `failedLoginAttempts`, `lockedAt`）は実装済み
- パスワード履歴機能は未実装

### 1.4 CI/CD

**Decision**: 既存の `.github/workflows/ci.yml` を拡張してセキュリティスキャンを追加

**現状**:
- PHPStan、Pint、Pest テストが実行されている
- composer audit、npm audit は未実装

---

## 2. 技術選定

### 2.1 パスワードポリシー

**Decision**: Laravel 標準の `Password` バリデーションルールを使用

**Rationale**:
- `Password::min(12)->letters()->mixedCase()->numbers()->symbols()->uncompromised()` で全要件を満たせる
- `uncompromised()` は Have I Been Pwned API を内部で使用
- カスタム実装不要で Laravel エコシステムに準拠

**Alternatives Considered**:
- カスタムバリデータ: Laravel 標準で十分なため却下

### 2.2 パスワード履歴

**Decision**: `password_histories` テーブルを新規作成し、ドメインサービスで管理

**Rationale**:
- Staff エンティティに直接持たせると複雑化する
- 5世代分の履歴を保持し、パスワード変更時にチェック
- セキュリティ標準ドキュメント（01_PasswordPolicy.md）の設計に準拠

### 2.3 絶対タイムアウト

**Decision**: カスタムミドルウェア `AbsoluteSessionTimeout` を作成

**Rationale**:
- Laravel 標準には絶対タイムアウト機能がない
- セッション開始時刻を記録し、8時間経過で強制ログアウト
- セキュリティ標準ドキュメント（02_SessionManagement.md）の設計に準拠

### 2.4 同時ログイン制御

**Decision**: `sessions` テーブルを活用し、ドメインサービスで制御

**Rationale**:
- Laravel の database session driver は `sessions` テーブルに `user_id` を記録
- ログイン時にアクティブセッション数をチェック
- 上限超過時は最古のセッションを削除
- セキュリティ標準ドキュメント（02_SessionManagement.md）の設計に準拠

### 2.5 セキュリティログ

**Decision**: Laravel の logging 機能を活用し、`security` チャンネルを追加

**Rationale**:
- `config/logging.php` に security チャンネルを追加
- 専用ログファイル `storage/logs/security.log` に出力
- 既存のログ設計標準（04_LoggingDesign.md）に準拠

### 2.6 セキュリティスキャン

**Decision**: GitHub Actions に `security.yml` を追加

**Rationale**:
- 既存の ci.yml を拡張するより、責務を分離したほうが保守しやすい
- composer audit、npm audit を実行
- Critical/High 検出時にパイプライン失敗

---

## 3. 実装アプローチ

### 3.1 バックエンド構成

DDD アーキテクチャに従い、以下の構成で実装:

```
packages/Domain/Staff/
├── Domain/
│   ├── Model/
│   │   └── PasswordHistory.php       # 新規: パスワード履歴エンティティ
│   ├── Services/
│   │   ├── PasswordHistoryService.php # 新規: パスワード履歴チェック
│   │   └── SessionManagerService.php  # 新規: 同時ログイン制御
│   └── Repositories/
│       └── PasswordHistoryRepositoryInterface.php # 新規
├── Application/
│   ├── Repositories/
│   │   └── PasswordHistoryRepository.php # 新規
│   └── UseCases/
│       └── ChangePassword/            # 新規: パスワード変更ユースケース
└── Infrastructure/
    └── EloquentModels/
        └── EloquentPasswordHistory.php # 新規
```

### 3.2 ミドルウェア構成

```
app/Http/Middleware/
├── AbsoluteSessionTimeout.php  # 新規: 絶対タイムアウト
└── SessionManager.php          # 新規: 同時ログイン制御
```

### 3.3 フロントエンド構成

セッション管理UI:

```
frontend/src/features/auth/
├── components/
│   └── SessionList.tsx          # 新規: セッション一覧表示
├── hooks/
│   └── useSessions.ts           # 新規: セッション管理フック
└── services/
    └── sessionApi.ts            # 新規: セッション管理API
```

---

## 4. 依存関係

### 4.1 既存機能との依存

| 依存元 | 依存先 | 関係 |
|--------|--------|------|
| パスワードポリシー | Staff エンティティ | パスワード変更時に適用 |
| パスワード履歴 | Staff エンティティ | 職員IDで関連付け |
| セッション管理 | 認証機能 (EPIC-001) | ログイン処理に統合 |
| セキュリティログ | 認証機能 (EPIC-001) | ログインイベントを記録 |

### 4.2 外部サービス

| サービス | 用途 | 障害時の挙動 |
|---------|------|------------|
| Have I Been Pwned API | パスワード漏洩チェック | スキップして警告ログ |

---

## 5. リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| Have I Been Pwned API 障害 | パスワード漏洩チェック不可 | タイムアウト設定、スキップして他の検証のみ実行 |
| セッション絶対タイムアウト精度 | 業務中断 | 5分前に警告表示（将来実装） |
| パスワード履歴チェックのパフォーマンス | レスポンス遅延 | 履歴5件のみチェック、インデックス最適化 |
