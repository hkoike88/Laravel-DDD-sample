# バックエンド標準ドキュメント 概要

## 目的

本ディレクトリには、バックエンド開発における設計標準・規約を定めたドキュメントを格納する。
開発者はこれらの標準に従い、一貫性のある高品質なコードベースを維持する。

---

## ドキュメント構成

| No. | ファイル名 | 内容 |
|-----|-----------|------|
| 01 | [01_CodingStandards.md](./01_CodingStandards.md) | コーディング規約 |
| 02 | [02_SecurityDesign.md](./02_SecurityDesign.md) | セキュリティ設計標準 |
| 03 | [03_Non-FunctionalRequirements.md](./03_Non-FunctionalRequirements.md) | 非機能要件 |
| 04 | [04_LoggingDesign.md](./04_LoggingDesign.md) | ログ設計標準 |
| 05 | [05_ApiDesign.md](./05_ApiDesign.md) | API 設計標準 |
| 06 | [06_ValidationDesign.md](./06_ValidationDesign.md) | バリデーション設計標準 |
| 07 | [07_ErrorHandling.md](./07_ErrorHandling.md) | エラーハンドリング設計標準 |
| 08 | [08_DatabaseDesign.md](./08_DatabaseDesign.md) | データベース設計標準 |
| 09 | [09_TransactionDesign.md](./09_TransactionDesign.md) | トランザクション設計標準 |
| 10 | [10_TransactionConsistencyDesign.md](./10_TransactionConsistencyDesign.md) | トランザクション整合性保証設計 |
| 11 | [11_TransactionConsistencyChecklist.md](./11_TransactionConsistencyChecklist.md) | トランザクション整合性チェックリスト |
| 12 | [12_EventDrivenDesign.md](./12_EventDrivenDesign.md) | イベント駆動設計標準 |
| 13 | [13_ExternalIntegration.md](./13_ExternalIntegration.md) | 外部連携設計標準 |
| 14 | [14_BatchProcessing.md](./14_BatchProcessing.md) | バッチ処理設計標準 |

---

## 各ドキュメントの概要

### 01_CodingStandards.md - コーディング規約

PHP / Laravel のコーディング規約を定める。PSR-12 準拠、DDD アーキテクチャに基づく実装パターン、静的解析ツール（PHPStan / Larastan）の設定などを記載。

### 02_SecurityDesign.md - セキュリティ設計標準

バックエンドにおけるセキュリティ設計標準を定める。OWASP Top 10 を基準とし、認証・認可、入力検証、暗号化など Laravel のセキュリティ機能を活用した実装指針を記載。

### 03_Non-FunctionalRequirements.md - 非機能要件

パフォーマンス、可用性、スケーラビリティ、運用性などの非機能要件を定める。API レスポンス時間、スループット、稼働率などの目標値と実装方針を記載。

### 04_LoggingDesign.md - ログ設計標準

運用監視、障害調査、セキュリティ監査に対応できるログ基盤の設計標準を定める。ログチャンネル、ログレベル、構造化ログ、機密情報の取り扱いなどを記載。

### 05_ApiDesign.md - API 設計標準

RESTful 設計原則に基づく API 設計標準を定める。URL 命名規約、HTTP メソッドの使い分け、レスポンス形式、ページネーション、バージョニングなどを記載。

### 06_ValidationDesign.md - バリデーション設計標準

入力検証の設計標準を定める。FormRequest と ValueObject の責務分離、Presentation 層と Domain 層の多層検証、エラーメッセージの標準化などを記載。

### 07_ErrorHandling.md - エラーハンドリング設計標準

一貫性のあるエラー処理の設計標準を定める。エラーコード体系、例外クラスの階層化、エラーレスポンス形式、環境別のデバッグ情報制御などを記載。

### 08_DatabaseDesign.md - データベース設計標準

MySQL 8.0 を使用したデータベース設計標準を定める。命名規約、データ型選定、インデックス設計、マイグレーション規約、ULID 採用方針などを記載。

### 09_TransactionDesign.md - トランザクション設計標準

トランザクション設計標準を定める。競合状態（Race Condition）の防止、トランザクション境界の配置（UseCase 層）、楽観的ロック・悲観的ロックの使い分け、実装パターンとチェックリストを記載。

### 10_TransactionConsistencyDesign.md - トランザクション整合性保証設計

分散システム・非同期処理でのトランザクション整合性保証設計を定める。Saga パターン、2フェーズコミット（2PC）、補償トランザクション、アウトボックスパターン、冪等性設計、At-least-once 配信設計を記載。

### 11_TransactionConsistencyChecklist.md - トランザクション整合性チェックリスト

At-least-once 前提の再試行設計における設計要素選定チェックリスト。冪等性・Transactional Outbox・Saga の採用判断フロー、設計レビュー時の指摘テンプレートを記載。

### 12_EventDrivenDesign.md - イベント駆動設計標準

イベント駆動アーキテクチャ（EDA）の設計標準を定める。At-least-once 配信前提、イベント命名規則、Transactional Outbox、コンシューマの冪等性、Saga との連携パターンを記載。

### 13_ExternalIntegration.md - 外部連携設計標準

外部システム連携（外部 API 呼び出し、Webhook 受信等）の設計標準を定める。タイムアウト、リトライ、サーキットブレーカー等の障害耐性パターンを記載。

### 14_BatchProcessing.md - バッチ処理設計標準

バッチ処理（スケジュールタスク、キュージョブ、大量データ処理）の設計標準を定める。冪等性、再開可能性、監視可能性、リソース効率などを記載。

---

## 技術スタック

| 項目 | 技術 |
|------|------|
| 言語 | PHP 8.3+ |
| フレームワーク | Laravel 11.x |
| データベース | MySQL 8.0 |
| 静的解析 | PHPStan / Larastan（レベル 6 以上） |
| フォーマッター | Laravel Pint |
| テスト | Pest |
| 認証 | Laravel Sanctum |
| ID 生成 | ULID（symfony/uid） |

---

## 関連ドキュメント

- [アーキテクチャ設計](../../20_architecture/backend/) - バックエンドアーキテクチャ設計
- [ADR（アーキテクチャ決定記録）](../../10_architecture/adr/) - 技術選定の決定記録
