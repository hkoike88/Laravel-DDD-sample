import { cn } from '@/lib/utils'

/**
 * アプリケーションのルートコンポーネント
 * Feature-based アーキテクチャのエントリーポイント
 */
function App() {
  const containerClass = cn('container', 'mx-auto', 'p-8')

  return (
    <div className={containerClass}>
      <h1 className="text-3xl font-bold text-gray-900 mb-4">laravel-ddd-library</h1>
      <p className="text-gray-600">React + TypeScript + Vite 開発環境が正常に動作しています。</p>
      <div className="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <p className="text-blue-800">Tailwind CSS が正しく設定されています。</p>
      </div>
    </div>
  )
}

export default App
