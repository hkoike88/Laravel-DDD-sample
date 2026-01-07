# Feature Specification: フロントエンド初期設定

**Feature Branch**: `004-frontend-setup`
**Created**: 2025-12-23
**Status**: Draft
**Input**: User description: "React + TypeScript + Vite プロジェクトを作成し、Feature-based アーキテクチャに基づいたディレクトリ構成、必要なパッケージのインストール、基本設定を完了する"

## User Scenarios & Testing

### User Story 1 - Vite + React + TypeScript プロジェクトの作成と開発サーバー起動 (Priority: P1) 🎯 MVP

開発者として、React + TypeScript + Vite プロジェクトが Docker コンテナ内で正常に動作し、開発サーバーでアプリケーションが表示される状態にしたい。

**Why this priority**: 開発の基盤となるプロジェクトが動作しないと、他のすべての作業ができない。最も基本的で必須の機能。

**Independent Test**: `npm run dev` で開発サーバーが起動し、ブラウザで http://localhost:5173 にアクセスしてアプリケーションが表示されることを確認できる。

**Acceptance Scenarios**:

1. **Given** Docker コンテナが起動している状態で、**When** frontend コンテナに入り `npm run dev` を実行する、**Then** 開発サーバーが起動し「Vite + React」のデフォルトページが表示される
2. **Given** 開発サーバーが起動している状態で、**When** TypeScript ファイルを編集する、**Then** ホットリロードによりブラウザに変更が即座に反映される
3. **Given** プロジェクトが作成された状態で、**When** `npm run build` を実行する、**Then** dist/ ディレクトリにプロダクションビルドが生成される

---

### User Story 2 - Feature-based ディレクトリ構成の作成 (Priority: P1)

開発者として、Feature-based アーキテクチャに基づいたディレクトリ構成が整備された状態にしたい。これにより、機能ごとにコードを整理し、スケーラブルで保守性の高い開発ができる。

**Why this priority**: ディレクトリ構成は開発初期に確立すべきもので、後から変更するとコストが高い。プロジェクト開始時に正しい構成を作ることが重要。

**Independent Test**: src/ 配下に app/, pages/, features/, components/, hooks/, lib/, types/ ディレクトリが存在し、各ディレクトリの役割が明確になっていることを確認できる。

**Acceptance Scenarios**:

1. **Given** プロジェクトが作成された状態で、**When** src/ ディレクトリを確認する、**Then** Feature-based アーキテクチャに基づいた構成（app/, pages/, features/, components/, hooks/, lib/, types/）が存在する
2. **Given** ディレクトリ構成が作成された状態で、**When** 新機能を追加する、**Then** features/ ディレクトリに機能モジュールを追加できる
3. **Given** components/ui/ と components/layout/ が存在する状態で、**When** 共通コンポーネントを追加する、**Then** 適切なディレクトリに配置できる

---

### User Story 3 - TypeScript 型チェックの動作確認 (Priority: P1)

開発者として、TypeScript の型チェックが正しく動作する環境を整備したい。これにより、型安全な開発ができ、バグを早期に発見できる。

**Why this priority**: 型チェックは開発品質の基盤であり、プロジェクト開始時から有効にしておく必要がある。

**Independent Test**: `npx tsc --noEmit` コマンドがエラーなく完了することを確認できる。

**Acceptance Scenarios**:

1. **Given** TypeScript 設定が完了した状態で、**When** `npx tsc --noEmit` を実行する、**Then** 型チェックがエラーなく完了する
2. **Given** strict モードが有効な状態で、**When** 型エラーを含むコードを記述する、**Then** エディタと CLI で型エラーが検出される
3. **Given** tsconfig.json が設定された状態で、**When** パスエイリアス（@/）を使用する、**Then** 正しくモジュールが解決される

---

### User Story 4 - ESLint / Prettier によるコード品質管理 (Priority: P2)

開発者として、ESLint と Prettier が設定され、コード品質とフォーマットが統一された状態にしたい。

**Why this priority**: コード品質ツールは重要だが、プロジェクトの動作には直接影響しない。P1 完了後に設定しても問題ない。

**Independent Test**: `npm run lint` でコード品質チェックが実行でき、`npm run format` でコードフォーマットが適用されることを確認できる。

**Acceptance Scenarios**:

1. **Given** ESLint が設定された状態で、**When** `npm run lint` を実行する、**Then** コード品質チェックが実行され結果が表示される
2. **Given** Prettier が設定された状態で、**When** `npm run format` を実行する、**Then** すべてのファイルがフォーマットされる
3. **Given** ESLint / Prettier が動作する状態で、**When** コードを保存する、**Then** エディタ連携により自動フォーマットが適用される（エディタ設定に依存）

---

### User Story 5 - Tailwind CSS によるスタイリング環境 (Priority: P2)

開発者として、Tailwind CSS が正しく設定され、ユーティリティクラスでスタイリングできる状態にしたい。

**Why this priority**: スタイリング環境は UI 開発に必要だが、基本的なプロジェクト動作とは独立している。

**Independent Test**: Tailwind CSS のユーティリティクラス（例：`bg-blue-500`）がコンポーネントに適用され、ブラウザで正しく表示されることを確認できる。

**Acceptance Scenarios**:

