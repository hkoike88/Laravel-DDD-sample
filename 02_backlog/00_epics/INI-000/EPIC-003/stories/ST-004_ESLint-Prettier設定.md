# ST-004: ESLint / Prettier の設定

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、ESLint と Prettier を設定したい。
**なぜなら**、コード品質を自動的にチェックし、一貫したコードスタイルを維持したいからだ。

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

1. [ ] ESLint が設定されていること
2. [ ] Prettier が設定されていること
3. [ ] `npm run lint` でリントが実行できること
4. [ ] `npm run format` でフォーマットが実行できること
5. [ ] ESLint と Prettier が競合しないこと
6. [ ] TypeScript + React のルールが適用されていること

---

## 技術仕様

### インストールパッケージ

| パッケージ | 用途 |
|-----------|------|
| eslint | リンター本体 |
| @eslint/js | ESLint 公式設定 |
| typescript-eslint | TypeScript 対応 |
| eslint-plugin-react | React ルール |
| eslint-plugin-react-hooks | Hooks ルール |
| prettier | フォーマッター本体 |
| eslint-config-prettier | ESLint + Prettier 競合解消 |

### インストールコマンド

```bash
# ESLint 関連
npm install -D eslint @eslint/js typescript-eslint eslint-plugin-react eslint-plugin-react-hooks

# Prettier 関連
npm install -D prettier eslint-config-prettier
```

### 設定ファイル

#### eslint.config.js

```javascript
import js from '@eslint/js'
import tseslint from 'typescript-eslint'
import react from 'eslint-plugin-react'
import reactHooks from 'eslint-plugin-react-hooks'
import prettier from 'eslint-config-prettier'

export default tseslint.config(
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    files: ['**/*.{ts,tsx}'],
    plugins: {
      react,
      'react-hooks': reactHooks,
    },
    languageOptions: {
      parserOptions: {
        ecmaFeatures: {
          jsx: true,
        },
      },
    },
    settings: {
      react: {
        version: 'detect',
      },
    },
    rules: {
      ...react.configs.recommended.rules,
      ...reactHooks.configs.recommended.rules,
      'react/react-in-jsx-scope': 'off',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
    },
  },
  prettier,
  {
    ignores: ['dist/', 'node_modules/'],
  }
)
```

#### .prettierrc

```json
{
  "semi": false,
  "singleQuote": true,
  "tabWidth": 2,
  "trailingComma": "es5",
  "printWidth": 100
}
```

#### .prettierignore

```
dist
node_modules
*.md
```

### package.json スクリプト追加

```json
{
  "scripts": {
    "lint": "eslint src --ext .ts,.tsx",
    "lint:fix": "eslint src --ext .ts,.tsx --fix",
    "format": "prettier --write src/**/*.{ts,tsx,css}",
    "format:check": "prettier --check src/**/*.{ts,tsx,css}"
  }
}
```

### 確認コマンド

```bash
# リント実行
npm run lint

# フォーマットチェック
npm run format:check

# 自動修正
npm run lint:fix
npm run format
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| ESLint 設定 | frontend/eslint.config.js |
| Prettier 設定 | frontend/.prettierrc |
| Prettier 除外設定 | frontend/.prettierignore |
| 更新された package.json | frontend/package.json |

---

## タスク

### Design Tasks（外部設計）

- [ ] ESLint ルールの選定
- [ ] Prettier オプションの確定

### Spec Tasks（詳細設計）

- [ ] パッケージのインストール
- [ ] eslint.config.js の作成
- [ ] .prettierrc の作成
- [ ] package.json へのスクリプト追加
- [ ] 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
