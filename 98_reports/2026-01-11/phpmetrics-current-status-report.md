# PHPMetrics 現状分析総評

**分析日時:** 2026-01-11 13:47:52
**PHPMetrics バージョン:** v2.9.1
**プロジェクト:** Laravel-DDD-sample

---

## エグゼクティブサマリー

本レポートは、PHPMetrics を用いたコード品質分析の結果をまとめたものです。

### 総合評価: **B+ (良好)**

プロジェクト全体として、コード品質は良好な状態にあります。特に以下の点が優れています：

✅ **優れている点:**
- 平均循環的複雑度が非常に低い（2.41）
- 予測バグ数が非常に少ない（0.05/クラス）
- クリティカルな違反が0件
- コメントが適切に記載されている（43-48%）

⚠️ **改善が必要な点:**
- 2件のエラーレベル違反
- 一部のクラスに責務の肥大化（God Object）
- パッケージ設計原則違反（SAP/SDP）

---

## 1. プロジェクト概要

### 1.1 基本情報

| 項目 | 値 |
|------|-----|
| 総コード行数 | 4,450行 |
| クラス数 | 80クラス |
| インターフェース数 | 3 (4%) |
| 平均クラスサイズ | 約56行/クラス |

### 1.2 アーキテクチャ

- **パターン:** DDD (Domain-Driven Design)
- **フレームワーク:** Laravel
- **構成:**
  - Domain層: Entity, ValueObject, Repository Interface, DomainService
  - Application層: UseCase, DTO, Repository Implementation
  - Presentation層: Controller, Request, Resource
  - Infrastructure層: EloquentModel, AuditLogger

---

## 2. 総合評価

### 2.1 評価サマリー

| カテゴリ | 評価 | スコア | コメント |
|----------|------|--------|----------|
| コード品質 | A | 90/100 | 複雑度が低く、バグ予測数も少ない |
| コード規模 | A | 95/100 | クラスサイズが適切 |
| 複雑度管理 | A+ | 98/100 | 非常に良好な複雑度 |
| OOP設計 | B | 75/100 | 一部のクラスで凝集度が低い |
| 結合度管理 | B+ | 82/100 | ドメイン層は安定、一部改善の余地 |
| ドキュメント | A | 90/100 | コメントが充実 |
| **総合** | **B+** | **85/100** | **全体的に良好** |

---

## 3. 詳細分析

### 3.1 コード品質（Violations）

#### 3.1.1 違反サマリー

| レベル | 件数 | 状態 |
|--------|------|------|
| **Criticals** | 0 | ✅ 良好 |
| **Errors** | 2 | ⚠️ 要対応 |
| **Warnings** | 19 | 🔵 継続的改善 |
| **Information** | 0 | - |
| **合計** | **21** | - |

#### 3.1.2 クラスレベル違反

**Blob / God Object（責務の肥大化）:**
1. `Packages\Domain\Staff\Application\Repositories\EloquentStaffRepository`
   - **問題:** 複数の責務を持つ肥大化したリポジトリ
   - **影響:** 保守性の低下、テストの困難さ
   - **優先度:** 高

2. `Packages\Domain\Book\Domain\Model\Book`
   - **問題:** ドメインモデルに多くのロジックが集中
   - **影響:** 単一責任の原則違反
   - **優先度:** 中

**Probably Bugged（バグの可能性）:**
1. `Packages\Domain\Book\Application\Repositories\EloquentBookRepository`
   - **問題:** 高い複雑度（Cyclomatic Complexity: 14）
   - **影響:** バグの混入リスク
   - **優先度:** 高

2. `Packages\Domain\Book\Domain\ValueObjects\ISBN`
   - **問題:** 高い複雑度（Cyclomatic Complexity: 17）
   - **影響:** 保守性の低下
   - **優先度:** 中

#### 3.1.3 パッケージレベル違反

**Stable Abstractions Principle (SAP) 違反: 13パッケージ**

