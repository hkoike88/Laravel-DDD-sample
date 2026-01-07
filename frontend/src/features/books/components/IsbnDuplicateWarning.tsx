interface IsbnDuplicateWarningProps {
  /** 同一ISBNの蔵書数 */
  count: number
  /** 続行ボタンクリック時のコールバック */
  onContinue: () => void
  /** 中止ボタンクリック時のコールバック */
  onCancel: () => void
}

/**
 * ISBN重複警告コンポーネント
 *
 * 同一ISBNの蔵書が既に登録されている場合に表示される警告。
 * ユーザーは続行（複本として登録）または中止を選択できる。
 */
export function IsbnDuplicateWarning({ count, onContinue, onCancel }: IsbnDuplicateWarningProps) {
  return (
    <div className="p-4 bg-yellow-50 border border-yellow-300 rounded-md">
      <div className="flex items-start">
        <div className="flex-shrink-0">
          <svg
            className="h-5 w-5 text-yellow-400"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
          >
            <path
              fillRule="evenodd"
              d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"
              clipRule="evenodd"
            />
          </svg>
        </div>
        <div className="ml-3 flex-1">
          <h3 className="text-sm font-medium text-yellow-800">
            同じISBNの蔵書が既に登録されています
          </h3>
          <div className="mt-2 text-sm text-yellow-700">
            <p>
              このISBNの蔵書は既に <strong>{count}冊</strong> 登録されています。
              複本として続けて登録しますか？
            </p>
          </div>
          <div className="mt-4 flex space-x-3">
            <button
              type="button"
              onClick={onContinue}
              className="px-4 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-md hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-yellow-500"
            >
              続行（複本として登録）
            </button>
            <button
              type="button"
              onClick={onCancel}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
              中止
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}
