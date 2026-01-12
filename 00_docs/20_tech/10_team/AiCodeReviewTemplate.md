# AIコードレビューテンプレート

<!--
コーディングエージェント（AI）に対して一貫性・再現性・実務適合性の高いコードレビューを依頼するためのテンプレート
-->

---

## 1. レビューの目的

本レビューは以下を目的とします：

* 本番環境へのマージ可否の判断
* 品質・保守性・セキュリティ・性能の確保
* 技術的負債の早期発見
* チーム内レビュー観点の標準化
* パフォーマンス問題（特にN+1問題）の検出

---

## 2. レビュアーの前提条件

あなたは以下の役割を持つシニアソフトウェアエンジニアとしてレビューを行ってください。

* 実務経験豊富なシニアエンジニア
* セキュリティ・アーキテクチャ設計に精通
* 本番運用・障害対応の経験あり

### レビュー方針

* 本番環境にデプロイ可能かどうかの視点で評価すること
* 抽象論ではなく**具体的な問題・理由・修正案**を示すこと
* 可能な限り修正例コードを提示すること

---

## 3. プロジェクト情報

```text
- 言語 / フレームワーク:
  - バックエンド: PHP 8.3 / Laravel 11.x
  - フロントエンド: TypeScript / React 18.x
- アーキテクチャ:
  - バックエンド: DDD (Domain-Driven Design)
  - フロントエンド: Feature-based Architecture
- 実行環境: Docker (PHP-FPM, Nginx, MySQL 8.0)
- 対象ブランチ / PR: [記入してください]
- 重要視する品質特性: セキュリティ / 保守性 / 性能
```

### プロジェクト固有の設計標準

レビュー時は以下のドキュメントに準拠しているか確認してください：

#### システムアーキテクチャ

* `00_docs/20_tech/20_architecture/01_SystemOverview.md` - システム全体概要
* `00_docs/20_tech/20_architecture/backend/01_ArchitectureDesign.md` - バックエンドDDD設計
* `00_docs/20_tech/20_architecture/frontend/01_ArchitectureDesign.md` - フロントエンドFeature-based設計

#### バックエンド設計標準

* `00_docs/20_tech/99_standard/backend/01_CodingStandards.md` - コーディング規約（PSR-12、DDD）
* `00_docs/20_tech/99_standard/backend/02_SecurityDesign.md` - セキュリティ設計（OWASP Top 10）
* `00_docs/20_tech/99_standard/backend/05_ApiDesign.md` - API設計（RESTful原則）
* `00_docs/20_tech/99_standard/backend/06_ValidationDesign.md` - バリデーション設計
* `00_docs/20_tech/99_standard/backend/07_ErrorHandling.md` - エラーハンドリング
* `00_docs/20_tech/99_standard/backend/08_DatabaseDesign.md` - データベース設計（ULID、命名規約）
* `00_docs/20_tech/99_standard/backend/09_TransactionDesign.md` - トランザクション設計（ロック、競合防止）
* `00_docs/20_tech/99_standard/backend/10_TransactionConsistencyDesign.md` - トランザクション整合性保証（Saga、Outbox）
* `00_docs/20_tech/99_standard/backend/11_TransactionConsistencyChecklist.md` - トランザクション整合性チェックリスト
* `00_docs/20_tech/99_standard/backend/12_EventDrivenDesign.md` - イベント駆動設計（冪等性保証）

#### フロントエンド設計標準

* `00_docs/20_tech/99_standard/frontend/01_CodingStandards.md` - コーディング規約（React/TypeScript）
* `00_docs/20_tech/99_standard/frontend/02_SecurityDesign.md` - セキュリティ設計（XSS、CSRF対策）
* `00_docs/20_tech/99_standard/frontend/03_Non-FunctionalRequirements.md` - 非機能要件（Core Web Vitals）

#### セキュリティ標準

* `00_docs/20_tech/99_standard/security/01_PasswordPolicy.md` - パスワードポリシー（NIST SP 800-63B）
* `00_docs/20_tech/99_standard/security/02_SessionManagement.md` - セッション管理
* `00_docs/20_tech/99_standard/security/04_EncryptionPolicy.md` - 暗号化ポリシー

#### チーム標準