主要な違反パッケージ:
- `Domain\Staff\Application\DTO\Auth`
- `Domain\Staff\Application\DTO\StaffAccount`
- `Domain\Staff\Domain\Exceptions`
- `Domain\Staff\Domain\Services`
- `Domain\Staff\Domain\Model`
- `Domain\Staff\Domain\ValueObjects`

**問題点:**
- 安定したパッケージに具象クラスが多く、抽象度が低い
- インターフェースの不足

**Stable Dependencies Principle (SDP) 違反: 6パッケージ**

主要な違反パッケージ:
- `Domain\Staff\Application\DTO\Auth`
- `Domain\Staff\Application\DTO\StaffAccount`
- `Domain\Staff\Domain\ValueObjects`
- `Domain\Staff\Domain\Repositories`
- `Domain\Book\Domain\Exceptions`

**問題点:**
- 安定したパッケージが不安定なパッケージに依存
- 循環依存のリスク

---

### 3.2 コード規模（Size & Volume）

#### 3.2.1 クラスサイズ分布

| サイズ範囲 | クラス数 | 割合 |
|------------|----------|------|
| 0-50行 | 52 | 65% |
| 51-100行 | 25 | 31% |
| 101-200行 | 3 | 4% |
| 201行以上 | 0 | 0% |

**評価:** ✅ 良好
- ほとんどのクラスが適切なサイズ（100行以下）
- 肥大化したクラスが少ない

#### 3.2.2 大きいクラス（LLOC > 100）

1. **ISBN** - 107行
   - ValueObjectとしてやや大きい
   - バリデーションロジックが多い

2. **BookStatus** - 105行
   - 状態遷移ロジックが含まれる
   - Enumパターンの実装

3. **EloquentBookRepository** - 95行
   - リポジトリとして適切なサイズ

**評価:** 🔵 許容範囲内だが、ISBN と BookStatus は分割を検討

#### 3.2.3 コメント状況

**Comment Weight 分布:**
- **30-50%:** 76クラス（適切）✅
- **20-30%:** 4クラス（やや少ない）
- **50%以上:** 0クラス

**評価:** ✅ 優良
- ほぼ全てのクラスで適切なコメント量
- ドキュメンテーションが充実

---

### 3.3 複雑度（Complexity）

#### 3.3.1 循環的複雑度

**平均クラス循環的複雑度: 2.41**

| 範囲 | クラス数 | 評価 |
|------|----------|------|
| 1-5 | 71 | ✅ シンプル |
| 6-10 | 7 | 🔵 やや複雑 |
| 11-20 | 2 | ⚠️ 複雑 |
| 21以上 | 0 | - |

**評価:** ✅ 非常に良好
- 平均値が非常に低く、シンプルなコード
- 複雑なクラスは少数

#### 3.3.2 複雑度が高いクラス

**WMC（重み付けメソッド数）上位:**

1. **ISBN** - WMC: 28, Class Cycl: 17
   - バリデーションロジックが複雑
   - リファクタリング推奨

2. **BookStatus** - WMC: 25, Class Cycl: 7
   - 状態遷移の分岐が多い
   - パターンの見直しを検討

3. **EloquentBookRepository** - WMC: 23, Class Cycl: 14
   - 検索条件の組み立てが複雑
   - Query Builderパターンの導入を検討

4. **UpdateStaffHandler** - WMC: 18, Class Cycl: 13
   - 更新ロジックの分岐が多い
   - 責務の分割を検討

**評価:** ⚠️ 上位4クラスは改善を推奨

#### 3.3.3 予測バグ数（Halstead Metrics）

**平均バグ数/クラス: 0.05**

**バグ数が多いクラス:**

1. **ISBN** - 0.43バグ
2. **EloquentBookRepository** - 0.35バグ
3. **StaffAccountController** - 0.32バグ
4. **UpdateStaffHandler** - 0.26バグ

**評価:** ✅ 全体的に良好だが、上位4クラスはテスト強化を推奨

#### 3.3.4 予測欠陥数（Kan Metrics）

**平均欠陥数/クラス: 0.23**

**欠陥数が多いクラス:**

