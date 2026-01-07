# Research: フロントエンド初期設定

**Date**: 2025-12-23
**Feature**: 004-frontend-setup
**Status**: Complete

## Overview

このドキュメントは、フロントエンド初期設定に関する技術調査と決定事項を記録します。

## Research Decisions

### R-001: Vite プロジェクト作成方法

**Decision**: `npm create vite@latest` を使用して React + TypeScript テンプレートでプロジェクトを作成

**Rationale**:
- Vite 公式の推奨方法
- TypeScript + React テンプレートが用意されている
- 最新バージョンの依存関係が自動的に設定される

**Alternatives Considered**:
- Create React App → 開発終了、Vite が推奨
- 手動セットアップ → 時間がかかる、設定ミスのリスク

---

### R-002: Feature-based ディレクトリ構成

**Decision**: EPIC-003 で定義された構成を採用（app/, pages/, features/, components/, hooks/, lib/, types/）

**Rationale**:
- 機能ごとにコードを整理でき、スケーラブル
- チーム開発での衝突を減らす
- 単一責任の原則に従う

**Alternatives Considered**:
- レイヤーベース構成（components/, services/, utils/） → 機能間の依存が分かりにくい
- Atomic Design → UI コンポーネントには適しているが、ビジネスロジックの整理には不向き

---

### R-003: ESLint 設定方式

**Decision**: ESLint Flat Config（eslint.config.js）を使用

**Rationale**:
- ESLint 9.x 以降の推奨方式
- .eslintrc 形式は非推奨
- TypeScript + React プラグインとの互換性が向上

**Alternatives Considered**:
- .eslintrc.json → レガシー形式、将来的に非推奨
- .eslintrc.js → Flat Config への移行が必要になる

---

### R-004: Tailwind CSS バージョンと設定

**Decision**: Tailwind CSS 3.x を PostCSS プラグインとして設定

**Rationale**:
- Vite との親和性が高い
- PostCSS 経由でビルドパイプラインに統合
- content パスで自動的に未使用クラスを除去（PurgeCSS 不要）

**Alternatives Considered**:
- CSS-in-JS（styled-components, Emotion） → ランタイムコスト、バンドルサイズ増加
- SCSS/SASS → ユーティリティファーストの利点を活かせない

---

### R-005: パスエイリアス設定

**Decision**: `@/` を `src/` にマッピング（tsconfig.json と vite.config.ts の両方で設定）

**Rationale**:
- 相対パスの深いネストを回避
- TypeScript と Vite の両方で解決が必要
- 一般的なプラクティス

**Alternatives Considered**:
- 相対パスのみ → `../../../` のような深いインポートが発生
- `~/` プレフィックス → `@/` の方が広く使われている

---

### R-006: Vite 開発サーバーの Docker 設定

**Decision**: `vite.config.ts` で `server.host: '0.0.0.0'` を設定

**Rationale**:
- Docker コンテナ外からアクセスするために必要
- localhost のみだとホストマシンからアクセスできない

**Alternatives Considered**:
- Docker ネットワーク設定の変更 → 複雑で移植性が低い
- ポートフォワーディングのみ → ホスト設定なしでは動作しない

---

### R-007: React Router 設定

**Decision**: React Router v7 を使用し、app/router.tsx で BrowserRouter ベースのルーティングを設定

**Rationale**:
- React Router 7.x は最新の安定版
- BrowserRouter は SPA に適している
- app/ ディレクトリにルーティング設定を集約

**Alternatives Considered**:
- React Router v6 → v7 が最新で機能が充実
- Tanstack Router → 採用実績が少ない、学習コストが高い

---

## Unresolved Items

なし - すべての技術決定が完了しました。

## References

- [Vite 公式ドキュメント](https://vitejs.dev/)
- [React 公式ドキュメント](https://react.dev/)
- [Tailwind CSS 公式ドキュメント](https://tailwindcss.com/)
- [ESLint Flat Config](https://eslint.org/docs/latest/use/configure/configuration-files-new)
- [React Router v7](https://reactrouter.com/)
