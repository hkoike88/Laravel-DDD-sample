# テストカバレッジレポート

**生成日時**: 2026-01-12 08:51:46 UTC
**測定ツール**: php-code-coverage 11.0.11
**PHP バージョン**: PHP 8.2.30
**テストフレームワーク**: PHPUnit 11.5.33
**カバレッジドライバー**: PCOV 1.0.12

---

## エグゼクティブサマリー

本レポートは、Laravel-DDD-sample プロジェクトのテストカバレッジ状況を報告します。

**全体カバレッジ**: 🟡 **54.61%** (Medium)

**評価**:
- 🟢 **改善点**: PCOV 設定修正により、packages/ ディレクトリのカバレッジが 0% → 59.68% に大幅改善
- 🟡 **注意点**: 全体で 54.61% と中程度のカバレッジ。目標達成には更なるテスト追加が必要
- 🔴 **課題**: app/ ディレクトリのカバレッジが 45.20% と低い

---

## 全体カバレッジサマリー

### コードカバレッジ全体

| 指標 | カバー率 | カバー済 / 総数 | レベル |
|------|----------|----------------|--------|
| **Lines (行)** | **54.61%** | 1055 / 1932 | 🟡 Medium |
| **Functions (関数・メソッド)** | **59.52%** | 222 / 373 | 🟡 Medium |
| **Classes (クラス・トレイト)** | **43.30%** | 42 / 97 | 🔴 Low |

### カバレッジレベルの定義

- 🟢 **High (高)**: 90% 〜 100%
- 🟡 **Medium (中)**: 50% 〜 90%
- 🔴 **Low (低)**: 0% 〜 50%

---

## ディレクトリ別カバレッジ詳細

### 1. app/ ディレクトリ

**パス**: `/var/www/html/app`
**総合評価**: 🔴 Low (45.20%)

| 指標 | カバー率 | カバー済 / 総数 | レベル |
|------|----------|----------------|--------|
| Lines | **45.20%** | 306 / 677 | 🔴 Low |
| Functions | **33.78%** | 25 / 74 | 🔴 Low |
| Classes | **21.05%** | 4 / 19 | 🔴 Low |

**分析**:
- Presentation 層（Controller、FormRequest 等）が含まれる
- 目標カバレッジ: **70%以上**
- 現状: **45.20%** (ギャップ: -24.80ポイント)
- **改善が必要**: Feature テストの追加が推奨される

**主な構成要素**:
- Controllers
- HTTP Middleware
- FormRequests
- Exceptions Handler

---

### 2. packages/ ディレクトリ

**パス**: `/var/www/html/packages`
**総合評価**: 🟡 Medium (59.68%)

| 指標 | カバー率 | カバー済 / 総数 | レベル |
|------|----------|----------------|--------|
| Lines | **59.68%** | 749 / 1255 | 🟡 Medium |
| Functions | **65.89%** | 197 / 299 | 🟡 Medium |
| Classes | **48.72%** | 38 / 78 | 🔴 Low |

**分析**:
- DDD 実装の中核（Domain、Application、Infrastructure 層）
- PCOV 設定修正により **0% → 59.68%** に大幅改善 ✅
- 目標カバレッジ:
  - Domain 層: **90%以上**
  - Application 層: **85%以上**
- 現状: **59.68%** (Domain 層目標比 -30.32ポイント)
- **継続的改善が必要**: Unit テストの追加が推奨される

**主な構成要素**:
- Domain/Book (蔵書ドメイン)
- Domain/Staff (職員ドメイン)
- Application/UseCases
- Infrastructure/Repositories
- Presentation/HTTP/Resources

---

## レイヤー別カバレッジ分析

### DDD アーキテクチャによる分類