1. **EloquentBookRepository** - 0.92
2. **ISBN** - 0.85
3. **UpdateStaffHandler** - 0.85

**評価:** 🔵 許容範囲内だが、コードレビュー強化を推奨

---

### 3.4 オブジェクト指向設計（OOP Metrics）

#### 3.4.1 LCOM（メソッドの凝集度の欠如）

**平均LCOM: 2.4**

| 範囲 | クラス数 | 評価 |
|------|----------|------|
| 0-2 | 49 | ✅ 高凝集 |
| 3-5 | 28 | 🔵 やや低凝集 |
| 6以上 | 3 | ⚠️ 低凝集 |

**低凝集なクラス（LCOM ≥ 6）:**

1. **SessionManagerService** - LCOM: 7
   - 複数の責務を持つ可能性
   - クラス分割を検討

2. **EloquentStaffRepository** - LCOM: 7
   - リポジトリパターンの見直し
   - Query Objectの導入を検討

3. **ISBN** - LCOM: 7
   - バリデーションロジックの分離を検討

**評価:** 🔵 概ね良好だが、上位3クラスは改善を推奨

#### 3.4.2 Difficulty（理解の難しさ）

**高Difficultyクラス:**

1. **ISBN** - 25.67
2. **EloquentBookRepository** - 19.96
3. **UpdateStaffHandler** - 13.28
4. **GetStaffListHandler** - 13.85

**評価:** ⚠️ これらのクラスは理解が困難
- コードのシンプル化を推奨
- 詳細なドキュメント追加を推奨

---

### 3.5 結合度（Coupling）

#### 3.5.1 Afferent Coupling（求心的結合度）

**多く依存されているクラス（AC ≥ 8）:**

1. **StaffId** - AC: 20
   - プロジェクトの中核的なValueObject
   - 変更時の影響範囲が非常に大きい

2. **StaffDomainException** - AC: 10
   - 例外の基底クラス
   - 安定している必要がある

3. **Email** - AC: 9
   - よく使用されるValueObject

4. **BookId** - AC: 8
   - プロジェクトの中核的なValueObject

**評価:** ✅ ドメイン層の中核クラスが高AC → 適切な設計

#### 3.5.2 Efferent Coupling（遠心的結合度）

**多くのクラスに依存しているクラス（EC ≥ 10）:**

1. **StaffAccountController** - EC: 17
   - Controllerとして多くの依存は自然

2. **BookController** - EC: 15
   - 同上

3. **UpdateStaffHandler** - EC: 13
   - UseCaseとして多くの依存
   - 依存の見直しを検討

4. **CreateStaffHandler** - EC: 12
   - 同上

5. **EloquentBookRepository** - EC: 11
   - Repositoryとして多くの依存

**評価:** 🔵 Application層/Presentation層が高EC → 許容範囲内

#### 3.5.3 Instability（不安定性）

**ドメイン層の主要クラス:**
- **StaffId** - Instability: 0.17（安定）✅
- **Email** - Instability: 0.31（安定）✅
- **StaffDomainException** - Instability: 0.09（非常に安定）✅

**アプリケーション層/プレゼンテーション層:**
- **StaffAccountController** - Instability: 1.0（不安定）✅
- **BookController** - Instability: 1.0（不安定）✅
- **UpdateStaffHandler** - Instability: 0.93（不安定）✅

**評価:** ✅ 優良
- ドメイン層は安定、Application/Presentation層は不安定
- DDD アーキテクチャとして理想的な構造

---

## 4. 主要な問題点と改善提案

### 4.1 🔴 最優先（即座に対応）

#### 問題1: エラーレベル違反 2件

**影響:** コード品質の低下、バグのリスク

**対応策:**
1. Violations ページでエラー詳細を確認
2. 該当コードを修正
3. ユニットテストを追加

**期限:** 1週間以内

---

#### 問題2: God Object（肥大化クラス）

**該当クラス:**
- `EloquentStaffRepository`
- `Book`

**影響:**
- 単一責任の原則違反
- テストが困難
- 保守性の低下

