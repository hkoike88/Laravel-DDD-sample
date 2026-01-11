# PHPMD レポート読み方ガイド

## 目次

- [1. 概要](#1-概要)
- [2. レポート閲覧方法](#2-レポート閲覧方法)
- [3. 優先度の読み方](#3-優先度の読み方)
- [4. Code Size Rules（コードサイズ関連ルール）](#4-code-sizerulesコードサイズ関連ルール)
- [5. Complexity Rules（複雑度関連ルール）](#5-complexity-rules複雑度関連ルール)
- [6. Design Rules（設計関連ルール）](#6-design-rules設計関連ルール)
- [7. Naming Rules（命名関連ルール）](#7-naming-rules命名関連ルール)
- [8. Unused Code Rules（未使用コード関連ルール）](#8-unused-code-rules未使用コード関連ルール)
- [9. Clean Code Rules（クリーンコード関連ルール）](#9-clean-code-rulesクリーンコード関連ルール)
- [10. まとめ：優先的に対応すべき項目](#10-まとめ優先的に対応すべき項目)

---

## 1. 概要

PHPMD（PHP Mess Detector）は、PHPコードの潜在的な問題を検出する静的解析ツールです。
循環的複雑度、コードサイズ、命名規則、未使用コードなど、様々な観点からコード品質を評価します。

このドキュメントでは、PHPMDが生成するHTMLレポートの読み方と、各ルールの意味、対処方法を説明します。

---

## 2. レポート閲覧方法

### 2.1 コマンド実行

```bash
# ターミナルに結果を表示
make phpmd

# HTMLレポートを生成
make phpmd-report
```

### 2.2 レポート閲覧

```bash
# ブラウザでHTMLレポートを開く
google-chrome backend/storage/phpmd/report.html
```

### 2.3 レポートの構成

HTMLレポートは以下のセクションで構成されています：

#### 1. Summary（概要）

レポート上部に表示される統計情報：

- **By priority（優先度別）**
  - Top (1): 最優先で対応すべき問題
  - High (2): 優先的に対応すべき問題
  - Moderate (3): 継続的に改善すべき問題

- **By namespace（名前空間別）**
  - どの名前空間に問題が多いかを確認

- **By rule set（ルールセット別）**
  - Code Size Rules（コードサイズ）
  - Complexity Rules（複雑度）
  - Design Rules（設計）
  - Naming Rules（命名）
  - Unused Code Rules（未使用コード）
  - Clean Code Rules（クリーンコード）

- **By name（ルール名別）**
  - どのルール違反が多いかを確認

#### 2. Details（問題詳細）

**重要:** デフォルトでは折りたたまれています。「Show details ▼」リンクをクリックして展開してください。

展開すると、各問題ごとに以下の情報が表示されます：

```
#1                                      ← 問題番号
The method handle() has a Cyclomatic   ← 問題の説明
Complexity of 18. The configured
cyclomatic complexity threshold is 10.
(help)                                  ← ルールの詳細説明へのリンク

Moderate (3)                            ← 優先度

File: /var/www/html/app/Console/       ← ファイルパス（クリック可能）
Commands/ImportBooksCommand.php

Show code ▼                             ← コードを表示（クリックで展開）
```

**各項目の見方:**

1. **問題番号**: レポート内での通し番号
2. **問題の説明**: 何が問題か、どの基準を超えているか
3. **(help)**: PHPMDの公式ドキュメントへのリンク
4. **優先度**: Top (1) / High (2) / Moderate (3)
5. **File**: 問題があるファイルのパス（クリックでファイルを開く）
6. **Show code**: クリックすると該当コードが表示される

**効率的な確認方法:**

1. まず Summary で問題の全体像を把握
2. 優先度が高い問題（Top, High）から確認
3. Details セクションで具体的なファイルと問題箇所を特定
4. Show code で実際のコードを確認
5. (help) リンクで詳細な対処方法を確認

---

## 3. 優先度の読み方

PHPMDは検出した問題を3つの優先度に分類します。

### 3.1 Top (1) - 最優先

**意味:** 即座に対応すべき重大な問題

**該当するルール:**
- ExcessiveMethodLength（メソッド長が極端に長い）
- ExcessiveClassLength（クラス長が極端に長い）
- CyclomaticComplexity（循環的複雑度が非常に高い）

**対応方針:**
- すぐにリファクタリングを実施
- 次のスプリントで必ず対応

---

### 3.2 High (2) - 高優先度

**意味:** できるだけ早く対応すべき問題

**該当するルール:**
- NPathComplexity（NPath複雑度が高い）
- ExcessiveParameterList（パラメータ数が多い）
- CouplingBetweenObjects（結合度が高い）

**対応方針:**
- 優先的にリファクタリングを検討
- 新規コードでは発生させない

---

### 3.3 Moderate (3) - 中優先度

**意味:** 時間があるときに対応すべき問題

**該当するルール:**
- UnusedFormalParameter（未使用パラメータ）
- UnusedLocalVariable（未使用ローカル変数）
- ShortMethodName（短いメソッド名）
- BooleanArgumentFlag（真偽値引数フラグ）

**対応方針:**
- 継続的に改善
- コードレビュー時に指摘

---

## 4. Code Size Rules（コードサイズ関連ルール）

コードのサイズに関する問題を検出します。

### 4.1 CyclomaticComplexity（循環的複雑度）

**意味:** メソッドの分岐数が多すぎる

**検出基準:**
- 循環的複雑度が設定値（デフォルト: 10）を超えている

**問題点:**
- テストが困難
- バグが混入しやすい
- 理解が困難

**対策:**
```php
// ❌ 悪い例: 複雑度が高い
public function processOrder($order) {
    if ($order->isPaid()) {
        if ($order->isShipped()) {
            if ($order->hasTracking()) {
                // ...複雑なロジック
            } else {
                // ...
            }
        } else {
            // ...
        }
    } else {
        // ...
    }
}

// ✅ 良い例: メソッドを分割
public function processOrder($order) {
    if (!$order->isPaid()) {
        return $this->handleUnpaidOrder($order);
    }

    return $this->handlePaidOrder($order);
}

private function handlePaidOrder($order) {
    if (!$order->isShipped()) {
        return $this->handleUnshippedOrder($order);
    }

    return $this->handleShippedOrder($order);
}
```

**目標値:**
- **1-10**: 良好 ✅
- **11-20**: 要改善
- **21以上**: 即座にリファクタリング必要

---

### 4.2 NPathComplexity（NPath複雑度）

**意味:** メソッド内の実行パスの総数が多すぎる

**検出基準:**
- NPath複雑度が設定値（デフォルト: 200）を超えている

**問題点:**
- すべてのパスをテストするのが困難
- 予期しない挙動が発生しやすい

**対策:**
- メソッドを分割
- 早期リターンを活用
- 条件分岐を減らす

**目標値:**
- **0-200**: 良好 ✅
- **201-1000**: 要改善
- **1001以上**: 即座にリファクタリング必要

---

### 4.3 ExcessiveMethodLength（過度に長いメソッド）

**意味:** メソッドの行数が多すぎる

**検出基準:**
- メソッドの行数が設定値（デフォルト: 100行）を超えている

**問題点:**
- 単一責任の原則に違反している可能性
- 理解が困難
- テストが困難

**対策:**
- メソッドを責務ごとに分割
- 共通処理をプライベートメソッドに抽出

**目標値:**
- **0-30行**: 理想的 ✅
- **31-50行**: 良好 ✅
- **51-100行**: やや長い
- **101行以上**: 長すぎる、分割が必要

---

### 4.4 ExcessiveClassLength（過度に長いクラス）

**意味:** クラスの行数が多すぎる

**検出基準:**
- クラスの行数が設定値（デフォルト: 1000行）を超えている

**問題点:**
- 複数の責務を持っている可能性（God Object）
- 理解が困難
- 変更の影響範囲が大きい

**対策:**
- クラスを責務ごとに分割
- 関連するメソッドを別クラスに抽出

**目標値:**
- **0-200行**: 良好 ✅
- **201-500行**: やや長い
- **501-1000行**: 長い、分割を検討
- **1001行以上**: 非常に長い、即座に分割が必要

---

### 4.5 ExcessiveParameterList（過度に多いパラメータ）

**意味:** メソッドのパラメータ数が多すぎる

**検出基準:**
- パラメータ数が設定値（デフォルト: 10個）を超えている

**問題点:**
- メソッドの責務が多すぎる可能性
- 呼び出しが複雑
- テストが困難

**対策:**
```php
// ❌ 悪い例: パラメータが多すぎる
public function createBook(
    $title, $author, $isbn, $publisher,
    $publishedDate, $price, $stock, $category,
    $description, $image
) {
    // ...
}

// ✅ 良い例: DTOオブジェクトを使う
public function createBook(BookCreateData $data) {
    // ...
}
```

**目標値:**
- **0-4個**: 理想的 ✅
- **5-7個**: やや多い
- **8-10個**: 多い、DTOの使用を検討
- **11個以上**: 多すぎる、即座に改善が必要

---

### 4.6 ExcessivePublicCount（過度に多いpublicメソッド/プロパティ）

**意味:** クラスのpublicメソッド/プロパティが多すぎる

**検出基準:**
- public要素の数が設定値（デフォルト: 45個）を超えている

**問題点:**
- クラスの責務が多すぎる可能性
- カプセル化が不十分

**対策:**
- クラスを分割
- 不要なpublicアクセスをprivateまたはprotectedに変更

---

### 4.7 TooManyFields（過度に多いフィールド）

**意味:** クラスのフィールド（プロパティ）が多すぎる

**検出基準:**
- フィールド数が設定値（デフォルト: 15個）を超えている

**問題点:**
- クラスの責務が多すぎる可能性
- データ構造が複雑

**対策:**
- 関連するフィールドを別クラス（ValueObject等）にまとめる
- クラスを分割

**目標値:**
- **0-10個**: 良好 ✅
- **11-15個**: やや多い
- **16個以上**: 多すぎる、分割を検討

---

### 4.8 TooManyMethods（過度に多いメソッド）

**意味:** クラスのメソッド数が多すぎる

**検出基準:**
- メソッド数が設定値（デフォルト: 25個）を超えている

**問題点:**
- クラスの責務が多すぎる可能性

**対策:**
- 関連するメソッドを別クラスに抽出
- 単一責任の原則を適用

**目標値:**
- **0-15個**: 良好 ✅
- **16-25個**: やや多い
- **26個以上**: 多すぎる、分割を検討

---

### 4.9 TooManyPublicMethods（過度に多いpublicメソッド）

**意味:** クラスのpublicメソッドが多すぎる

**検出基準:**
- publicメソッド数が設定値（デフォルト: 10個）を超えている

**問題点:**
- インターフェースが大きすぎる
- クラスの責務が多すぎる可能性

**対策:**
- インターフェース分離の原則（ISP）を適用
- クラスを分割

**目標値:**
- **0-7個**: 良好 ✅
- **8-10個**: やや多い
- **11個以上**: 多すぎる、分割を検討

---

### 4.10 ExcessiveClassComplexity（過度に複雑なクラス）

**意味:** クラス全体の循環的複雑度が高すぎる

**検出基準:**
- クラス全体の循環的複雑度の合計が設定値（デフォルト: 50）を超えている

**問題点:**
- クラス全体が複雑
- テストが困難

**対策:**
- メソッドの分割
- クラスの分割
- 複雑なロジックのシンプル化

**目標値:**
- **0-30**: 良好 ✅
- **31-50**: やや複雑
- **51以上**: 複雑すぎる、リファクタリング必要

---

## 5. Complexity Rules（複雑度関連ルール）

### 5.1 CyclomaticComplexity

Code Size Rules の [4.1 CyclomaticComplexity](#41-cyclomaticcomplexity循環的複雑度) を参照してください。

### 5.2 NPathComplexity

Code Size Rules の [4.2 NPathComplexity](#42-npathcomplexitynpath複雑度) を参照してください。

---

## 6. Design Rules（設計関連ルール）

設計上の問題を検出します。

### 6.1 CouplingBetweenObjects（オブジェクト間結合度）

**意味:** クラスが依存している他のクラスの数が多すぎる

**検出基準:**
- 依存クラス数が設定値（デフォルト: 13個）を超えている

**問題点:**
- 結合度が高すぎる
- テストが困難（多くのモックが必要）
- 変更の影響範囲が大きい

**対策:**
```php
// ❌ 悪い例: 依存が多すぎる
class BookController
{
    public function __construct(
        BookRepository $bookRepository,
        AuthorRepository $authorRepository,
        PublisherRepository $publisherRepository,
        CategoryRepository $categoryRepository,
        StockService $stockService,
        PriceCalculator $priceCalculator,
        ImageUploader $imageUploader,
        EmailNotifier $emailNotifier,
        Logger $logger,
        CacheManager $cacheManager,
        EventDispatcher $eventDispatcher,
        Validator $validator,
        Transformer $transformer,
        // ... さらに続く
    ) {
        // ...
    }
}

// ✅ 良い例: UseCaseに責務を分離
class BookController
{
    public function __construct(
        private CreateBookUseCase $createBookUseCase,
        private UpdateBookUseCase $updateBookUseCase,
        private DeleteBookUseCase $deleteBookUseCase,
    ) {
    }
}
```

**目標値:**
- **0-7個**: 良好 ✅
- **8-13個**: やや多い
- **14個以上**: 多すぎる、設計を見直す

---

### 6.2 ExitExpression（exit/die式）

**意味:** exit() または die() を使用している

**問題点:**
- テストが困難
- フレームワークのライフサイクルを破壊
- エラーハンドリングが適切に行えない

**対策:**
```php
// ❌ 悪い例
if ($error) {
    exit('エラーが発生しました');
}

// ✅ 良い例: 例外をスロー
if ($error) {
    throw new RuntimeException('エラーが発生しました');
}

// ✅ または: Laravelのabort()を使用
if ($error) {
    abort(500, 'エラーが発生しました');
}
```

---

### 6.3 GotoStatement（goto文）

**意味:** goto文を使用している

**問題点:**
- スパゲッティコードの原因
- 可読性が低下
- デバッグが困難

**対策:**
- 構造化プログラミングを使用（if, while, for等）
- 早期リターンを活用

---

## 7. Naming Rules（命名関連ルール）

命名規則に関する問題を検出します。

### 7.1 ShortVariable（短すぎる変数名）

**意味:** 変数名が短すぎて意味が不明確

**検出基準:**
- 変数名が3文字未満（プロジェクト設定により異なる）

**例外:**
- ループカウンタ: `$i`, `$j`, `$k`
- 一般的な略語: `$id`, `$db`

**対策:**
```php
// ❌ 悪い例
$b = new Book();
$u = $this->userRepository->find($id);

// ✅ 良い例
$book = new Book();
$user = $this->userRepository->find($id);
```

---

### 7.2 LongVariable（長すぎる変数名）

**意味:** 変数名が長すぎる

**検出基準:**
- 変数名が20文字を超えている（プロジェクト設定により異なる）

**対策:**
```php
// ❌ 悪い例
$bookRepositoryForAdminUserManagement = new BookRepository();

// ✅ 良い例
$adminBookRepository = new BookRepository();
```

---

### 7.3 ShortMethodName（短すぎるメソッド名）

**意味:** メソッド名が短すぎて意味が不明確

**検出基準:**
- メソッド名が3文字未満

**例外:**
- ゲッター: `id()`, `at()`
- マジックメソッド: `__construct()`, `__get()`

**対策:**
```php
// ❌ 悪い例
public function do() { }
public function go() { }

// ✅ 良い例
public function execute() { }
public function navigate() { }
```

---

### 7.4 ConstructorWithNameAsEnclosingClass（クラス名と同じコンストラクタ）

**意味:** PHP4スタイルのコンストラクタを使用している

**対策:**
```php
// ❌ 悪い例 (PHP4スタイル)
class Book
{
    public function Book() { }
}

// ✅ 良い例
class Book
{
    public function __construct() { }
}
```

---

### 7.5 ConstantNamingConventions（定数命名規則）

**意味:** 定数名が規則に従っていない

**対策:**
```php
// ❌ 悪い例
const maxValue = 100;

// ✅ 良い例
const MAX_VALUE = 100;
```

---

### 7.6 BooleanGetMethodName（真偽値を返すメソッドの命名）

**意味:** 真偽値を返すメソッド名が適切でない

**対策:**
```php
// ❌ 悪い例
public function available(): bool { }

// ✅ 良い例
public function isAvailable(): bool { }
public function hasStock(): bool { }
public function canBorrow(): bool { }
```

---

## 8. Unused Code Rules（未使用コード関連ルール）

未使用のコードを検出します。

### 8.1 UnusedPrivateField（未使用のprivateフィールド）

**意味:** 宣言されているが使用されていないprivateフィールド

**問題点:**
- デッドコード
- 保守性の低下
- コードが肥大化

**対策:**
- 未使用のフィールドを削除

---

### 8.2 UnusedPrivateMethod（未使用のprivateメソッド）

**意味:** 宣言されているが使用されていないprivateメソッド

**対策:**
- 未使用のメソッドを削除

---

### 8.3 UnusedFormalParameter（未使用の仮引数）

**意味:** メソッドのパラメータが使用されていない

**問題点:**
- インターフェースの設計ミスの可能性
- 混乱を招く

**対策:**
```php
// ❌ 悪い例
public function validate($attribute, $value, $parameters) {
    // $attribute を使用していない
    return strlen($value) > 0;
}

// ✅ 良い例1: パラメータを削除
public function validate($value, $parameters) {
    return strlen($value) > 0;
}

// ✅ 良い例2: インターフェース実装で削除できない場合はコメント
public function validate($attribute, $value, $parameters) {
    // $attribute は使用しない（インターフェース要件のため）
    return strlen($value) > 0;
}
```

---

### 8.4 UnusedLocalVariable（未使用のローカル変数）

**意味:** 宣言されているが使用されていないローカル変数

**対策:**
```php
// ❌ 悪い例
public function process() {
    $result = $this->calculate();
    $temp = 100; // 未使用

    return $result;
}

// ✅ 良い例
public function process() {
    $result = $this->calculate();

    return $result;
}
```

---

## 9. Clean Code Rules（クリーンコード関連ルール）

クリーンコードの原則に関する問題を検出します。

### 9.1 BooleanArgumentFlag（真偽値引数フラグ）

**意味:** メソッドが真偽値の引数を持っている

**問題点:**
- 単一責任の原則に違反
- メソッドが複数の動作をする
- 可読性が低下

**対策:**
```php
// ❌ 悪い例
public function create($name, $isAdmin) {
    if ($isAdmin) {
        // 管理者として作成
    } else {
        // 通常ユーザーとして作成
    }
}

// ✅ 良い例: メソッドを分割
public function createAsAdmin($name) {
    // 管理者として作成
}

public function createAsUser($name) {
    // 通常ユーザーとして作成
}

// ✅ または: Enumを使用（PHP 8.1+）
public function create($name, UserRole $role) {
    match ($role) {
        UserRole::Admin => $this->createAdmin($name),
        UserRole::User => $this->createUser($name),
    };
}
```

---

### 9.2 ElseExpression（else式）

**意味:** else句を使用している

**哲学:**
- 早期リターンを推奨
- ネストを減らして可読性を向上

**対策:**
```php
// ❌ 悪い例（else使用）
public function getDiscount($amount) {
    if ($amount > 10000) {
        return 0.1;
    } else {
        return 0;
    }
}

// ✅ 良い例（早期リターン）
public function getDiscount($amount) {
    if ($amount > 10000) {
        return 0.1;
    }

    return 0;
}
```

**注意:**
- このルールは厳格すぎる場合があるため、プロジェクト設定で除外している
- 可読性を優先して、適切にelseを使うことは許容される

---

### 9.3 StaticAccess（静的アクセス）

**意味:** 静的メソッドを使用している

**問題点:**
- テストが困難（モックが作りにくい）
- 依存性注入ができない
- グローバル状態を持つ可能性

**Laravel での注意:**
- Laravelのファサードは静的アクセスだが、一般的に許容される
- プロジェクト設定で除外している

**対策:**
```php
// ❌ 悪い例
public function process() {
    SomeClass::doSomething();
}

// ✅ 良い例: 依存性注入
public function __construct(
    private SomeClass $someClass
) {
}

public function process() {
    $this->someClass->doSomething();
}
```

---

## 10. まとめ：優先的に対応すべき項目

### 10.1 優先度別対応方針

#### 🔴 最優先（すぐに対応）

1. **Top (1) 優先度の問題**
   - CyclomaticComplexity（循環的複雑度 > 20）
   - ExcessiveMethodLength（メソッド長 > 100行）
   - ExcessiveClassLength（クラス長 > 1000行）

2. **High (2) 優先度の問題**
   - NPathComplexity（> 1000）
   - ExcessiveParameterList（> 10個）
   - CouplingBetweenObjects（> 15個）

**対応期限:** 次のスプリントまでに対応

---

#### 🟡 中優先（計画的に対応）

1. **Moderate (3) 優先度の問題**
   - TooManyPublicMethods（> 10個）
   - TooManyFields（> 15個）
   - ExcessiveClassComplexity（> 50）

2. **設計改善**
   - BooleanArgumentFlag
   - CouplingBetweenObjects（8-13個）

**対応期限:** 2-3スプリント以内に対応

---

#### 🟢 低優先（継続的に改善）

1. **コード整理**
   - UnusedFormalParameter
   - UnusedLocalVariable
   - UnusedPrivateMethod

2. **命名規則**
   - ShortMethodName
   - ShortVariable

**対応期限:** 新規コードでは発生させない、既存コードは継続的に改善

---

### 10.2 定期的な確認タイミング

#### 毎回のコミット前
- 新規追加したコードでTop/High優先度の問題が発生していないか確認
- `make phpmd` を実行して確認

#### プルリクエスト作成時
- 問題総数が増えていないか確認
- 新規追加した問題がある場合は理由を説明

#### スプリント終了時
- Top優先度の問題がゼロになっているか確認
- 問題総数が前スプリントより減っているか確認

#### リリース前
- Top優先度: 0件
- High優先度: 可能な限り0件に近づける
- 複雑度の高いクラスのテストカバレッジが十分か確認

---

### 10.3 良好な指標を維持するために

#### コーディング時の心がけ

1. **メソッドは短く保つ**
   - 1メソッド20行以内を目標
   - 1つのメソッドは1つのことだけをする

2. **複雑度を抑える**
   - ネストは3階層以内
   - 早期リターンを活用
   - 複雑な条件式は変数に抽出

3. **依存を少なく**
   - コンストラクタの引数は7個以内
   - 結合度を低く保つ
   - インターフェースに依存

4. **未使用コードは即削除**
   - 使っていないコードは迷わず削除
   - バージョン管理システムがあるので安心

---

#### コードレビュー時の確認事項

1. **新規追加クラスの確認**
   - 循環的複雑度は適切か
   - メソッド/クラスのサイズは適切か
   - 結合度は低いか

2. **変更による悪化の確認**
   - 複雑度が上がっていないか
   - メソッド/クラスが肥大化していないか

3. **PHPMD実行**
   - レビュー前に `make phpmd` を実行
   - 問題が増えている場合は理由を確認

---

### 10.4 参考: 理想的な目標値

| 指標 | 理想値 | 許容値 | 要改善 |
|------|--------|--------|--------|
| メソッド循環的複雑度 | < 5 | < 10 | ≥ 11 |
| NPath複雑度 | < 100 | < 200 | ≥ 201 |
| メソッド行数 | < 30行 | < 50行 | ≥ 51行 |
| クラス行数 | < 200行 | < 500行 | ≥ 501行 |
| パラメータ数 | < 4個 | < 7個 | ≥ 8個 |
| 結合度（依存クラス数） | < 7個 | < 13個 | ≥ 14個 |
| publicメソッド数 | < 7個 | < 10個 | ≥ 11個 |
| フィールド数 | < 10個 | < 15個 | ≥ 16個 |
| クラス全体の複雑度 | < 30 | < 50 | ≥ 51 |

---

## 参考資料

### PHPMD 公式ドキュメント
- https://phpmd.org/
- https://phpmd.org/rules/index.html

### 関連する設計原則
- **SOLID 原則**
  - 単一責任の原則（SRP）: 1クラス1責務
  - 開放閉鎖の原則（OCP）: 拡張に開き、修正に閉じる
  - リスコフの置換原則（LSP）: サブクラスは親クラスと置換可能
  - インターフェース分離の原則（ISP）: クライアントに不要なメソッドを強制しない
  - 依存性逆転の原則（DIP）: 抽象に依存、具象に依存しない

### メトリクス関連
- **循環的複雑度（Cyclomatic Complexity）**: Thomas J. McCabe, 1976
- **NPath複雑度**: Brian A. Nejmeh, 1988

### クリーンコード
- 「Clean Code」 Robert C. Martin (Uncle Bob)
- 「リファクタリング」 Martin Fowler

---

## 更新履歴

| 日付 | 内容 |
|------|------|
| 2026-01-12 | 初版作成 |
| 2026-01-12 | Detailsセクションの詳しい見方を追加 |