* `00_docs/20_tech/10_team/dod.md` - Definition of Done（完了の定義）
* `00_docs/20_tech/10_team/dor.md` - Definition of Ready（着手可能の定義）

---

## 4. レビュー観点

以下の観点で網羅的にレビューしてください。

### 4.1 コーディング規約

**バックエンド（PHP/Laravel）**

* PSR-12 準拠
* DDD パターン（Entity、ValueObject、Repository、DomainService、ApplicationService）
* Eloquent ORM の適切な使用
* 命名規約（キャメルケース、単数形/複数形）

**フロントエンド（TypeScript/React）**

* ESLint ルール準拠
* React Hooks の正しい使用
* 型定義の完全性（any の不使用）
* Feature-based ディレクトリ構成

### 4.2 セキュリティ

**OWASP Top 10 対応**

* **SQLインジェクション**: パラメータバインディング、Eloquent ORM 使用
* **XSS**: エスケープ処理（React の自動エスケープ、dangerouslySetInnerHTML 禁止）
* **CSRF**: Laravel Sanctum トークン検証
* **認証・認可**: ミドルウェア、ポリシー、ゲートの適切な使用
* **機密情報の露出**: `.env` 管理、ログへの機密情報出力禁止
* **不適切なアクセス制御**: ロールベース/ポリシーベース制御
* **セキュリティ設定ミス**: HTTPS強制、セキュアヘッダー設定

**パスワード・セッション管理**

* パスワードハッシュ化（bcrypt、最小12文字）
* セッションタイムアウト設定
* トークンの適切な管理（有効期限、リフレッシュ）

### 4.3 アーキテクチャ

**DDD 原則（バックエンド）**

* レイヤー分離（Presentation、Application、Domain、Infrastructure）
* 依存関係の方向（内側への単方向依存）
* 集約境界の明確化
* ドメインロジックの Domain 層への集約
* リポジトリパターンの適切な実装

**Feature-based Architecture（フロントエンド）**

* 機能単位でのディレクトリ分割
* 共通コンポーネントの適切な切り出し
* 状態管理の適切な分離（TanStack Query、Zustand）

### 4.4 パフォーマンス

**バックエンド**

* **N+1 問題の検出と対策**（最重要）
  * リレーション取得時の `with()` による Eager Loading の使用
  * ループ内でのクエリ実行の禁止
  * `debugbar` や SQL ログでのクエリ数確認
  * 特にリスト表示、詳細表示でのリレーション取得に注意
* 不要なクエリの削減
  * `select()` による必要カラムのみの取得
  * `exists()` / `count()` の適切な使用
* インデックスの適切な設定
  * 外部キー、検索条件によく使うカラムへのインデックス
  * 複合インデックスの検討
* トランザクション範囲の最適化
  * 必要最小限の範囲でのトランザクション開始
  * 外部API呼び出しをトランザクション外に配置

**フロントエンド**

* 不要な再レンダリングの防止（useMemo、useCallback）
* メモリリークの防止（useEffect クリーンアップ）
* バンドルサイズの最適化
* Core Web Vitals（LCP、FID、CLS）

### 4.5 トランザクション・整合性

* **悲観的ロック/楽観的ロック**: 競合状態の防止
* **冪等性保証**: 重複実行時の安全性
* **Saga パターン**: 分散トランザクションの補償処理
* **Outbox パターン**: イベント発行の確実性
* **整合性チェックリスト**: `11_TransactionConsistencyChecklist.md` 準拠

### 4.6 エラーハンドリング

* 例外の適切なキャッチと処理
* ユーザーフレンドリーなエラーメッセージ
* エラーログの適切な出力（スタックトレース、コンテキスト情報）
* 異常系テストの実装

### 4.7 テスト

* **単体テスト**: 新規コードのカバレッジ
* **境界値テスト**: 正常系・異常系・境界値
* **統合テスト**: API エンドポイント、DB アクセス
* **E2Eテスト**: 主要なユーザーシナリオ

### 4.8 ドキュメント

* **コードコメント**: 複雑なロジックへの説明（日本語）
* **型定義**: インターフェース、型エイリアスの完全性
* **API ドキュメント**: エンドポイント、リクエスト/レスポンス仕様
* **README**: セットアップ手順、環境変数の説明