**対応策:**

**EloquentStaffRepository の改善:**
```php
// Before: 1つのリポジトリに全てのクエリロジック

// After: Query Objectパターンで分割
- StaffQueryBuilder（検索条件の構築）
- StaffPersistenceService（永続化処理）
- EloquentStaffRepository（薄いレイヤー）
```

**Book の改善:**
```php
// Before: ドメインモデルに多くのロジック

// After: ドメインサービスに責務を分離
- Book（状態とバリデーション）
- BookStatusTransitionService（状態遷移ロジック）
- BookBusinessRuleService（ビジネスルール）
```

**期限:** 2週間以内

---

#### 問題3: Probably Bugged（バグの可能性）

**該当クラス:**
- `EloquentBookRepository`（Cyclomatic Complexity: 14）
- `ISBN`（Cyclomatic Complexity: 17）

**影響:**
- バグの混入リスク
- 理解の困難さ

**対応策:**

**EloquentBookRepository:**
1. 複雑な検索ロジックをQuery Builderパターンで分離
2. ユニットテストのカバレッジを90%以上に引き上げ
3. 統合テストを追加

**ISBN:**
1. バリデーションロジックを個別のValidatorクラスに分離
2. 各バリデーションに対してユニットテストを追加
3. 早期リターンを活用して複雑度を下げる

**期限:** 2週間以内

---

### 4.2 🟡 中優先（計画的に対応）

#### 問題4: 高LCOM クラス

**該当クラス:**
- `SessionManagerService`（LCOM: 7）
- `EloquentStaffRepository`（LCOM: 7）
- `ISBN`（LCOM: 7）

**対応策:**
- メソッドの関連性を分析
- 関連性の低いメソッドを別クラスに分離
- 単一責任の原則を適用

**期限:** 1ヶ月以内

---

#### 問題5: 高複雑度クラス

**該当クラス:**
- `ISBN`（WMC: 28, Difficulty: 25.67）
- `BookStatus`（WMC: 25）
- `EloquentBookRepository`（WMC: 23, Difficulty: 19.96）

**対応策:**
- 複雑なメソッドを小さなメソッドに分割
- Strategy パターンや State パターンの導入を検討
- コメントとドキュメントの充実

**期限:** 1ヶ月以内

---

#### 問題6: パッケージ設計原則違反

**SAP違反:** 13パッケージ
**SDP違反:** 6パッケージ

**対応策:**

**短期（1-2ヶ月）:**
1. Repository インターフェースをDomain層に移動（SDP対応）
2. 主要なDomainServiceにインターフェースを追加（SAP対応）

**中期（3-6ヶ月）:**
1. DTOパッケージの依存関係を見直し
2. Exceptionパッケージの抽象化を検討

**長期（6ヶ月以上）:**
1. パッケージ構造の全体的な見直し
2. クリーンアーキテクチャへの段階的移行を検討

**期限:** 段階的に実施

---

### 4.3 🟢 低優先（継続的改善）

#### 問題7: Warnings の削減

**現状:** 19件

**対応策:**
- 毎スプリント3-5件ずつ削減
- コードレビュー時に新規Warningsを追加しない

**期限:** 3ヶ月で10件以下に削減

---

## 5. 優先対応ロードマップ

### Week 1-2（即座に対応）

- [ ] エラーレベル違反2件の修正
- [ ] `EloquentBookRepository` のテストカバレッジ向上
- [ ] `ISBN` のリファクタリング計画策定

### Week 3-4（高優先）

- [ ] `EloquentStaffRepository` のリファクタリング
- [ ] `Book` ドメインモデルの責務分離
- [ ] `ISBN` のバリデーション分離

### Month 2（中優先）

- [ ] `SessionManagerService` のクラス分割
- [ ] `BookStatus` の複雑度削減
- [ ] Repository インターフェースのDomain層への移動

### Month 3-6（継続的改善）

- [ ] Warnings を10件以下に削減
- [ ] 高LCOMクラスの改善
- [ ] パッケージ設計原則違反の段階的解消
- [ ] テストカバレッジの全体的な向上