| レイヤー | ディレクトリ | 目標 | 現状 (Lines) | 現状 (Functions) | ギャップ | 評価 |
|---------|-------------|------|--------------|------------------|----------|------|
| **Presentation 層** | app/ | 70%以上 | 45.20% | 33.78% | -24.80pt | 🔴 |
| **Application 層** | packages/*/Application | 85%以上 | - | 65.89% | -19.11pt | 🟡 |
| **Domain 層** | packages/*/Domain | 90%以上 | 59.68% | - | -30.32pt | 🟡 |
| **Infrastructure 層** | packages/*/Infrastructure | 50%以上 | - | - | - | - |

**注**: packages/ 全体の値を使用。より詳細な分析には各サブディレクトリの個別レポート参照が必要。

---

## テスト実行結果

### テスト統計

- **総テスト数**: 164 passed
- **総アサーション数**: 1,419 assertions
- **実行時間**: 約 9.12 秒
- **警告**: 211 warnings (doc-comment メタデータ非推奨)

### テストスイート構成

1. **Unit Tests** (単体テスト)
   - Domain/Book モデルテスト
   - Domain/Staff モデルテスト
   - ValueObject テスト
   - DTO テスト
   - UseCase テスト

2. **Feature Tests** (機能テスト)
   - 認証テスト (Auth)
   - 認可テスト (Authorization)
   - 蔵書検索テスト
   - セッション管理テスト
   - セキュリティ設定テスト

3. **Integration Tests** (統合テスト)
   - Repository テスト

---

## カバレッジ向上の推奨事項

### 🔴 優先度: 高 - Presentation 層 (app/)

**現状**: 45.20% / 目標: 70%以上
**ギャップ**: -24.80 ポイント

**推奨アクション**:
1. **Controller のテストケース追加**
   - 正常系のテストは存在
   - 異常系（バリデーションエラー、認証エラー等）のテスト追加
   - エッジケースのテスト追加

2. **Middleware のテスト追加**
   - セッションタイムアウト
   - 認証・認可
   - CORS

3. **FormRequest のテスト追加**
   - バリデーションルールの網羅的テスト
   - カスタムバリデーションのテスト

**期待される効果**: +25 〜 30 ポイント向上

---

### 🟡 優先度: 中 - Domain 層 (packages/Domain)

**現状**: 59.68% / 目標: 90%以上
**ギャップ**: -30.32 ポイント

**推奨アクション**:
1. **Domain Model のテストケース追加**
   - ビジネスロジックのエッジケース
   - 状態遷移の異常系
   - 不変条件（Invariant）の検証

2. **ValueObject のテスト追加**
   - バリデーションの境界値テスト
   - フォーマット変換のテスト

3. **Domain Service のテスト追加**
   - 複雑なビジネスロジックのテスト

**期待される効果**: +30 〜 35 ポイント向上

---

### 🟡 優先度: 中 - Application 層 (packages/Application)

**現状**: 65.89% (Functions) / 目標: 85%以上
**ギャップ**: -19.11 ポイント

**推奨アクション**:
1. **UseCase のテストケース追加**
   - 異常系（例外ハンドリング）
   - トランザクション境界のテスト
   - 複数 Repository 呼び出しのテスト

2. **DTO のテスト追加**
   - 変換ロジックのテスト
   - バリデーションのテスト

**期待される効果**: +20 ポイント向上

---

### 🟢 優先度: 低 - Infrastructure 層

**推奨アクション**:
1. **Repository 実装のテスト追加**
   - クエリビルディングのテスト
   - トランザクション処理のテスト
   - データマッピングのテスト

**注**: 現在の Integration Tests で一部カバーされている可能性あり

---

## カバレッジの技術的課題

### 1. PCOV 設定問題（解決済み）✅

**問題**: 初回レポート生成時、packages/ ディレクトリのカバレッジが 0%

**原因**: `pcov.directory=/var/www/html/app` となっており、packages が除外されていた

**解決策**: `pcov.directory=/var/www/html` に変更

**結果**: packages/ カバレッジが **0% → 59.68%** に改善

---

### 2. カバレッジ除外設定

以下のディレクトリは phpunit.xml でカバレッジ計測から除外されています:

1. **EloquentModels** (`packages/*/Infrastructure/EloquentModels`)
   - 理由: ビジネスロジックを含まない、DB とのマッピングのみ

2. **API Resources** (`packages/*/Presentation/HTTP/Resources`)
   - 理由: シンプルなデータ変換のみ

**妥当性**: ✅ 適切。これらのコンポーネントは薄いラッパーであり、カバレッジ計測の優先度は低い

---

### 3. PHPUnit 警告 (211 warnings)

**警告内容**:
```
Metadata found in doc-comment for method XXX.
Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12.
```

**影響**: 現時点では動作に影響なし

**推奨対応**: PHPUnit 12 リリース前に、doc-comment から Attribute ベースのメタデータに移行

**優先度**: 🟢 低（将来対応）

---

## カバレッジ目標の設定

### 短期目標 (1-2週間)

| レイヤー | 現状 | 短期目標 | 必要な改善 |
|---------|------|----------|------------|
| Presentation | 45.20% | 60% | +14.80pt |
| Application | 65.89% | 75% | +9.11pt |
| Domain | 59.68% | 75% | +15.32pt |

**合計**: 約 40 ポイント改善

**必要なテストケース**: 推定 50-80 ケース

---

### 中期目標 (1ヶ月)

| レイヤー | 現状 | 中期目標 | 必要な改善 |
|---------|------|----------|------------|
| Presentation | 45.20% | 70% | +24.80pt |
| Application | 65.89% | 85% | +19.11pt |
| Domain | 59.68% | 90% | +30.32pt |

**合計**: 約 74 ポイント改善

**必要なテストケース**: 推定 100-150 ケース

---

### 長期目標 (3ヶ月)

**全体カバレッジ**: 80%以上

**ブレークダウン**:
- Presentation: 70%
- Application: 85%
- Domain: 90%
- Infrastructure: 50%

---

## CI/CD 統合の推奨

### GitHub Actions 設定例

```yaml
name: Tests with Coverage

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP with PCOV
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: pcov

      - name: Install Dependencies
        run: composer install

      - name: Run Tests with Coverage
        run: ./vendor/bin/pest --coverage --min=50 --coverage-clover=coverage.xml

      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          files: ./coverage.xml
```

**推奨設定**:
- 最低カバレッジ: 50%（現在達成済み）
- 段階的に引き上げ: 50% → 60% → 70% → 80%

---

## カバレッジレポートの活用方法

### 1. HTML レポートの確認

```bash
# カバレッジレポート生成
make coverage-html

# ブラウザで確認
open backend/build/coverage/index.html  # macOS
xdg-open backend/build/coverage/index.html  # Linux
```

**レポートの見方**:
- 🟢 緑の行: テストでカバーされている
- 🔴 赤の行: テストでカバーされていない
- 🟡 黄色の行: 部分的にカバーされている

---

### 2. ターミナルでのサマリー確認

```bash
# シンプルなサマリー表示
make coverage

# 詳細表示（ファイル別）
make coverage-report
```

---

### 3. カバレッジ低下の防止

**推奨フロー**:
1. Pull Request 作成時に自動でカバレッジチェック
2. カバレッジが低下する場合は警告
3. 最低カバレッジ率を下回る場合は CI/CD 失敗

---

## メトリクス・統計

### コードベース統計

| 項目 | 値 |
|------|-----|
| 総行数 | 1,932 行 |
| 総関数・メソッド数 | 373 個 |
| 総クラス・トレイト数 | 97 個 |
| app/ 行数 | 677 行 (35.0%) |
| packages/ 行数 | 1,255 行 (65.0%) |

### カバレッジ統計

| 項目 | 値 |
|------|-----|
| カバー済み行数 | 1,055 行 |
| 未カバー行数 | 877 行 |
| カバー済み関数 | 222 個 |
| 未カバー関数 | 151 個 |
| カバー済みクラス | 42 個 |
| 未カバークラス | 55 個 |

### テスト統計

| 項目 | 値 |
|------|-----|
| テストファイル数 | 推定 30-40 ファイル |
| テストケース数 | 164 ケース |
| アサーション数 | 1,419 個 |
| 平均アサーション数/ケース | 8.7 個 |
| 実行時間 | 9.12 秒 |

---

## 結論と次のステップ

### 総合評価

**現在の状況**: 🟡 **Medium (54.61%)**

**強み**:
- ✅ PCOV 環境が正しく構築され、packages/ ディレクトリのカバレッジ測定が可能
- ✅ Domain 層のコアロジック（Model、ValueObject）に Unit テストが存在
- ✅ Feature テストで主要な機能がカバーされている

**課題**:
- ❌ Presentation 層のカバレッジが低い (45.20%)
- ❌ Domain 層が目標 (90%) に達していない (59.68%)
- ❌ Application 層が目標 (85%) に達していない (65.89%)

---

### アクションプラン

#### Phase 1: 短期改善 (1-2週間)

1. **Presentation 層の強化**
   - Controller の異常系テスト追加
   - FormRequest のバリデーションテスト追加
   - 目標: 45% → 60%

2. **Domain 層の補強**
   - エッジケースのテスト追加
   - 状態遷移の異常系テスト追加
   - 目標: 60% → 75%

**期待される成果**: 全体カバレッジ 54% → 65%

---

#### Phase 2: 中期改善 (1ヶ月)

1. **Application 層の網羅**
   - UseCase の異常系テスト追加
   - トランザクション境界のテスト追加
   - 目標: 66% → 85%

2. **Domain 層の完成**
   - ビジネスロジックの完全網羅
   - 目標: 75% → 90%

3. **Presentation 層の完成**
   - Middleware のテスト追加
   - 目標: 60% → 70%

**期待される成果**: 全体カバレッジ 65% → 80%

---

#### Phase 3: CI/CD 統合 (継続)

1. **自動化の実装**
   - GitHub Actions でカバレッジチェック
   - Codecov 連携

2. **カバレッジの維持**
   - Pull Request 時のカバレッジチェック
   - 最低カバレッジ率の段階的引き上げ

---

## 添付資料

### カバレッジレポートの場所

- **HTML レポート**: `backend/build/coverage/index.html`
- **Dashboard**: `backend/build/coverage/dashboard.html`
- **app/ 詳細**: `backend/build/coverage/app/index.html`
- **packages/ 詳細**: `backend/build/coverage/packages/index.html`

### 関連ドキュメント

- **カバレッジ取得ガイド**: `backend/docs/testing-coverage.md`
- **コーディング規約**: `00_docs/20_tech/99_standard/backend/01_CodingStandards.md`
- **PCOV 環境構築レポート**: `99_reviews/COMPLETION-003_PCOV-Coverage-Setup.md`

---

**レポート生成日**: 2026-01-12
**次回レポート予定**: カバレッジ改善実施後（推奨: 1週間後）
