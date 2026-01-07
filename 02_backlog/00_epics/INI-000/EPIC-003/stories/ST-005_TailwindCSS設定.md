# ST-005: Tailwind CSS の設定

最終更新: 2025-12-23

---

## ストーリー

**開発者として**、Tailwind CSS を設定したい。
**なぜなら**、ユーティリティファーストの CSS フレームワークで効率的に UI を構築したいからだ。

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

1. [ ] Tailwind CSS がインストールされていること
2. [ ] tailwind.config.js が設定されていること
3. [ ] postcss.config.js が設定されていること
4. [ ] グローバル CSS に Tailwind ディレクティブが追加されていること
5. [ ] Tailwind のクラスが正しく適用されること
6. [ ] 開発時にホットリロードでスタイルが反映されること

---

## 技術仕様

### インストールパッケージ

| パッケージ | 用途 |
|-----------|------|
| tailwindcss | CSS フレームワーク本体 |
| postcss | CSS 変換ツール |
| autoprefixer | ベンダープレフィックス自動追加 |

### インストールコマンド

```bash
# フロントエンドコンテナに入る
docker compose exec frontend bash

# Tailwind CSS インストール
npm install -D tailwindcss postcss autoprefixer

# 設定ファイル生成
npx tailwindcss init -p
```

### 設定ファイル

#### tailwind.config.js

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './index.html',
    './src/**/*.{js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        // カスタムカラーを必要に応じて追加
      },
      fontFamily: {
        // カスタムフォントを必要に応じて追加
      },
    },
  },
  plugins: [],
}
```

#### postcss.config.js

```javascript
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

#### src/index.css

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* カスタムスタイルを必要に応じて追加 */
```

### 動作確認用コンポーネント

```tsx
// src/app/App.tsx での確認例
function App() {
  return (
    <div className="min-h-screen bg-gray-100 flex items-center justify-center">
      <div className="bg-white p-8 rounded-lg shadow-md">
        <h1 className="text-2xl font-bold text-gray-800">
          Tailwind CSS が動作しています！
        </h1>
        <p className="mt-4 text-gray-600">
          スタイルが正しく適用されていれば成功です。
        </p>
      </div>
    </div>
  )
}
```

### 確認方法

```bash
# 開発サーバー起動
npm run dev

# ブラウザで http://localhost:5173 にアクセスし、スタイルが適用されていることを確認
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| Tailwind 設定 | frontend/tailwind.config.js |
| PostCSS 設定 | frontend/postcss.config.js |
| グローバル CSS | frontend/src/index.css |

---

## タスク

### Design Tasks（外部設計）

- [ ] カスタムテーマの検討（必要に応じて）
- [ ] プラグインの選定（必要に応じて）

### Spec Tasks（詳細設計）

- [ ] パッケージのインストール
- [ ] tailwind.config.js の作成
- [ ] postcss.config.js の作成
- [ ] src/index.css の更新
- [ ] 動作確認

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-23 | 初版作成 |