---

## 6. コード品質維持のためのガイドライン

### 6.1 新規コード作成時

**必須チェック項目:**
- [ ] クラスサイズは100行以下か？
- [ ] メソッドサイズは20行以下か？
- [ ] 循環的複雑度は10以下か？
- [ ] LCOM は5以下か？
- [ ] 適切なコメントが記載されているか？

**推奨事項:**
- 単一責任の原則を守る
- 早期リターンを活用
- 深いネストを避ける（3階層以内）
- 依存性注入を活用

---

### 6.2 コードレビュー時

**チェック項目:**
- [ ] PHPMetrics の指標が悪化していないか？
- [ ] 新規Violationsが追加されていないか？
- [ ] テストが十分に書かれているか？
- [ ] コメントが適切に記載されているか？

---

### 6.3 定期メンテナンス

**毎週:**
- PHPMetrics を実行
- Violations の増減を確認

**毎スプリント:**
- Violations の総数が減っているか確認
- 平均複雑度が増えていないか確認
- テストカバレッジレポートと照合

**四半期ごと:**
- パッケージ構造の見直し
- アーキテクチャの健全性確認
- 技術的負債の棚卸し

---

## 7. まとめ

### 7.1 プロジェクトの健全性

**総合評価: B+ (85/100)**

Laravel-DDD-sampleプロジェクトは、全体的に **良好なコード品質** を維持しています。

**強み:**
- ✅ 非常に低い循環的複雑度（2.41）
- ✅ 予測バグ数が少ない（0.05/クラス）
- ✅ 適切なクラスサイズ（平均56行）
- ✅ 充実したドキュメント（Comment Weight: 43-48%）
- ✅ 適切な依存関係設計（ドメイン層が安定）

**改善点:**
- ⚠️ 2件のエラーレベル違反
- ⚠️ 一部のクラスに責務の肥大化
- ⚠️ パッケージ設計原則違反

---

### 7.2 今後の方針

#### 短期目標（1-2ヶ月）
1. エラーレベル違反の完全解消
2. God Objectの解消（EloquentStaffRepository、Book）
3. 複雑度の高いクラスのリファクタリング（ISBN、EloquentBookRepository）

#### 中期目標（3-6ヶ月）
1. Violations を10件以下に削減
2. 全クラスの循環的複雑度を10以下に維持
3. パッケージ設計原則違反の50%削減

#### 長期目標（6ヶ月以上）
1. 総合評価をA（90点以上）に向上
2. テストカバレッジ90%以上を達成
3. クリーンアーキテクチャへの完全移行を検討

---

### 7.3 最終所見

このプロジェクトは、DDDアーキテクチャを採用し、適切なレイヤー分割がなされています。
コードの複雑度は非常に低く、将来の拡張性も考慮された設計となっています。

いくつかの改善点はありますが、**段階的に対応することで、より高品質なコードベースを維持できる** と考えます。

特に、ドメイン層の安定性が高く、ビジネスロジックがしっかりと保護されている点は、
長期的なメンテナンス性の観点から非常に優れています。

今後も定期的にPHPMetricsによる分析を実施し、品質の維持・向上に努めることを推奨します。

---

## 付録

### A. 分析データ詳細

**分析対象:**
- ディレクトリ: `backend/`
- 言語: PHP
- フレームワーク: Laravel
- アーキテクチャ: DDD

**除外パス:**
- `vendor/`
- `tests/`
- `storage/`
- `bootstrap/cache/`

### B. 参考資料

- PHPMetrics 公式ドキュメント: https://phpmetrics.github.io/website/
- プロジェクト設計標準: `00_docs/20_tech/99_standard/backend/`
- アーキテクチャ設計: `00_docs/20_tech/20_architecture/backend/`

### C. 次回分析予定

- **次回実施日:** 2週間後（2026-01-25）
- **目標:** エラー0件、Warnings 15件以下

---

**レポート作成者:** Claude (AI Assistant)
**レポート作成日:** 2026-01-11
