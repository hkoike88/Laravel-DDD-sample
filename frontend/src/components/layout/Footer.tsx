/**
 * 共通フッターコンポーネント
 *
 * @feature 004-dashboard-ui
 */

interface FooterProps {
  /** 著作権表示年（オプション） */
  year?: number
  /** 組織名（オプション） */
  organizationName?: string
}

/**
 * アプリケーション共通フッター
 */
export function Footer({
  year = new Date().getFullYear(),
  organizationName = '青空市立図書館',
}: FooterProps) {
  return (
    <footer className="bg-gray-100 border-t border-gray-200 py-4" role="contentinfo">
      <div className="container mx-auto px-4 text-center text-sm text-gray-600">
        © {year} {organizationName}
      </div>
    </footer>
  )
}
