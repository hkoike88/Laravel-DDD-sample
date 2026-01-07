# Data Model: バックエンド初期設定

**Date**: 2025-12-23
**Feature**: 003-backend-setup
**Status**: Initial Setup (No domain entities yet)

## Overview

このフィーチャーはバックエンド初期設定であり、具体的なドメインエンティティの実装は含まれません。ここでは DDD アーキテクチャのレイヤー構成と、将来のエンティティ配置ガイドラインを文書化します。

## DDD Layer Architecture

### Layer Responsibilities

| Layer | Responsibility | Example Components |
|-------|---------------|-------------------|
| **Domain** | ビジネスロジック、ドメインルール | Entity, ValueObject, DomainService, Repository(Interface) |
| **Application** | ユースケース、アプリケーションサービス | UseCase, ApplicationService, DTO |
| **Infrastructure** | 外部システム連携、永続化 | RepositoryImpl, ExternalAPI, Persistence |
| **Presentation** | API エンドポイント、リクエスト/レスポンス | Controller, Request, Resource |

### Namespace Mapping

```
App\Src\{BoundedContext}\Domain\Entity\      → Entity クラス
App\Src\{BoundedContext}\Domain\ValueObject\ → ValueObject クラス
App\Src\{BoundedContext}\Domain\Repository\  → Repository インターフェース
App\Src\{BoundedContext}\Application\UseCase\ → UseCase クラス
App\Src\{BoundedContext}\Infrastructure\Persistence\ → Repository 実装
App\Src\{BoundedContext}\Presentation\Controller\ → API コントローラー
```

## Planned Bounded Contexts

以下の Bounded Context が将来的に実装される予定です（本フィーチャーでは空ディレクトリのみ作成）:

### BookManagement（書籍管理）

| Entity | Fields (Planned) | Relationships |
|--------|-----------------|---------------|
| Book | id, isbn, title, author, publishedAt | hasMany: Loan |

### LoanManagement（貸出管理）

| Entity | Fields (Planned) | Relationships |
|--------|-----------------|---------------|
| Loan | id, bookId, userId, borrowedAt, dueAt, returnedAt | belongsTo: Book, User |

### UserManagement（ユーザー管理）

| Entity | Fields (Planned) | Relationships |
|--------|-----------------|---------------|
| User | id, name, email, password | hasMany: Loan |

## Laravel Standard Models

Laravel の標準的な Eloquent モデルは `app/Models/` に配置されます。DDD エンティティとの使い分け:

| 配置場所 | 用途 |
|---------|------|
| `app/Models/` | Eloquent ORM 機能を直接使用するモデル（認証 User など） |
| `app/src/{Context}/Domain/Entity/` | ドメインロジックを持つ純粋なエンティティ |
| `app/src/{Context}/Infrastructure/Persistence/` | Eloquent を使用した Repository 実装 |

## Database Schema (Laravel Standard)

Sanctum インストール後のマイグレーションで作成されるテーブル:

```sql
-- Laravel 標準
users
password_reset_tokens
sessions
cache
jobs
failed_jobs

-- Sanctum
personal_access_tokens
```

## Validation Rules (Template)

将来のエンティティ実装時に適用する検証ルールのテンプレート:

```php
// Book Entity Example
[
    'isbn' => ['required', 'string', 'size:13'],
    'title' => ['required', 'string', 'max:255'],
    'author' => ['required', 'string', 'max:255'],
]
```

## State Transitions

本フィーチャーでは状態遷移を持つエンティティは実装しません。将来の Loan エンティティでは以下のような状態遷移が想定されます:

```
[Created] → [Borrowed] → [Returned]
                ↓
            [Overdue]
```

## Notes

- 本フィーチャーはディレクトリ構成と開発ツールの設定が主目的
- 具体的なエンティティ実装は後続フィーチャーで行う
- DDD 層間の依存関係は「外側から内側へ」（Infrastructure → Application → Domain）
