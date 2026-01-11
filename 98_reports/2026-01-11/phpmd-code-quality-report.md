# PHPMD コード品質分析レポート

**分析日時**: 2026-01-11 14:51
**分析ツール**: PHPMD 2.15.0
**PHP バージョン**: 8.2.30

---

## エグゼクティブサマリー

PHPMDによる静的解析の結果、**27件の問題**が検出されました。

### 優先度別内訳

| 優先度 | 件数 | 割合 | 対応期限 |
|--------|------|------|----------|
| **Top (1)** | 2件 | 7.4% | 即座に対応 |
| **High (2)** | 3件 | 11.1% | 次スプリント |
| **Moderate (3)** | 22件 | 81.5% | 継続的に改善 |

### 総合評価

**評価: C（要改善）**

- ✅ **良好な点**: 致命的な問題（Top優先度）は2件のみで全体の7.4%
- ⚠️ **改善点**: 複雑度の高いメソッドが存在（循環的複雑度18、NPath複雑度36888）
- ⚠️ **改善点**: 結合度の高いコントローラが存在（最大21依存）
- ℹ️ **参考**: 未使用パラメータなど軽微な問題が多数

---

## 1. 重要な問題（Top & High 優先度）

### 🔴 Top (1) 優先度: 2件

#### 1. MissingImport - PasswordNotCompromisedRule.php:80

**問題内容:**
```
Missing class import via use statement (line '80', column '23')
```

**影響:**
- コードの可読性低下
- 名前空間の明示性欠如

**推奨対応:**
```php
// 80行目付近で use 文を追加
use SomeClass;
```

**対応期限**: 即座に対応

---

#### 2. BooleanArgumentFlag - Staff.php:58

**問題内容:**
```
The method create has a boolean flag argument $isAdmin,
which is a certain sign of a Single Responsibility Principle violation.
```

**影響:**
- 単一責任の原則違反
- メソッドが複数の動作をする
- テストが複雑になる

**推奨対応:**
```php
// ❌ 現在
public function create($name, $isAdmin) {
    if ($isAdmin) {
        // 管理者作成
    } else {
        // 通常ユーザー作成
    }
}

// ✅ 改善案
public function createAsAdmin($name) {
    // 管理者作成
}

public function createAsUser($name) {
    // 通常ユーザー作成
}
```

**対応期限**: 即座に対応

---

### 🟡 High (2) 優先度: 3件

#### 1. CouplingBetweenObjects - StaffAccountController.php

**問題内容:**
```
The class StaffAccountController has a coupling between objects value of 21.
Consider to reduce the number of dependencies under 13.
```

**影響:**
- 非常に高い結合度（推奨値13を大幅に超える）
- テストが困難
- 変更の影響範囲が大きい

**推奨対応:**
- UseCaseクラスに責務を分離
- 依存を抽象化（インターフェース導入）
- ファサードの使用を検討

**対応期限**: 次スプリントで対応

---

#### 2. CouplingBetweenObjects - BookController.php

**問題内容:**
```
The class BookController has a coupling between objects value of 16.
Consider to reduce the number of dependencies under 13.
```

**影響:**
- 高い結合度（推奨値13を超える）
- 複数の責務を持っている可能性

**推奨対応:**
- UseCaseクラスへの責務分離
- コンストラクタ注入の見直し

**対応期限**: 次スプリントで対応

---

#### 3. CouplingBetweenObjects - UpdateStaffHandler.php

**問題内容:**
```
The class UpdateStaffHandler has a coupling between objects value of 14.
Consider to reduce the number of dependencies under 13.
```

**影響:**
- やや高い結合度

**推奨対応:**
- 依存の見直し
- 共通処理のサービス化

**対応期限**: 次スプリントで対応

---

## 2. ルールセット別分析

### Code Size Rules: 12件（44.4%）

最も多く検出された問題カテゴリ。

#### 主な問題

1. **CyclomaticComplexity（循環的複雑度）: 2件**
   - `ImportBooksCommand::handle()` - **18**（推奨値: 10以下）
   - `ImportBooksCommand::validateRow()` - **16**（推奨値: 10以下）

