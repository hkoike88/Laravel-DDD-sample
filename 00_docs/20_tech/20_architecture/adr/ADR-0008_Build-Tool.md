# ADR-0008: ビルドツール（Vite）

## ステータス

採用

## コンテキスト

React + TypeScript プロジェクトの開発・ビルド環境を構築するツールを決定する必要がある。以下の要件を考慮する。

- 開発サーバーの起動速度
- HMR（Hot Module Replacement）の速度
- 本番ビルドの最適化
- 設定のシンプルさ
- エコシステムとの互換性

## 決定

**Vite 6.0** を採用する。

### 採用理由

1. **開発サーバーの高速起動**
   - ESM ネイティブにより、バンドルなしで即座に起動
   - プロジェクト規模に関わらず一定の起動速度

2. **高速な HMR**
   - 変更したモジュールのみを更新
   - 大規模プロジェクトでも瞬時に反映

3. **本番ビルドの最適化**
   - Rollup ベースで最適化されたバンドル
   - コード分割、Tree Shaking が標準

4. **設定のシンプルさ**
   - 最小限の設定で動作
   - React 用テンプレートが公式提供

5. **TypeScript サポート**
   - 追加設定なしで TypeScript を認識
   - 型チェックは tsc と並行実行

6. **エコシステム**
   - Vitest（テストフレームワーク）との統合
   - 豊富なプラグイン

## 比較検討

| 項目 | Vite | Create React App | Next.js | webpack |
|------|------|------------------|---------|---------|
| 開発サーバー起動 | ◎ | △ | ○ | △ |
| HMR 速度 | ◎ | ○ | ○ | ○ |
| 設定の簡単さ | ◎ | ◎ | ○ | △ |
| 本番ビルド | ◎ | ○ | ◎ | ◎ |
| SSR サポート | ○ | × | ◎ | ○ |
| 学習コスト | 低 | 低 | 中 | 高 |
| カスタマイズ性 | ◎ | △ | ○ | ◎ |

### 不採用理由

- **Create React App (CRA)**: メンテナンスが停滞。起動・ビルドが遅い
- **Next.js**: SSR が必要な場合は優れているが、純粋な SPA にはオーバースペック
- **webpack 直接利用**: 設定が複雑。Vite で十分な機能がある

## 結果

### メリット

- 開発体験の大幅な向上（即座の起動・反映）
- 設定ファイルがシンプル
- 最新の Web 標準（ESM）に準拠

### デメリット

- CommonJS モジュールとの互換性に注意が必要な場合がある
- Rollup の知識が必要な高度なカスタマイズ時

### リスクと対策

| リスク | 対策 |
|--------|------|
| CJS モジュールの問題 | vite-plugin-commonjs で対応 |
| ビルド時の型エラー見逃し | CI で tsc による型チェックを実行 |

## 実装方針

### プロジェクト作成

```bash
npm create vite@latest frontend -- --template react-ts
cd frontend
npm install
```

### 設定ファイル

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
  build: {
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['react', 'react-dom', 'react-router-dom'],
          query: ['@tanstack/react-query'],
        },
      },
    },
  },
})
```

### TypeScript 設定

```json
// tsconfig.json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["src/*"]
    }
  },
  "include": ["src"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
```

### package.json スクリプト

```json
{
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "preview": "vite preview",
    "lint": "eslint . --ext ts,tsx",
    "type-check": "tsc --noEmit"
  }
}
```

### 環境変数

```bash
# .env.development
VITE_API_BASE_URL=http://localhost:8000

# .env.production
VITE_API_BASE_URL=https://api.example.com
```

```typescript
// 使用例
const apiUrl = import.meta.env.VITE_API_BASE_URL;
```

## 参考資料

- [Vite 公式ドキュメント](https://vitejs.dev/)
- [Vitest 公式ドキュメント](https://vitest.dev/)
- [フロントエンド アーキテクチャ設計](../../99_standard/frontend/01_ArchitectureDesign.md)
