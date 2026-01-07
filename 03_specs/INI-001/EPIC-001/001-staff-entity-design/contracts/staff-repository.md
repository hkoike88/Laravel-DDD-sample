# Staff Repository Contract

**Branch**: `001-staff-entity-design` | **Date**: 2025-12-25

## 概要

StaffRepositoryInterface は、Staff エンティティの永続化と取得を抽象化するインターフェースです。
ドメイン層とインフラストラクチャ層を分離し、データアクセスを抽象化します。

## インターフェース定義

```php
<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Repositories;

use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\Email;

interface StaffRepositoryInterface
{
    /**
     * ID で職員を取得
     *
     * @param StaffId $id 職員ID
     * @return Staff 職員エンティティ
     * @throws StaffNotFoundException 職員が存在しない場合
     */
    public function find(StaffId $id): Staff;

    /**
     * ID で職員を取得（存在しない場合は null）
     *
     * @param StaffId $id 職員ID
     * @return Staff|null 職員エンティティまたは null
     */
    public function findOrNull(StaffId $id): ?Staff;

    /**
     * メールアドレスで職員を検索
     *
     * @param Email $email メールアドレス
     * @return Staff|null 職員エンティティまたは null
     */
    public function findByEmail(Email $email): ?Staff;

    /**
     * メールアドレスの存在確認
     *
     * @param Email $email メールアドレス
     * @return bool 存在する場合 true
     */
    public function existsByEmail(Email $email): bool;

    /**
     * 職員を保存（新規作成または更新）
     *
     * @param Staff $staff 職員エンティティ
     */
    public function save(Staff $staff): void;

    /**
     * 職員を削除
     *
     * @param StaffId $id 職員ID
     */
    public function delete(StaffId $id): void;
}
```

## メソッド仕様

### find

| 項目 | 内容 |
|------|------|
| 入力 | `StaffId $id` - 検索対象の職員ID |
| 出力 | `Staff` - 職員エンティティ |
| 例外 | `StaffNotFoundException` - 職員が存在しない場合 |
| 備考 | 存在確認が必須の場合に使用 |

### findOrNull

| 項目 | 内容 |
|------|------|
| 入力 | `StaffId $id` - 検索対象の職員ID |
| 出力 | `Staff|null` - 職員エンティティまたは null |
| 例外 | なし |
| 備考 | 存在しない可能性がある場合に使用 |

### findByEmail

| 項目 | 内容 |
|------|------|
| 入力 | `Email $email` - 検索対象のメールアドレス |
| 出力 | `Staff|null` - 職員エンティティまたは null |
| 例外 | なし |
| 備考 | 認証時のログインユーザー取得に使用 |

### existsByEmail

| 項目 | 内容 |
|------|------|
| 入力 | `Email $email` - 確認対象のメールアドレス |
| 出力 | `bool` - 存在する場合 true |
| 例外 | なし |
| 備考 | 新規登録時の重複チェックに使用 |

### save

| 項目 | 内容 |
|------|------|
| 入力 | `Staff $staff` - 保存対象の職員エンティティ |
| 出力 | なし |
| 例外 | `DuplicateEmailException` - メールアドレスが重複する場合（新規作成時） |
| 備考 | ID が既存なら更新、新規なら挿入（upsert） |

### delete

| 項目 | 内容 |
|------|------|
| 入力 | `StaffId $id` - 削除対象の職員ID |
| 出力 | なし |
| 例外 | なし |
| 備考 | 存在しない ID を指定してもエラーにならない（冪等性保証） |

## 実装ガイドライン

1. **トランザクション**: `save` メソッドはトランザクション内で実行されることを想定
2. **楽観的ロック**: 将来的に `updated_at` を使用した楽観的ロックを検討
3. **キャッシュ**: 必要に応じて `find` 結果をキャッシュ可能（実装は Infrastructure 層）
4. **ログ**: 重要な操作（save, delete）は監査ログを出力
