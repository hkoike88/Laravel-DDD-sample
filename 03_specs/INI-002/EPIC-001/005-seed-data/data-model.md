# Data Model: シードデータ投入

**Date**: 2025-12-24
**Feature**: 005-seed-data

## 既存エンティティ（参照のみ）

### Book（蔵書）

既存のbooksテーブルを使用。本機能ではデータ投入のみ行い、スキーマ変更は行わない。

| フィールド | 型 | 制約 | 説明 |
|-----------|-----|------|------|
| id | string(26) | PK | ULID形式 |
| title | string(255) | NOT NULL | 書籍タイトル |
| author | string(255) | NULLABLE | 著者名 |
| isbn | string(13) | NULLABLE, INDEX | ISBN-13（ハイフンなし） |
| publisher | string(255) | NULLABLE | 出版社名 |
| published_year | smallint | NULLABLE, INDEX | 出版年 |
| genre | string(100) | NULLABLE, INDEX | ジャンル |
| status | string(20) | DEFAULT 'available', INDEX | 貸出状態 |
| created_at | timestamp | | 作成日時 |
| updated_at | timestamp | | 更新日時 |

### BookStatus（蔵書状態）

| 値 | 日本語 | 説明 |
|----|--------|------|
| available | 貸出可 | 貸出可能な状態 |
| borrowed | 貸出中 | 貸出中の状態 |
| reserved | 予約あり | 予約されている状態 |

## CSVインポート形式

### ファイル形式

- エンコーディング: UTF-8
- 区切り文字: カンマ（,）
- 改行: LF または CRLF
- ヘッダー行: 必須（1行目）

### カラム定義

| カラム名 | 必須 | 型 | 説明 |
|---------|------|-----|------|
| title | ○ | string | 書籍タイトル（255文字以内） |
| author | | string | 著者名（255文字以内） |
| isbn | | string | ISBN-13（13桁数字、ハイフン可） |
| publisher | | string | 出版社名（255文字以内） |
| published_year | | integer | 出版年（1000〜現在年） |
| genre | | string | ジャンル（100文字以内） |
| status | | string | 状態（available/borrowed/reserved） |

### サンプルCSV

```csv
title,author,isbn,publisher,published_year,genre,status
吾輩は猫である,夏目漱石,9784003101018,岩波書店,1905,文学,available
坊っちゃん,夏目漱石,9784101010014,新潮社,1906,文学,borrowed
羅生門,芥川龍之介,9784003107010,岩波書店,1915,文学,available
走れメロス,太宰治,9784101006017,新潮社,1940,文学,reserved
```

## バリデーションルール

### ISBN-13検証

1. ハイフンを除去して13桁の数字列を取得
2. チェックディジット（最終桁）を検証
3. 計算式: `(10 - (Σ(d[i] * (1 or 3)) mod 10)) mod 10`

### 重複チェック

- ISBNが既存レコードと重複する場合はスキップ
- 重複ISBNは処理レポートに記録

## インポート結果レポート

### レポート形式（コンソール出力）

```text
=== 蔵書インポート結果 ===
処理ファイル: storage/app/sample_books.csv
総行数: 105
成功: 100件
スキップ: 5件

--- スキップ詳細 ---
行 15: ISBN形式エラー (1234567890123)
行 23: タイトル未入力
行 45: 重複ISBN (9784003101018)
行 67: 無効な出版年 (3000)
行 89: 無効な状態値 (unknown)
```

## サンプルデータ設計

### データ分布

| カテゴリ | 件数 | 割合 |
|---------|------|------|
| available（貸出可） | 40 | 40% |
| borrowed（貸出中） | 35 | 35% |
| reserved（予約あり） | 25 | 25% |
| **合計** | **100** | 100% |

### ジャンル分布

| ジャンル | 件数 |
|---------|------|
| 文学 | 35 |
| 歴史 | 20 |
| 科学 | 15 |
| 芸術 | 10 |
| 哲学 | 10 |
| その他 | 10 |

### 著者（一部抜粋）

- 夏目漱石
- 芥川龍之介
- 太宰治
- 川端康成
- 三島由紀夫
- 村上春樹
- 司馬遼太郎
- 宮沢賢治