2. **NPathComplexity: 3件**
   - `ImportBooksCommand::handle()` - **36,888**（推奨値: 200以下）
   - `ImportBooksCommand::validateRow()` - **3,600**（推奨値: 200以下）
   - `EloquentBookRepository::applySearchCriteria()` - 256（推奨値: 200以下）

3. **ExcessiveMethodLength: 1件**
   - `ImportBooksCommand::handle()` - **119行**（推奨値: 100行以下）

4. **TooManyPublicMethods: 3件**
   - `Book` - 16個（推奨値: 10個以下）
   - `BookStatus` - 16個（推奨値: 10個以下）
   - `Staff` - 13個（推奨値: 10個以下）

5. **ExcessiveParameterList: 3件**
   - `BookSearchCriteria::__construct()` - **12個**（推奨値: 10個以下）
   - `Book::__construct()` - 10個（推奨値: 10個以下）
   - `Book::reconstruct()` - 10個（推奨値: 10個以下）

---

### Unused Code Rules: 7件（25.9%）

#### 内訳

- **UnusedFormalParameter（未使用パラメータ）: 5件**
  - `ImportBooksCommand::validateRow()` - `$lineNumber`
  - `PasswordNotReusedRule` - `$attribute`
  - `PasswordPolicyRule` - `$attribute`
  - `BookCollectionResource` - `$request`
  - `BookResource` - `$request`

- **UnusedLocalVariable（未使用ローカル変数）: 2件**
  - `PasswordNotCompromisedRule` - `$count`
  - `UpdateStaffHandler` - `$currentUpdatedAt`

**影響:**
- コードの保守性低下
- 混乱を招く可能性

**推奨対応:**
- 未使用の変数/パラメータを削除
- インターフェース要件の場合はコメントで明示

---

### Naming Rules: 3件（11.1%）

#### ShortMethodName（短いメソッド名）: 3件

- `Book::id()` - 3文字未満
- `PasswordHistory::id()` - 3文字未満
- `Staff::id()` - 3文字未満

**影響:**
- 軽微（DDDのゲッターメソッドとして一般的）

**推奨対応:**
- 現状維持（DDDパターンとして許容範囲）
- または設定で除外

---

### Design Rules: 3件（11.1%）

すべてCouplingBetweenObjects（前述のHigh優先度参照）

---

### Clean Code Rules: 2件（7.4%）

- BooleanArgumentFlag: 1件（前述のTop優先度参照）
- MissingImport: 1件（前述のTop優先度参照）

---

## 3. 名前空間別分析

### App\Console\Commands: 5件（27.8%）

**最も問題が集中している領域**

主な問題ファイル:
- `ImportBooksCommand.php`
  - handle() メソッドが複雑すぎる（複雑度18、119行）
  - validateRow() メソッドも複雑（複雑度16）

**推奨対応:**
- メソッドの分割
- バリデーションロジックを別クラスに抽出
- 早期リターンの活用

---

### Packages\Domain\Book\Domain\Model: 4件（22.2%）

主な問題:
- `Book.php` - publicメソッド数16個、パラメータ数10個
- `BookStatus.php` - publicメソッド数16個

**推奨対応:**
- publicメソッドの見直し（本当に全て必要か）
- パラメータはDTOでグループ化を検討

---

### Packages\Domain\Staff\Domain\Model: 3件（16.7%）

主な問題:
- `Staff.php` - publicメソッド数13個、BooleanArgumentFlag
- `PasswordHistory.php` - 短いメソッド名

---

### その他の名前空間: 各1件

- Packages\Domain\Book\Application\DTO
- Packages\Domain\Book\Application\Repositories
- Packages\Domain\Book\Domain\ValueObjects
- Packages\Domain\Book\Presentation\HTTP\Controllers
- Packages\Domain\Staff\Application\UseCases\Commands\UpdateStaff
- Packages\Domain\Staff\Presentation\HTTP\Controllers

---

## 4. 重点対応すべき項目

### 🔴 最優先（今週中）

1. **ImportBooksCommand のリファクタリング**
   - handle() メソッドを複数のprivateメソッドに分割
   - validateRow() をバリデータクラスに抽出
   - 目標: 循環的複雑度を10以下に

2. **Staff::create() の修正**
   - BooleanArgumentFlag を解消
   - createAsAdmin() と createAsUser() に分割

3. **use 文の追加**
   - PasswordNotCompromisedRule.php の80行目

---

