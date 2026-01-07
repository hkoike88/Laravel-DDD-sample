# ST-001: Vite + React + TypeScript プロジェクトの作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、Vite + React + TypeScript プロジェクトを作成したい。
**なぜなら**、高速な開発サーバーとモダンなビルド環境でフロントエンド開発を始めたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: フロントエンド初期設定](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] Vite + React + TypeScript プロジェクトが作成されていること
2. [ ] `npm run dev` で開発サーバーが起動できること
3. [ ] TypeScript の型チェックが通ること
4. [ ] `npm run build` でビルドが成功すること
5. [ ] index.html が適切に設定されていること

---

## 技術仕様

### プロジェクト作成コマンド

```bash
# フロントエンドコンテナに入る
docker compose exec frontend bash

# Vite プロジェクトを作成（現在のディレクトリに展開）
npm create vite@latest . -- --template react-ts

# または既存ディレクトリにインストール
npm create vite@latest temp -- --template react-ts
mv temp/* temp/.[!.]* .
rmdir temp

# 依存パッケージのインストール
npm install
```

### 確認コマンド

```bash
# 開発サーバー起動
npm run dev -- --host 0.0.0.0

# 型チェック
npx tsc --noEmit

# ビルド
npm run build
```

### vite.config.ts 基本設定

```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      usePolling: true
    }
  }
})
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Vite プロジェクト一式 | frontend/ |
| package.json | frontend/package.json |
| tsconfig.json | frontend/tsconfig.json |
| vite.config.ts | frontend/vite.config.ts |
| index.html | frontend/index.html |

---

## タスク

### Design Tasks（外部設計）

- [ ] Vite バージョンの確定（6.x）
- [ ] React バージョンの確定（18.x）

### Spec Tasks（詳細設計）

- [ ] Vite プロジェクトの作成
- [ ] vite.config.ts の設定
- [ ] tsconfig.json の調整
- [ ] 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