1. **Given** Tailwind CSS が設定された状態で、**When** コンポーネントに `className="bg-blue-500 text-white p-4"` を適用する、**Then** スタイルが正しくレンダリングされる
2. **Given** tailwind.config.js が設定された状態で、**When** content パスを確認する、**Then** src/ 配下のすべてのファイルが対象になっている
3. **Given** PostCSS が設定された状態で、**When** ビルドを実行する、**Then** 使用されているユーティリティクラスのみが出力される

---

### User Story 6 - 必要なパッケージのインストールと動作確認 (Priority: P2)

開発者として、開発に必要なパッケージ（React Router, TanStack Query, Zustand, Axios, React Hook Form, Zod）がインストールされ、使用可能な状態にしたい。

**Why this priority**: パッケージは今後の開発で必要だが、初期設定の動作確認には必須ではない。

**Independent Test**: package.json に必要なパッケージが含まれており、`npm ls` で正しくインストールされていることを確認できる。

**Acceptance Scenarios**:

1. **Given** package.json が作成された状態で、**When** 必要なパッケージをインストールする、**Then** 依存関係エラーなくインストールが完了する
2. **Given** パッケージがインストールされた状態で、**When** `npm ls react-router-dom @tanstack/react-query zustand axios react-hook-form zod` を実行する、**Then** すべてのパッケージが表示される
3. **Given** パッケージがインストールされた状態で、**When** ビルドを実行する、**Then** エラーなくビルドが完了する

---

### Edge Cases

- Vite プロジェクト作成時に既存ファイルが存在する場合はどうなるか？ → 既存の設定を尊重し、必要なファイルのみ上書きまたは追加
- npm パッケージのバージョン競合が発生した場合はどうなるか？ → package-lock.json をコミットして依存関係を固定
- Docker コンテナ外から開発サーバーにアクセスできない場合はどうなるか？ → Vite の host 設定で 0.0.0.0 を指定
- TypeScript strict モードでエラーが多発する場合はどうなるか？ → 段階的に strict オプションを有効化

## Requirements

### Functional Requirements

- **FR-001**: システムは Vite + React + TypeScript プロジェクトを frontend/ ディレクトリに作成できなければならない
- **FR-002**: システムは開発サーバーを起動し、http://localhost:5173 でアプリケーションを提供できなければならない
- **FR-003**: システムは TypeScript ファイルの編集時にホットリロードで変更を反映しなければならない
- **FR-004**: システムは Feature-based アーキテクチャに基づいたディレクトリ構成（app/, pages/, features/, components/, hooks/, lib/, types/）を持たなければならない
- **FR-005**: システムは TypeScript の型チェックを strict モードで実行できなければならない
- **FR-006**: システムは ESLint によるコード品質チェックを実行できなければならない
- **FR-007**: システムは Prettier によるコードフォーマットを適用できなければならない
- **FR-008**: システムは Tailwind CSS によるユーティリティクラスベースのスタイリングをサポートしなければならない
- **FR-009**: システムは必要なパッケージ（React Router, TanStack Query, Zustand, Axios, React Hook Form, Zod）をインストール済みの状態で提供しなければならない
- **FR-010**: システムはプロダクションビルドを生成できなければならない

### Key Entities

- **プロジェクト設定**: Vite、TypeScript、ESLint、Prettier、Tailwind CSS の設定ファイル群
- **ディレクトリ構成**: Feature-based アーキテクチャに基づいた src/ 配下の構造
- **依存パッケージ**: package.json で管理される npm パッケージ群

### Definitions

- **Feature-based アーキテクチャ**: 機能（feature）ごとにコードをグループ化するディレクトリ構成パターン。各機能は独立したモジュールとして管理される
- **Domain Layer**: features/ 配下の機能モジュール
- **Application Layer**: app/ 配下のアプリケーション設定、ルーティング、プロバイダー
- **Presentation Layer**: pages/ と components/ 配下の UI コンポーネント

## Success Criteria

### Measurable Outcomes

- **SC-001**: `npm run dev` コマンドで開発サーバーが 10 秒以内に起動する
- **SC-002**: http://localhost:5173 でアプリケーションが正常に表示される
- **SC-003**: `npx tsc --noEmit` コマンドがエラー 0 件で完了する
- **SC-004**: `npm run lint` コマンドがエラー 0 件で完了する
- **SC-005**: `npm run build` コマンドが 60 秒以内に正常完了する
- **SC-006**: Feature-based ディレクトリ構成（7 ディレクトリ）が src/ 配下に存在する
- **SC-007**: 開発者は 5 分以内にフロントエンド環境をセットアップできる

## Assumptions

- Docker 環境（EPIC-001）が正常に動作していること
- Node.js 20.x が Docker コンテナ内で利用可能であること
- インターネット接続があり、npm パッケージのダウンロードが可能であること
- 開発者は基本的な React と TypeScript の知識を持っていること

## Dependencies

| Dependency | Type | Description |
|------------|------|-------------|
| EPIC-001 Docker 環境構築 | 前提 | Docker Compose 環境が動作していること |
| EPIC-002 バックエンド初期設定 | 並行可能 | API 連携は後続タスクで実装 |

## Out of Scope

- 具体的なページやコンポーネントの実装
- バックエンド API との連携
- 認証・認可機能の実装
- テストフレームワーク（Jest, Vitest 等）の設定
- CI/CD パイプラインの設定
