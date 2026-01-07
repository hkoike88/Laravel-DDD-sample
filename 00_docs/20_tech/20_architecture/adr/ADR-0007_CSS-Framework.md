# ADR-0007: CSS フレームワーク（Tailwind CSS）

## ステータス

採用

## コンテキスト

React アプリケーションのスタイリング方式を決定する必要がある。以下の要件を考慮する。

- 開発効率（スタイリングの速度）
- デザインの一貫性
- バンドルサイズ
- 学習コスト
- カスタマイズ性

## 決定

**Tailwind CSS 3.4** を採用する。

### 採用理由

1. **開発効率の向上**
   - HTML/JSX 内で直接スタイリング
   - CSS ファイルを行き来する必要がない
   - クラス名を考える時間が不要

2. **デザインシステムの強制**
   - 事前定義された spacing、color、typography
   - 一貫したデザインを自然に実現
   - カスタムテーマで拡張可能

3. **バンドルサイズの最適化**
   - 使用されていないクラスを自動削除（PurgeCSS 内蔵）
   - 本番ビルドで最小限の CSS のみ出力

4. **コンポーネントとの相性**
   - React のコンポーネント設計と親和性が高い
   - 同一ファイル内でスタイルを完結

5. **エコシステム**
   - Headless UI、daisyUI などのコンポーネントライブラリ
   - Tailwind UI（有料）で高品質なテンプレート
   - IDE プラグインによる補完サポート

## 比較検討

| 項目 | Tailwind CSS | CSS Modules | styled-components | MUI |
|------|-------------|-------------|-------------------|-----|
| 学習曲線 | 中 | 低 | 中 | 中 |
| 開発速度 | ◎ | ○ | ○ | ◎ |
| バンドルサイズ | ◎ | ◎ | △ | △ |
| カスタマイズ性 | ◎ | ◎ | ◎ | ○ |
| デザイン一貫性 | ◎ | △ | △ | ◎ |
| TypeScript 連携 | ○ | ◎ | ◎ | ◎ |

### 不採用理由

- **CSS Modules**: デザインシステムの強制がない。クラス名の命名が必要
- **styled-components**: ランタイムオーバーヘッド。バンドルサイズが増加
- **MUI（Material-UI）**: Material Design に縛られる。カスタマイズに労力がかかる
- **Emotion**: styled-components と同様の課題

## 結果

### メリット

- プロトタイピングが高速
- デザインの一貫性が自動的に担保される
- CSS の重複がなく、バンドルサイズが小さい
- レスポンシブデザインが簡単（`sm:`, `md:` などのプレフィックス）

### デメリット

- HTML が長くなる傾向（クラスの羅列）
- 初期学習コスト（ユーティリティクラスの習得）
- 複雑なアニメーションには追加設定が必要

### リスクと対策

| リスク | 対策 |
|--------|------|
| クラスの重複 | `@apply` でコンポーネント化、または共通コンポーネント作成 |
| 可読性の低下 | Prettier プラグインでクラスをソート |
| デザイン変更 | tailwind.config.js でテーマをカスタマイズ |

## 実装方針

### 設定ファイル

```javascript
// tailwind.config.js
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
        },
      },
      fontFamily: {
        sans: ['Noto Sans JP', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
```

### 使用例

```tsx
// components/ui/Button.tsx
interface ButtonProps {
  variant?: 'primary' | 'secondary';
  children: React.ReactNode;
}

export const Button = ({ variant = 'primary', children }: ButtonProps) => {
  const baseClasses = 'px-4 py-2 rounded-md font-medium transition-colors';
  const variantClasses = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700',
    secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300',
  };

  return (
    <button className={`${baseClasses} ${variantClasses[variant]}`}>
      {children}
    </button>
  );
};
```

### Prettier プラグイン

```json
// .prettierrc
{
  "plugins": ["prettier-plugin-tailwindcss"]
}
```

### VS Code 設定

```json
// .vscode/settings.json
{
  "tailwindCSS.experimental.classRegex": [
    ["clsx\\(([^)]*)\\)", "(?:'|\"|`)([^']*)(?:'|\"|`)"]
  ]
}
```

## 参考資料

- [Tailwind CSS 公式ドキュメント](https://tailwindcss.com/docs)
- [Headless UI](https://headlessui.com/)
- [フロントエンド コーディング規約](../../99_standard/frontend/02_CodingStandards.md)
