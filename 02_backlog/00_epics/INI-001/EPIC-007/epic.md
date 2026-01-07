# EPIC-007: セキュリティ対策準備

最終更新: 2025-12-26

---

## 概要

開発環境およびアプリケーションに対して、セキュリティ標準に基づいたセキュリティ対策を準備・実装する。パスワードポリシー、セッション管理、暗号化設定、脆弱性スキャン体制を整備し、安全な開発・運用基盤を構築する。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| イニシアチブ | [INI-001: 認証・利用者管理基盤](../../../../01_vision/initiatives/INI-001/charter.md) |
| Use Case | [UC-001-007: セキュリティ対策準備](../../../../01_vision/initiatives/INI-001/usecases/UC-001-007_セキュリティ対策準備.md) |
| 優先度 | Must |
| ステータス | Planned |

---

## ビジネス価値

セキュリティ標準に基づいた対策を事前に準備することで、脆弱性の早期発見と対応が可能になる。NIST SP 800-63B、OWASP ガイドラインに準拠した実装により、セキュリティインシデントのリスクを低減する。

---

## 受け入れ条件

1. パスワードポリシーが実装されていること（12文字以上、複雑性要件、漏洩チェック）
2. セッション管理が適切に設定されていること（アイドル30分、絶対8時間タイムアウト）
3. 同時ログイン制御が実装されていること（最大3台）
4. 暗号化設定が標準に準拠していること（bcrypt cost=12 または Argon2id）
5. セキュリティスキャンが CI/CD で自動実行されること
6. 既知の Critical/High 脆弱性が解消されていること
7. セキュリティ関連のログが適切に記録されること

---

## 参照セキュリティ標準

| No. | ドキュメント | 内容 |
|:---:|-------------|------|
| 01 | [01_PasswordPolicy.md](../../../../00_docs/20_tech/99_standard/security/01_PasswordPolicy.md) | パスワードポリシー |
| 02 | [02_SessionManagement.md](../../../../00_docs/20_tech/99_standard/security/02_SessionManagement.md) | セッション管理ポリシー |
| 03 | [03_DataClassification.md](../../../../00_docs/20_tech/99_standard/security/03_DataClassification.md) | データ分類・保護ポリシー |
| 04 | [04_EncryptionPolicy.md](../../../../00_docs/20_tech/99_standard/security/04_EncryptionPolicy.md) | 暗号化ポリシー |
| 05 | [05_IncidentResponse.md](../../../../00_docs/20_tech/99_standard/security/05_IncidentResponse.md) | インシデント対応手順 |
| 06 | [06_VulnerabilityManagement.md](../../../../00_docs/20_tech/99_standard/security/06_VulnerabilityManagement.md) | 脆弱性管理プロセス |
| 07 | [07_ThirdPartySecurity.md](../../../../00_docs/20_tech/99_standard/security/07_ThirdPartySecurity.md) | サードパーティセキュリティ |
| 08 | [08_SecurityScanning.md](../../../../00_docs/20_tech/99_standard/security/08_SecurityScanning.md) | セキュリティスキャンガイド |

---

## User Story 一覧

| ID | Story 名 | ポイント | 優先度 | ステータス |
|----|----------|---------|--------|----------|
| [ST-001](./stories/ST-001_パスワードポリシー実装.md) | パスワードポリシーの実装 | 3 | Must | Planned |
| [ST-002](./stories/ST-002_セッション管理設定.md) | セッション管理の設定 | 3 | Must | Planned |
| [ST-003](./stories/ST-003_暗号化設定確認.md) | 暗号化設定の確認・調整 | 2 | Must | Planned |
| [ST-004](./stories/ST-004_セキュリティスキャン設定.md) | セキュリティスキャンの設定 | 3 | Must | Planned |
| [ST-005](./stories/ST-005_依存関係脆弱性対応.md) | 依存関係の脆弱性対応 | 2 | Should | Planned |
| [ST-006](./stories/ST-006_セキュリティログ設定.md) | セキュリティログの設定 | 2 | Should | Planned |

---

## 成果物

| 成果物 | 配置場所 | 説明 |
|--------|---------|------|
| パスワードバリデーション | backend/app/Providers/AuthServiceProvider.php | Password::defaults() 設定 |
| セッション設定 | backend/config/session.php | タイムアウト、Cookie 設定 |
| ハッシュ設定 | backend/config/hashing.php | bcrypt/Argon2id 設定 |
| セキュリティスキャン設定 | Makefile | スキャンコマンド定義 |
| GitHub Actions | .github/workflows/security.yml | CI/CD セキュリティスキャン |
| スキャンレポート | reports/security/ | スキャン結果出力先 |

---

## 技術仕様

### パスワードポリシー

| 項目 | 設定値 |
|------|--------|
| 最小文字数 | 12文字 |
| 英大文字 | 1文字以上必須 |
| 英小文字 | 1文字以上必須 |
| 数字 | 1文字以上必須 |
| 記号 | 1文字以上必須 |
| 漏洩チェック | Have I Been Pwned API |
| 履歴 | 過去5世代の再利用禁止 |

### セッション管理

| 項目 | 設定値 |
|------|--------|
| アイドルタイムアウト | 30分 |
| 絶対タイムアウト | 8時間 |
| 同時ログイン数 | 最大3台（管理者は1台） |
| Cookie Secure | true |
| Cookie HttpOnly | true |
| Cookie SameSite | Lax |

### 暗号化設定

| 項目 | 設定値 |
|------|--------|
| パスワードハッシュ | bcrypt (cost=12) |
| 代替アルゴリズム | Argon2id |
| セッション暗号化 | 有効 |
| 通信暗号化 | TLS 1.2以上 |

### セキュリティスキャン

| ツール | 対象 | 実行タイミング |
|-------|------|---------------|
| composer audit | PHP 依存関係 | CI/CD 毎 |
| npm audit | JS 依存関係 | CI/CD 毎 |
| PHPStan/Larastan | PHP コード | CI/CD 毎 |
| OWASP ZAP | 実行中アプリ | 週次 |

---

## 依存関係

### 前提条件

| イニシアチブ | Epic ID | Epic 名 | 関係 |
|-------------|---------|---------|------|
| INI-000 | EPIC-001〜004 | 開発環境構築 | 完了していること |
| INI-001 | EPIC-001 | 職員ログイン機能 | 認証機能が完了していること |
| INI-001 | EPIC-006 | 職員ログアウト機能 | 認証機能が完了していること |

### 後続タスク

なし（本 Epic でセキュリティ対策準備は完了）

---

## リスクと対策

| リスク | 影響 | 対策 |
|--------|------|------|
| パスワード漏洩 | アカウント不正アクセス | Have I Been Pwned API による漏洩チェック |
| セッションハイジャック | なりすまし攻撃 | HttpOnly Cookie、セッションID再生成 |
| 脆弱な依存関係 | システム侵害 | CI/CD での自動脆弱性スキャン |
| 暗号化の不備 | データ漏洩 | TLS 1.2以上、適切なハッシュアルゴリズム |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