### 🟡 高優先（次スプリント）

1. **コントローラの結合度削減**
   - StaffAccountController（21依存 → 13以下へ）
   - BookController（16依存 → 13以下へ）
   - UpdateStaffHandler（14依存 → 13以下へ）
   - UseCaseパターンの徹底

2. **パラメータ数の削減**
   - BookSearchCriteria（12個 → 10個以下へ）
   - DTOの見直し

---

### 🟢 中優先（継続的に改善）

1. **未使用コードの削除**
   - 未使用パラメータ 5件
   - 未使用ローカル変数 2件

2. **publicメソッド数の削減**
   - Book、BookStatus、Staff のメソッド見直し
   - 本当に外部公開が必要なメソッドのみ残す

3. **短いメソッド名**
   - 現状維持または設定で除外を検討

---

## 5. 推奨アクションプラン

### Week 1（即座に対応）

- [ ] ImportBooksCommand のリファクタリング
  - [ ] handle() メソッドを分割
  - [ ] バリデーションクラスを抽出
- [ ] Staff::create() のBoolean引数を解消
- [ ] PasswordNotCompromisedRule の use 文追加

### Week 2-3（次スプリント）

- [ ] StaffAccountController の結合度削減
- [ ] BookController の結合度削減
- [ ] UpdateStaffHandler の依存見直し
- [ ] BookSearchCriteria のパラメータ削減

### 継続的改善

- [ ] 未使用コードの削除（レビュー時に都度対応）
- [ ] publicメソッド数の見直し（新規開発時に意識）
- [ ] 新規コードでは問題を発生させない
  - コミット前に `make phpmd` を実行
  - 循環的複雑度10以下を維持
  - 結合度13以下を維持

---

## 6. 定期的なモニタリング

### 毎週

- PHPMD実行: `make phpmd`
- Top/High優先度の問題数を確認
- 新規問題の発生を防ぐ

### スプリント終了時

- 問題総数の推移を確認
- 目標: 前スプリントより減少
- HTMLレポート生成: `make phpmd-report`

### リリース前

- Top優先度: **0件**
- High優先度: **可能な限り0件**
- 総問題数: **15件以下**を目標

---

## 7. まとめ

### 現状評価

**総合評価: C（要改善）**

- 検出問題数: 27件
- Top優先度: 2件（7.4%）
- High優先度: 3件（11.1%）

### 主要課題

1. **ImportBooksCommand の複雑度が非常に高い**
   - 循環的複雑度18、NPath複雑度36,888
   - 即座のリファクタリングが必要

2. **コントローラの結合度が高い**
   - StaffAccountController: 21依存（非常に高い）
   - BookController: 16依存（高い）

3. **パラメータ数が多い**
   - BookSearchCriteria: 12個

### 改善の方向性

1. **短期（1-2週間）**
   - Top/High優先度の問題を解消
   - 複雑なメソッドのリファクタリング

2. **中期（1-2スプリント）**
   - 結合度の削減
   - パラメータ数の削減
   - UseCaseパターンの徹底

3. **長期（継続的）**
   - 新規コードで問題を発生させない
   - コードレビューでの品質維持
   - 定期的なモニタリング

---

## 付録: 詳細データ

### ルール別問題数

| ルール名 | 件数 | 割合 |
|---------|------|------|
| UnusedFormalParameter | 5 | 18.5% |
| NPathComplexity | 3 | 11.1% |
| ExcessiveParameterList | 3 | 11.1% |
| TooManyPublicMethods | 3 | 11.1% |
| ShortMethodName | 3 | 11.1% |
| CouplingBetweenObjects | 3 | 11.1% |
| CyclomaticComplexity | 2 | 7.4% |
| UnusedLocalVariable | 2 | 7.4% |
| ExcessiveMethodLength | 1 | 3.7% |
| MissingImport | 1 | 3.7% |
| BooleanArgumentFlag | 1 | 3.7% |

### 問題ファイル一覧

1. ImportBooksCommand.php - 5件
2. Book.php - 4件
3. Staff.php - 3件
4. PasswordNotCompromisedRule.php - 2件
5. BookController.php - 1件
6. StaffAccountController.php - 1件
7. その他 - 11件

---

**レポート作成日**: 2026-01-12
**次回レビュー**: 2026-01-19
