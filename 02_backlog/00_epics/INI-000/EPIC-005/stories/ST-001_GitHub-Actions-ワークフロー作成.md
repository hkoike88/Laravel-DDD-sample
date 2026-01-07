# ST-001: GitHub Actions ワークフローの作成

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、GitHub Actions の CI ワークフローを作成したい。
**なぜなら**、プッシュや PR 時に自動的にコード品質をチェックし、問題を早期に発見したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: CI/CD 環境構築](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Draft |

---

## 受け入れ条件

1. [ ] `.github/workflows/ci.yml` が作成されていること
2. [ ] push イベントで CI が実行されること
3. [ ] pull_request イベントで CI が実行されること
4. [ ] master, main, develop ブランチがトリガー対象であること
5. [ ] Backend と Frontend のジョブが並列実行されること

---

## 技術仕様

### ワークフロー基本構成

```yaml
# .github/workflows/ci.yml

name: CI

on:
  push:
    branches: [master, main, develop]
  pull_request:
    branches: [master, main, develop]

env:
  PHP_VERSION: '8.3'
  NODE_VERSION: '20'

jobs:
  backend:
    name: Backend
    runs-on: ubuntu-latest
    # ... (ST-002 で詳細定義)

  frontend:
    name: Frontend
    runs-on: ubuntu-latest
    # ... (ST-003 で詳細定義)
```

### トリガー設定

| イベント | ブランチ | 説明 |
|----------|---------|------|
| push | master, main, develop | 直接プッシュ時 |
| pull_request | master, main, develop | PR 作成・更新時 |

### 環境変数

| 変数名 | 値 | 用途 |
|--------|-----|------|
| PHP_VERSION | 8.3 | PHP バージョン |
| NODE_VERSION | 20 | Node.js バージョン |

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| CI ワークフロー | .github/workflows/ci.yml |

---

## タスク

### Design Tasks（外部設計）

- [ ] トリガーイベントの決定
- [ ] 対象ブランチの決定
- [ ] ジョブ構成の設計

### Spec Tasks（詳細設計）

- [ ] .github/workflows ディレクトリの作成
- [ ] ci.yml の基本構造作成
- [ ] 環境変数の設定
- [ ] ジョブの並列実行設定

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
