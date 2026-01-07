# Research: 蔵書エンティティ・Value Object 設計

**Date**: 2025-12-24
**Feature**: 001-book-entity-design

## 調査概要

本フィーチャーの技術的な未知項目と設計判断について調査を行った。プロジェクト内の既存ドキュメント（ADR、アーキテクチャ設計）を参照し、ベストプラクティスを確認した。

---

## 1. ULID 生成ライブラリ

### Decision: symfony/uid を使用

### Rationale
- ADR-0006 で明示的に推奨されている
- Symfony コンポーネントとして実績が豊富
- Laravel 11.x との互換性が確認済み
- Crockford's Base32 エンコーディングで26文字固定長

### Alternatives Considered
| ライブラリ | 評価 | 不採用理由 |
|-----------|------|-----------|
| robinvdvleuten/ulid | ○ | Symfony の方がメンテナンス体制が堅牢 |
| ramsey/uuid (UUID v7) | △ | ULID より長い（36文字）、ライブラリサポートが限定的 |
| 自前実装 | × | 車輪の再発明、エッジケースの考慮漏れリスク |

### Implementation
```php
// composer require symfony/uid
use Symfony\Component\Uid\Ulid;

$ulid = new Ulid();
$value = $ulid->toBase32(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"
```

---

## 2. ISBN バリデーションアルゴリズム

### Decision: 自前実装（ISBN-10/ISBN-13 両対応）

### Rationale
- 外部ライブラリへの依存を最小化
- ISBN バリデーションは比較的シンプルなアルゴリズム
- ドメイン層に外部依存を持ち込まない DDD の原則に適合

### Alternatives Considered
| 方法 | 評価 | 不採用理由 |
|------|------|-----------|
| biblys/isbn | △ | 機能過多、ドメイン層への外部依存 |
| nicebooks/isbn | △ | メンテナンス頻度が低い |
| 自前実装 | ◎ | シンプル、ドメイン層に最適 |

### Algorithm Details

**ISBN-13 チェックディジット計算:**
1. 先頭12桁の数字を取得
2. 奇数位置（1, 3, 5, ...）の数字を1倍
3. 偶数位置（2, 4, 6, ...）の数字を3倍
4. 合計を計算
5. チェックディジット = (10 - (合計 % 10)) % 10

**ISBN-10 チェックディジット計算:**
1. 先頭9桁の数字を取得
2. 各桁に10, 9, 8, ..., 2 を掛けて合計
3. チェックディジット = (11 - (合計 % 11)) % 11
4. チェックディジットが10の場合は 'X'

---

## 3. 状態遷移パターン

### Decision: ValueObject + Enum-like パターン

### Rationale
- 02_実装パターン.md の OrderStatus パターンを踏襲
- 状態遷移の可否判定メソッドを持つ不変オブジェクト
- PHP 8.1+ の Enum は使用せず、クラスベースで実装（既存パターンとの一貫性）

### Alternatives Considered
| パターン | 評価 | 不採用理由 |
|---------|------|-----------|
| PHP Enum | ○ | 既存パターンとの一貫性を優先 |
| State Pattern | △ | 3状態程度では過剰設計 |
| 文字列定数 | × | 型安全性が低い |

### State Transition Rules
```
available ──(borrow)──> borrowed
available ──(reserve)─> reserved
borrowed  ──(return)──> available
reserved  ──(lendToReserver)──> borrowed
reserved  ──(cancelReservation)──> available
```

---

## 4. ディレクトリ構成

### Decision: packages/Domain/Book/ 配下にドメイン別グループ化

### Rationale
- 03_ディレクトリ構成例.md に準拠
- 小規模プロジェクトのため BoundedContext 層は省略
- 将来的な拡張性を確保（必要時に BoundedContext 層を追加可能）

### Alternatives Considered
| 構成 | 評価 | 不採用理由 |
|------|------|-----------|
| app/Domain/ | △ | Laravel デフォルト構造との混在リスク |
| src/ | ○ | ドキュメントでは src/ だが、既存が packages/ |
| packages/Domain/ | ◎ | 既存プロジェクト構造との整合性 |

### Namespace Convention
```php
namespace Packages\Domain\Book\Domain\Model;
namespace Packages\Domain\Book\Domain\ValueObjects;
namespace Packages\Domain\Book\Domain\Repositories;
namespace Packages\Domain\Book\Domain\Exceptions;
```

---

## 5. テストフレームワーク

### Decision: Pest を使用

### Rationale
- CLAUDE.md で Pest が Active Technology として記載
- Laravel 11.x との親和性が高い
- 表現力豊かなテスト記述が可能

### Test Structure
```
tests/Unit/Domain/Book/
├── Model/
│   └── BookTest.php
└── ValueObjects/
    ├── BookIdTest.php
    ├── ISBNTest.php
    └── BookStatusTest.php
```

---

## 6. 例外設計

### Decision: ドメイン固有の例外クラスを定義

### Rationale
- 02_実装パターン.md の例外設計パターンを踏襲
- ドメインルール違反を明示的に表現
- キャッチ可能な粒度で例外を分離

### Exception Classes
| 例外クラス | 用途 |
|-----------|------|
| InvalidISBNException | ISBN 形式・チェックディジット不正 |
| InvalidBookStatusTransitionException | 不正な状態遷移 |
| EmptyBookTitleException | タイトル空白 |

---

## 7. 依存関係の注入

### Decision: コンストラクタインジェクション + ServiceProvider

### Rationale
- Laravel の標準パターン
- 03_ディレクトリ構成例.md の ServiceProvider 実装例に準拠
- リポジトリインターフェースと実装の分離を容易にする

### Implementation Example
```php
// BookServiceProvider.php
$this->app->bind(
    BookRepositoryInterface::class,
    EloquentBookRepository::class // 後続タスクで実装
);
```

---

## 結論

すべての技術的な未知項目が解決されました。既存のプロジェクトドキュメント（ADR、アーキテクチャ設計）に従い、一貫性のある実装が可能です。

### 次のステップ
1. data-model.md でエンティティと Value Object の詳細設計
2. quickstart.md で開発者向けクイックスタートガイド作成
3. /speckit.tasks でタスク分解
