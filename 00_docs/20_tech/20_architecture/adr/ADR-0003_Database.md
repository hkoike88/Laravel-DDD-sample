# ADR-0003: データベース（MySQL）

## ステータス

採用

## コンテキスト

図書館デジタル化システムのデータ永続化において、以下の要件を満たすデータベースが必要である。

- ACID 特性を持つトランザクション処理
- 書籍・貸出などのリレーショナルデータの管理
- Laravel Eloquent ORM との親和性
- 運用・管理のしやすさ
- 学習目的として一般的な技術

## 決定

**MySQL 8.0** を採用する。

### 採用理由

1. **Laravel との親和性**
   - Laravel のデフォルトデータベースとして最も実績がある
   - Eloquent ORM で完全サポート
   - マイグレーション・シーディングの動作が安定

2. **機能の充実**
   - MySQL 8.0 で Window 関数、CTE、JSON 型をサポート
   - フルテキスト検索（書籍検索に有用）
   - レプリケーション・バックアップ機能

3. **運用実績**
   - 世界で最も使用されている RDBMS の一つ
   - ホスティングサービスでの対応が広い
   - 管理ツール（phpMyAdmin、MySQL Workbench）が充実

4. **学習価値**
   - 最も普及している RDBMS であり、学習価値が高い
   - 日本語ドキュメント・書籍が豊富

5. **ライセンス**
   - Community Edition は GPL ライセンスで無料利用可能

## 比較検討

| 項目 | MySQL | PostgreSQL | MariaDB | SQLite |
|------|-------|------------|---------|--------|
| Laravel 親和性 | ◎ | ○ | ◎ | ○ |
| 機能性 | ○ | ◎ | ○ | △ |
| パフォーマンス | ○ | ○ | ○ | ○ |
| 運用実績 | ◎ | ○ | ○ | ○ |
| 学習リソース | ◎ | ○ | ○ | ○ |
| セットアップ容易性 | ○ | ○ | ○ | ◎ |

### 不採用理由

- **PostgreSQL**: 高機能だが、本プロジェクトでは MySQL で十分。日本での採用実績は MySQL の方が多い
- **MariaDB**: MySQL 互換だが、微妙な差異がある。Laravel のデフォルトは MySQL
- **SQLite**: 本番環境には不向き。同時接続に弱い

## 結果

### メリット

- Laravel エコシステムとの高い互換性
- phpMyAdmin による GUI 管理が容易
- Docker での構築が簡単

### デメリット

- PostgreSQL と比較すると高度な機能（配列型、範囲型など）がない
- Oracle 社による管理（ライセンス方針の変更リスク）

### リスクと対策

| リスク | 対策 |
|--------|------|
| 文字コードの問題 | utf8mb4 を使用し、絵文字を含む全 Unicode 対応 |
| パフォーマンス劣化 | 適切なインデックス設計、Slow Query Log の監視 |
| バックアップ漏れ | mysqldump による日次バックアップを設定 |

## 設定方針

### 文字コード設定

```ini
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

### Laravel 設定

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'library'),
    'username' => env('DB_USERNAME', 'library'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'strict' => true,
    'engine' => 'InnoDB',
],
```

## 参考資料

- [MySQL 8.0 リファレンスマニュアル](https://dev.mysql.com/doc/refman/8.0/ja/)
- [Laravel Database 設定](https://laravel.com/docs/database)
