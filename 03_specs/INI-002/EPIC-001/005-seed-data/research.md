# Research: シードデータ投入

**Date**: 2025-12-24
**Feature**: 005-seed-data

## 1. Laravel Seeder ベストプラクティス

### Decision: Laravel標準のSeederクラスを使用

**Rationale**:
- Laravel 11.xの標準機能として安定している
- `php artisan db:seed --class=BookSeeder` でクラス指定実行可能
- DatabaseSeederから呼び出すことで一括実行も可能
- テスト時にRefreshDatabaseトレイトと併用可能

**Alternatives considered**:
- 直接SQLインサート → 型安全性がない、バリデーションスキップ
- Eloquentモデル直接使用 → Seederの方が構造的

### サンプルデータ設計

**Decision**: 日本の古典文学作品を中心に100件以上のデータを用意

**データ構成**:
- 状態分布: available 40件, borrowed 35件, reserved 25件（バランスよく）
- ジャンル: 文学、歴史、科学、芸術など多様性を確保
- 著者: 夏目漱石、芥川龍之介、太宰治など有名作家
- 出版年: 1900年代〜2000年代まで幅広く

## 2. Laravel Factory ベストプラクティス

### Decision: Fakerを活用したBookFactoryを作成

**Rationale**:
- Faker日本語ローカライズ（`ja_JP`）でリアルな日本語データ生成
- 状態メソッド（`available()`, `borrowed()`, `reserved()`）で状態指定可能
- `Book::factory()->count(500)->create()` で大量データ生成
- symfony/uidでULID生成（既存のBookId値オブジェクトと整合）

**ISBN生成戦略**:
- Faker の `$this->faker->isbn13()` を使用（チェックディジット込み）
- ハイフンなし13桁形式に正規化

**Alternatives considered**:
- 手動でランダム生成 → チェックディジット計算が複雑
- 固定ISBNリスト → 拡張性がない

## 3. CSVインポート実装

### Decision: League\Csvライブラリを使用

**Rationale**:
- Laravelと高い互換性
- ストリーム処理で大容量ファイル対応
- UTF-8エンコーディング検出・変換サポート
- 行単位でのバリデーションとエラーハンドリングが容易

**代替案検討**:
- PHPネイティブの`fgetcsv()` → エンコーディング処理が煩雑
- Laravel Excel → 依存が大きい、CSVにはオーバースペック

### CSVフォーマット仕様

```csv
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
```

**必須カラム**: title
**任意カラム**: author, isbn, publisher, published_year, genre, status
**デフォルト値**: status = "available"

### バリデーションルール

| フィールド | ルール |
|-----------|--------|
| title | 必須、255文字以内 |
| author | 任意、255文字以内 |
| isbn | 任意、13桁数字（チェックディジット検証） |
| publisher | 任意、255文字以内 |
| published_year | 任意、1000〜現在年 |
| genre | 任意、100文字以内 |
| status | 任意、available/borrowed/reservedのいずれか |

### エラーハンドリング

**Decision**: 不正行スキップ・継続方式

**実装方針**:
1. 各行を検証し、不正な行はスキップ
2. 有効な行のみデータベースに投入
3. 処理完了後、スキップした行番号とエラー内容をレポート出力

## 4. パフォーマンス考慮事項

### バルクインサート

**Decision**: 100件単位でチャンク処理

**Rationale**:
- メモリ効率とトランザクション安全性のバランス
- 1000件/1分の要件を満たす（100件×10チャンク）

### 重複チェック

**Decision**: ISBNによる重複検出

**実装方針**:
1. インポート前にISBNの一意性をチェック
2. 重複ISBNはスキップし、レポートに記録
3. 追加動作（既存データは保持）

## 5. Artisanコマンド設計

### コマンド一覧

| コマンド | 説明 |
|---------|------|
| `php artisan db:seed --class=BookSeeder` | サンプルデータ投入 |
| `php artisan import:books {file}` | CSVインポート |
| `php artisan book:generate {count}` | ランダムデータ生成 |

### オプション設計

**import:books**:
- `--dry-run`: 実際に投入せずバリデーションのみ実行
- `--skip-duplicates`: 重複ISBNをスキップ（デフォルト有効）

**book:generate**:
- `--status={status}`: 生成する状態を指定（available/borrowed/reserved）

## 6. テスト戦略

### テストカテゴリ

| カテゴリ | テスト対象 |
|---------|-----------|
| Unit | BookFactory（データ生成ロジック） |
| Feature | BookSeeder（100件以上投入確認） |
| Feature | ImportBooksCommand（CSVインポート） |

### テストデータ

- 有効なCSVファイル
- 不正行を含むCSVファイル
- 重複ISBNを含むCSVファイル
- 大容量CSVファイル（1000件）
