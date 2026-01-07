# ADR-0006: ID 生成戦略（ULID）

## ステータス

採用

## コンテキスト

エンティティの識別子（ID）の生成方式を決定する必要がある。以下の要件を考慮する。

- 分散環境での一意性保証
- URL に含めても問題ない形式
- データベースパフォーマンス
- ソート可能性（時系列での並び替え）

## 決定

**ULID（Universally Unique Lexicographically Sortable Identifier）** を採用する。

### 採用理由

1. **時系列ソート可能**
   - 先頭 48 ビットがタイムスタンプ
   - 生成順でソートすると時系列順になる
   - created_at なしでも作成順を把握可能

2. **URL フレンドリー**
   - 26 文字の英数字（Crockford's Base32）
   - 小文字のみで構成され、URL で扱いやすい
   - 例: `01ARZ3NDEKTSV4RRFFQ69G5FAV`

3. **データベースパフォーマンス**
   - UUID v4 と異なり、単調増加に近い値
   - B+Tree インデックスでの挿入パフォーマンスが良好
   - MySQL InnoDB との相性が良い

4. **一意性**
   - 同一ミリ秒内でも 80 ビットのランダム部分で衝突を回避
   - 分散環境でも中央サーバー不要

5. **文字列長**
   - 26 文字固定長（UUID の 36 文字より短い）
   - ストレージ効率が良い

## 比較検討

| 項目 | ULID | UUID v4 | UUID v7 | 連番 (Auto Increment) |
|------|------|---------|---------|----------------------|
| ソート可能 | ◎ | × | ◎ | ◎ |
| 分散生成 | ◎ | ◎ | ◎ | × |
| URL 安全性 | ◎ | ○ | ○ | ◎ |
| DB パフォーマンス | ◎ | △ | ◎ | ◎ |
| 文字列長 | 26 | 36 | 36 | 可変 |
| 推測困難性 | ○ | ◎ | ○ | × |
| 実装の普及度 | ○ | ◎ | △ | ◎ |

### 不採用理由

- **UUID v4**: ランダムなため時系列ソート不可。B+Tree でのパフォーマンス問題
- **UUID v7**: 仕様が比較的新しく、ライブラリのサポートが限定的
- **連番**: 分散環境で衝突。ID の推測が容易でセキュリティリスク

## 結果

### メリット

- 時系列でのソートが自然にできる
- UUID v4 より短く、URL で扱いやすい
- インデックスパフォーマンスが良好

### デメリット

- UUID ほど普及していない
- 一部のツールで直接サポートされていない場合がある
- タイムスタンプ部分から概算の作成時刻が推測可能

### リスクと対策

| リスク | 対策 |
|--------|------|
| 作成時刻の推測 | 機密性が必要な場合は別途対策（問題になることは稀） |
| ライブラリの信頼性 | 実績のあるライブラリを使用 |

## 実装方針

### PHP（バックエンド）

```php
// composer require symfony/uid
use Symfony\Component\Uid\Ulid;

class BookId
{
    private function __construct(
        private readonly string $value
    ) {}

    public static function generate(): self
    {
        return new self((new Ulid())->toBase32());
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
```

### TypeScript（フロントエンド）

```typescript
// npm install ulid
import { ulid } from 'ulid';

// 生成（通常はサーバー側で生成）
const id = ulid(); // "01ARZ3NDEKTSV4RRFFQ69G5FAV"

// 型定義
type BookId = string; // Branded Type も検討可
```

### データベース

```sql
CREATE TABLE books (
    id CHAR(26) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- インデックスは PRIMARY KEY で自動作成
-- CHAR(26) で固定長として格納
```

### Laravel Migration

```php
Schema::create('books', function (Blueprint $table) {
    $table->char('id', 26)->primary();
    $table->string('title');
    $table->timestamps();
});
```

## 参考資料

- [ULID 仕様](https://github.com/ulid/spec)
- [Symfony Uid Component](https://symfony.com/doc/current/components/uid.html)
- [ulid (npm)](https://www.npmjs.com/package/ulid)
