# Research: バックエンド初期設定

**Date**: 2025-12-23
**Feature**: 003-backend-setup
**Status**: Complete

## Research Tasks

### 1. Laravel 11.x プロジェクト作成方法

**Decision**: `composer create-project laravel/laravel` を使用

**Rationale**:
- 公式推奨の方法
- 最新の Laravel 11.x がインストールされる
- Docker コンテナ内で実行可能
- 既存の composer.json がある場合は `composer require laravel/framework` で対応

**Alternatives Considered**:
- Laravel Installer (`laravel new`) - グローバルインストールが必要なため却下
- Laravel Sail - 独自の Docker 構成が既に存在するため不要

---

### 2. DDD ディレクトリ構成のベストプラクティス

**Decision**: `app/src/` 配下に Bounded Context ごとのディレクトリを配置

**Rationale**:
- Laravel の `app/` ディレクトリを拡張する形で DDD 構造を追加
- 各 Bounded Context（BookManagement, LoanManagement 等）を独立して管理
- 共通リソースは `Common/` ディレクトリに集約
- Composer PSR-4 オートロードで名前空間をマッピング

**Structure**:
```
app/src/
├── Common/
│   ├── Domain/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   └── Repository/
│   ├── Application/
│   │   └── Service/
│   └── Infrastructure/
│       └── Persistence/
├── BookManagement/
│   ├── Domain/
│   ├── Application/
│   ├── Infrastructure/
│   └── Presentation/
└── [OtherContexts]/
```

**Alternatives Considered**:
- `packages/` ディレクトリ方式 - 小規模プロジェクトには過剰
- モジュール式（Laravel Modules）- 追加パッケージ依存を避けたい

---

### 3. Composer オートロード設定

**Decision**: PSR-4 オートロードで `App\Src\` 名前空間を追加

**Rationale**:
- Laravel 標準の `App\` 名前空間と共存
- DDD 層は `App\Src\{BoundedContext}\{Layer}` でアクセス

**Configuration** (composer.json):
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "App\\Src\\": "app/src/"
        }
    }
}
```

---

### 4. PHPStan/Larastan 設定

**Decision**: Larastan を使用、解析レベル 5 から開始

**Rationale**:
- Larastan は PHPStan の Laravel 拡張
- Eloquent モデルや Facade を正しく解析
- レベル 5 はバランスの取れた開始点（max は 9）
- 段階的にレベルを上げることが可能

**Configuration** (phpstan.neon):
```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    level: 5
    paths:
        - app/
```

**Alternatives Considered**:
- PHPStan のみ - Laravel 固有の構文を解析できない
- Psalm - Laravel サポートが限定的

---

### 5. Pest テストフレームワーク

**Decision**: Pest を使用、PHPUnit との互換性を維持

**Rationale**:
- 簡潔な記法で可読性向上
- PHPUnit と完全互換
- Laravel 公式がサポート

**Configuration**:
- `./vendor/bin/pest --init` で初期化
- `tests/Pest.php` で共通設定
- Feature/Unit テストディレクトリ構造を維持

---

### 6. Laravel Sanctum 認証

**Decision**: Sanctum をインストールし、API トークン認証の基盤を準備

**Rationale**:
- Laravel 標準の API 認証パッケージ
- SPA 認証と API トークン認証の両方をサポート
- 軽量で設定が簡単

**Installation Steps**:
1. `composer require laravel/sanctum`
2. `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
3. `php artisan migrate`

---

### 7. データベース接続設定

**Decision**: `.env` ファイルで Docker 環境変数を参照

**Rationale**:
- Docker Compose の環境変数と連携
- ホスト名は `db`（サービス名）
- 認証情報は `.env` から注入

**Configuration** (.env):
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=library
DB_USERNAME=library
DB_PASSWORD=secret
```

---

## Summary

すべての研究タスクが完了しました。Technical Context に「NEEDS CLARIFICATION」はありません。

| Task | Status | Decision |
|------|--------|----------|
| Laravel プロジェクト作成 | ✅ | composer create-project |
| DDD ディレクトリ構成 | ✅ | app/src/ 配下に Bounded Context |
| Composer オートロード | ✅ | PSR-4 で App\Src\ 追加 |
| PHPStan/Larastan | ✅ | Larastan レベル 5 |
| Pest テスト | ✅ | Pest + PHPUnit 互換 |
| Sanctum 認証 | ✅ | API トークン基盤準備 |
| データベース接続 | ✅ | Docker 環境変数連携 |
