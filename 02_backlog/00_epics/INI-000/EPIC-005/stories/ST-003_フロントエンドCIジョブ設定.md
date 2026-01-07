# ST-003: フロントエンド CI ジョブの設定

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、フロントエンドの静的解析、型チェック、テストを CI で自動実行したい。
**なぜなら**、TypeScript/React コードの品質を継続的にチェックし、ビルドエラーを防ぎたいからだ。

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

1. [ ] Node.js 20 環境がセットアップされること
2. [ ] npm 依存パッケージがキャッシュされること
3. [ ] ESLint によるリントチェックが実行されること
4. [ ] Prettier によるフォーマットチェックが実行されること
5. [ ] TypeScript による型チェックが実行されること
6. [ ] Vite によるビルドが成功すること
7. [ ] Vitest によるテストが実行されること

---

## 技術仕様

### ジョブ定義

```yaml
frontend:
  name: Frontend
  runs-on: ubuntu-latest
  defaults:
    run:
      working-directory: frontend

  steps:
    - name: Checkout
      uses: actions/checkout@v4

    - name: Setup Node.js
      uses: actions/setup-node@v4
      with:
        node-version: ${{ env.NODE_VERSION }}
        cache: 'npm'
        cache-dependency-path: frontend/package-lock.json

    - name: Install dependencies
      run: npm ci

    - name: ESLint
      run: npm run lint

    - name: Prettier
      run: npm run format:check

    - name: TypeScript
      run: npm run typecheck

    - name: Build
      run: npm run build

    - name: Unit tests
      run: npm run test:run
```

### 実行ステップ

| ステップ | コマンド | 失敗時 |
|----------|---------|--------|
| ESLint | `npm run lint` | ジョブ失敗 |
| Prettier | `npm run format:check` | ジョブ失敗 |
| TypeScript | `npm run typecheck` | ジョブ失敗 |
| Build | `npm run build` | ジョブ失敗 |
| Unit tests | `npm run test:run` | ジョブ失敗 |

### キャッシュ設定

| 設定 | 値 | 説明 |
|------|-----|------|
| cache | npm | npm キャッシュを使用 |
| cache-dependency-path | frontend/package-lock.json | キャッシュキー |

### 必要な npm scripts

```json
{
  "scripts": {
    "lint": "eslint .",
    "format:check": "prettier --check \"src/**/*.{ts,tsx}\"",
    "typecheck": "tsc --noEmit",
    "build": "tsc -b && vite build",
    "test:run": "vitest run"
  }
}
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Frontend ジョブ定義 | .github/workflows/ci.yml |

---

## タスク

### Design Tasks（外部設計）

- [ ] 実行するチェック項目の決定
- [ ] チェック順序の決定
- [ ] キャッシュ戦略の決定

### Spec Tasks（詳細設計）

- [ ] frontend ジョブの定義
- [ ] Node.js セットアップの設定
- [ ] npm キャッシュの設定
- [ ] ESLint ステップの追加
- [ ] Prettier ステップの追加
- [ ] TypeScript ステップの追加
- [ ] Build ステップの追加
- [ ] Vitest ステップの追加

---

## トラブルシューティング

| 問題 | 原因 | 解決策 |
|------|------|--------|
| npm ci 失敗 | package-lock.json 不整合 | ローカルで npm install 後 push |
| ESLint エラー | コードスタイル違反 | npm run lint:fix で修正 |
| TypeScript エラー | 型不整合 | 型定義を修正 |
| ビルド失敗 | インポートエラー | 依存関係を確認 |

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
