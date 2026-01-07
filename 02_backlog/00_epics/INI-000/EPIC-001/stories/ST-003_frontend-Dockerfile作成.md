# ST-003: フロントエンド用 Dockerfile の作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、React + TypeScript アプリケーションを実行できるコンテナ環境を構築したい。
**なぜなら**、Node.js がインストールされた統一環境で開発を行いたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-001: Docker 環境構築](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] Node.js 22.x が利用可能なこと
2. [ ] npm が利用可能なこと
3. [ ] Vite 開発サーバーが起動できること
4. [ ] HMR（Hot Module Replacement）が機能すること
5. [ ] ホストからのファイル変更が即時反映されること

---

## 技術仕様

### ベースイメージ

- `node:22-alpine`（LTS、軽量）

### Dockerfile 構成

```dockerfile
FROM node:22-alpine

# 作業ディレクトリの設定
WORKDIR /app

# パッケージファイルのコピー
COPY package*.json ./

# 依存パッケージのインストール
RUN npm ci

# ソースコードのコピー（開発時はボリュームマウントで上書き）
COPY . .

# Vite の開発サーバーポート
EXPOSE 5173

# 開発サーバーの起動
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

### Vite 設定（HMR 対応）

```typescript
// vite.config.ts
export default defineConfig({
  server: {
    host: '0.0.0.0',
    port: 5173,
    watch: {
      usePolling: true, // Docker 環境での監視対応
    },
  },
});
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Dockerfile | frontend/ |
| .dockerignore | frontend/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] Node.js バージョンの確定
- [ ] Vite 設定要件の整理

### Spec Tasks（詳細設計）

- [ ] Dockerfile の作成
- [ ] .dockerignore の作成
- [ ] ビルドテスト
- [ ] HMR 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
