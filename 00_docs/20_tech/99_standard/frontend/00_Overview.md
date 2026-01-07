# フロントエンド標準ドキュメント 概要

## 目的

本ディレクトリには、フロントエンド開発における設計標準・規約を定めたドキュメントを格納する。
開発者はこれらの標準に従い、一貫性のある高品質なコードベースを維持する。

---

## ドキュメント構成

| No. | ファイル名 | 内容 |
|-----|-----------|------|
| 01 | [01_CodingStandards.md](./01_CodingStandards.md) | コーディング規約 |
| 02 | [02_SecurityDesign.md](./02_SecurityDesign.md) | セキュリティ設計標準 |
| 03 | [03_Non-FunctionalRequirements.md](./03_Non-FunctionalRequirements.md) | 非機能要件 |

---

## 各ドキュメントの概要

### 01_CodingStandards.md - コーディング規約

React + TypeScript のコーディング規約を定める。SOLID 原則に基づく設計、コンポーネント設計、状態管理（TanStack Query / Zustand）、API 通信パターンなどを記載。

### 02_SecurityDesign.md - セキュリティ設計標準

フロントエンドにおけるセキュリティ対策の設計標準を定める。XSS 対策、CSRF 対策、認証情報の安全な管理、依存関係の脆弱性対策などを記載。

### 03_Non-FunctionalRequirements.md - 非機能要件

パフォーマンス、アクセシビリティ、保守性、ユーザビリティなどの非機能要件を定める。Core Web Vitals 目標、WCAG 準拠、テストカバレッジなどを記載。

---

## 技術スタック

| 項目 | 技術 |
|------|------|
| 言語 | TypeScript 5.x |
| UI ライブラリ | React 18.x |
| ビルドツール | Vite 6.x |
| 状態管理（サーバー） | TanStack Query 5.x |
| 状態管理（クライアント） | Zustand 5.x |
| ルーティング | React Router 7.x |
| HTTP クライアント | Axios 1.x |
| フォーム | React Hook Form 7.x |
| バリデーション | Zod 3.x |
| スタイリング | Tailwind CSS 3.x |

---

## 関連ドキュメント

- [フロントエンド アーキテクチャ設計](../../20_architecture/frontend/01_ArchitectureDesign.md) - Feature-based アーキテクチャ設計
- [システム概要](../../20_architecture/01_SystemOverview.md) - システム全体のアーキテクチャ
- [ADR（アーキテクチャ決定記録）](../../20_architecture/adr/) - 技術選定の決定記録
