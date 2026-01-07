/**
 * パスワード表示コンポーネント
 *
 * 初期パスワードのマスク表示/平文表示の切り替えと
 * クリップボードへのコピー機能を提供する。
 *
 * @feature EPIC-003-staff-account-create
 */

import { useState } from 'react'

/**
 * PasswordDisplay コンポーネントの Props
 */
interface PasswordDisplayProps {
  /** 表示するパスワード */
  password: string
}

/**
 * パスワード表示コンポーネント
 *
 * @param props - コンポーネントプロパティ
 * @returns パスワード表示UI
 *
 * @example
 * <PasswordDisplay password="Abc123!@#xyz1234" />
 */
export function PasswordDisplay({ password }: PasswordDisplayProps) {
  const [visible, setVisible] = useState(false)
  const [copied, setCopied] = useState(false)

  /**
   * パスワードをクリップボードにコピー
   */
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(password)
      setCopied(true)
      // 2秒後にコピー状態をリセット
      setTimeout(() => setCopied(false), 2000)
    } catch {
      // クリップボード API が利用できない場合は何もしない
    }
  }

  /**
   * 表示/非表示を切り替え
   */
  const toggleVisibility = () => {
    setVisible(!visible)
  }

  return (
    <div className="flex flex-col gap-3">
      <div className="flex items-center gap-2">
        {/* パスワード表示エリア */}
        <div className="flex-1 rounded-md bg-gray-100 px-4 py-3 font-mono text-lg">
          {visible ? password : '••••••••••••••••'}
        </div>
      </div>

      {/* ボタンエリア */}
      <div className="flex gap-2">
        <button
          type="button"
          onClick={toggleVisibility}
          className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          aria-label={visible ? 'パスワードを非表示にする' : 'パスワードを表示する'}
        >
          {visible ? '非表示' : '表示'}
        </button>

        <button
          type="button"
          onClick={handleCopy}
          className={`rounded-md px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 ${
            copied
              ? 'bg-green-600 text-white focus:ring-green-500'
              : 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500'
          }`}
          aria-label="パスワードをクリップボードにコピー"
        >
          {copied ? 'コピーしました' : 'コピー'}
        </button>
      </div>

      {/* 注意事項 */}
      <p className="text-sm text-gray-500">
        ※ この画面を離れると初期パスワードは再表示できません。必ずコピーして安全に保管してください。
      </p>
    </div>
  )
}
