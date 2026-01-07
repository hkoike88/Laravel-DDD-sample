/**
 * パスワードリセット確認ダイアログコンポーネント
 *
 * パスワードリセット確認および結果表示ダイアログ。
 * 一時パスワードのコピー機能付き。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { useState } from 'react'

/**
 * パスワードリセットダイアログの Props
 */
interface PasswordResetDialogProps {
  /** ダイアログ表示状態 */
  isOpen: boolean
  /** 対象職員の氏名 */
  staffName: string
  /** リセット実行時のコールバック */
  onConfirm: () => void
  /** ダイアログを閉じる時のコールバック */
  onClose: () => void
  /** リセット処理中フラグ */
  isPending?: boolean
  /** リセット成功フラグ */
  isSuccess?: boolean
  /** 一時パスワード（リセット成功時） */
  temporaryPassword?: string
}

/**
 * パスワードリセット確認ダイアログコンポーネント
 *
 * @param props - コンポーネントのプロパティ
 *
 * @example
 * <PasswordResetDialog
 *   isOpen={showDialog}
 *   staffName="山田 太郎"
 *   onConfirm={() => resetPassword(staffId)}
 *   onClose={() => setShowDialog(false)}
 *   isPending={isPending}
 *   isSuccess={isSuccess}
 *   temporaryPassword={data?.temporaryPassword}
 * />
 */
export function PasswordResetDialog({
  isOpen,
  staffName,
  onConfirm,
  onClose,
  isPending = false,
  isSuccess = false,
  temporaryPassword,
}: PasswordResetDialogProps) {
  const [copied, setCopied] = useState(false)

  /**
   * 一時パスワードをクリップボードにコピー
   */
  const handleCopy = async () => {
    if (!temporaryPassword) return

    try {
      await navigator.clipboard.writeText(temporaryPassword)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch {
      // フォールバック: 旧式のコピー方法
      const textArea = document.createElement('textarea')
      textArea.value = temporaryPassword
      document.body.appendChild(textArea)
      textArea.select()
      document.execCommand('copy')
      document.body.removeChild(textArea)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    }
  }

  /**
   * ダイアログを閉じる（コピー状態もリセット）
   */
  const handleClose = () => {
    setCopied(false)
    onClose()
  }

  if (!isOpen) return null

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      {/* オーバーレイ */}
      <div
        className="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
        onClick={!isPending ? handleClose : undefined}
      />

      {/* ダイアログ本体 */}
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
          {/* リセット成功時の表示 */}
          {isSuccess && temporaryPassword ? (
            <>
              <div className="mb-4 flex items-center">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                  <svg
                    className="h-6 w-6 text-green-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                </div>
                <h3 className="ml-3 text-lg font-medium text-gray-900">
                  パスワードをリセットしました
                </h3>
              </div>

              <div className="mb-6">
                <p className="mb-3 text-sm text-gray-600">
                  {staffName}さんの一時パスワードが発行されました。
                  このパスワードを本人に伝えてください。
                </p>

                {/* 一時パスワード表示 */}
                <div className="rounded-md bg-gray-100 p-4">
                  <p className="mb-1 text-xs text-gray-500">一時パスワード</p>
                  <div className="flex items-center justify-between">
                    <code className="font-mono text-lg font-medium text-gray-900">
                      {temporaryPassword}
                    </code>
                    <button
                      type="button"
                      onClick={handleCopy}
                      className="ml-3 rounded-md bg-white px-3 py-1 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                      {copied ? 'コピー済み' : 'コピー'}
                    </button>
                  </div>
                </div>

                <p className="mt-3 text-xs text-red-600">
                  このパスワードは一度だけ表示されます。ダイアログを閉じると確認できなくなります。
                </p>
              </div>

              <div className="flex justify-end">
                <button
                  type="button"
                  onClick={handleClose}
                  className="rounded-md bg-blue-600 px-4 py-2 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  閉じる
                </button>
              </div>
            </>
          ) : (
            /* 確認画面 */
            <>
              <div className="mb-4 flex items-center">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100">
                  <svg
                    className="h-6 w-6 text-yellow-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                </div>
                <h3 className="ml-3 text-lg font-medium text-gray-900">パスワードリセットの確認</h3>
              </div>

              <div className="mb-6">
                <p className="text-sm text-gray-600">
                  <span className="font-medium">{staffName}</span>
                  さんのパスワードをリセットしますか？
                </p>
                <p className="mt-2 text-sm text-gray-500">
                  新しい一時パスワードが発行されます。現在のパスワードは使用できなくなります。
                </p>
              </div>

              <div className="flex justify-end gap-3">
                <button
                  type="button"
                  onClick={handleClose}
                  disabled={isPending}
                  className="rounded-md bg-gray-100 px-4 py-2 text-gray-700 font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:bg-gray-50 disabled:cursor-not-allowed"
                >
                  キャンセル
                </button>
                <button
                  type="button"
                  onClick={onConfirm}
                  disabled={isPending}
                  className="rounded-md bg-yellow-600 px-4 py-2 text-white font-medium hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 disabled:bg-yellow-400 disabled:cursor-not-allowed"
                >
                  {isPending ? 'リセット中...' : 'リセットする'}
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  )
}