---

## 5. レビュー結果の出力先

```text
98_reports/code-reviews/YYYY-MM-DD/
```

※ 実際の出力はMarkdown形式で、上記パスを冒頭に明記してください。

---

## 6. 出力フォーマット

以下の形式でレビュー結果を出力してください。

---

### 📊 総合評価

* 承認推奨度:
  * ✅ 承認推奨
  * ⚠️ 修正提案あり（マージ可能だが改善推奨）
  * ❌ 要修正（マージ前に必須対応）

* **総合コメント**: [全体的な評価コメント]

---

### ✅ 良い点

* [具体的な良い点]

---

### ❌ 重大な問題（重要度: S）

本番障害・セキュリティ事故・データ破壊につながる問題。

```text
- ID: CRIT-001
  ファイル: [ファイルパス]
  行番号: [行番号]
  問題: [具体的な問題内容]
  理由: [なぜ重大なのか]
  影響: [本番環境での影響]
  修正案: [具体的な修正方法]
  修正例コード:
  ```言語
  [修正例コード]
  ```
  参照: [該当する設計標準ドキュメント]
```

---

### ⚠️ 改善提案（重要度: A / B）

* **A**: 品質・保守性に大きな影響（早期対応推奨）
* **B**: 改善推奨レベル（技術的負債化の可能性）

```text
- ID: IMP-001
  重要度: A
  ファイル: [ファイルパス]
  行番号: [行番号]
  問題: [具体的な問題内容]
  理由: [なぜ改善が必要か]
  修正案: [具体的な修正方法]
  修正例コード:
  ```言語
  [修正例コード]
  ```
  参照: [該当する設計標準ドキュメント]
```

---

### 📝 その他の指摘・提案

* **技術的負債**: [将来的に問題になりそうな点]
* **将来的なリスク**: [スケーラビリティ、保守性の懸念]
* **設計改善案**: [より良い設計アプローチ]
* **パフォーマンス最適化**: [性能改善の余地]

---

## 7. 補足指示

* 抽象論ではなく、必ず**具体的なファイルパス・行番号・理由**を示すこと
* 指摘は簡潔かつ明確に、ただし理由と影響は詳細に説明
* **必ず修正例コードを提示**すること
* 該当する設計標準ドキュメントを参照として明記すること
* 不明点がある場合は仮定を明記した上でレビューすること
* セキュリティ・整合性に関わる問題は見逃さないこと

---

## 8. 利用例（依頼文サンプル）

### 例1: PRレビュー依頼

```text
このPRについて、`00_docs/20_tech/10_team/AiCodeReviewTemplate.md` に従ってコードレビューをお願いします。

対象ブランチ: feature/user-registration
重要視する品質特性: セキュリティ・トランザクション整合性
レビュー結果出力先: 98_reports/code-reviews/2026-01-12/user-registration-review.md
```

### 例2: 特定ファイルのレビュー依頼

```text
以下のファイルについて、DDD原則とトランザクション設計の観点からレビューしてください。
テンプレート: 00_docs/20_tech/10_team/AiCodeReviewTemplate.md

対象ファイル:
- backend/src/Domain/User/UserService.php
- backend/src/Application/User/RegisterUserUseCase.php

レビュー結果出力先: 98_reports/code-reviews/2026-01-12/user-service-review.md
```

---

## 9. レビュー前の確認事項

レビュー実施前に以下を確認してください：

- [ ] 対象ブランチ/PRが明確に指定されている
- [ ] プロジェクトの設計標準ドキュメントを参照できる
- [ ] 出力先ディレクトリが存在する（なければ作成）
- [ ] レビュー観点が明確（特に重要視する品質特性）

---

## 10. レビュー後の対応フロー

1. **重大な問題（S）がある場合**:
   - 必ず修正してから再レビュー
   - セキュリティ・整合性問題は最優先対応

2. **改善提案（A）がある場合**:
   - できるだけ早期に対応
   - 対応困難な場合は Issue 化して計画的に対応

3. **改善提案（B）がある場合**:
   - 技術的負債として Issue 化
   - 優先度を判断して計画的に対応

4. **その他の指摘**:
   - 将来のリファクタリング時に考慮
   - 設計改善のナレッジとして蓄積
