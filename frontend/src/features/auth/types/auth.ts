/**
 * 認証関連の型定義
 *
 * ログイン画面実装（003-login-ui）で使用する型定義。
 * バックエンド API（ST-002）のレスポンス型と対応。
 */

// =============================================================================
// API リクエスト型
// =============================================================================

/**
 * ログインリクエスト
 */
export interface LoginRequest {
  /** メールアドレス（必須、メール形式、最大255文字） */
  email: string
  /** パスワード（必須、最小8文字） */
  password: string
}

// =============================================================================
// API レスポンス型
// =============================================================================

/**
 * 職員情報
 */
export interface Staff {
  /** 職員ID（ULID形式、26文字） */
  id: string
  /** 職員名（最大100文字） */
  name: string
  /** メールアドレス（最大255文字） */
  email: string
  /** 管理者フラグ */
  is_admin: boolean
}

/**
 * 職員情報レスポンス
 */
export interface StaffResponse {
  data: Staff
}

/**
 * メッセージレスポンス
 */
export interface MessageResponse {
  message: string
}

/**
 * エラーレスポンス
 */
export interface ErrorResponse {
  message: string
}

/**
 * バリデーションエラーレスポンス
 */
export interface ValidationErrorResponse {
  message: string
  errors: Record<string, string[]>
}

// =============================================================================
// 認証ステート型
// =============================================================================

/**
 * 認証ステート
 */
export interface AuthState {
  /** 認証済みフラグ */
  isAuthenticated: boolean
  /** 現在のユーザー情報 */
  currentUser: Staff | null
  /** 認証確認中フラグ */
  isLoading: boolean
}

/**
 * 認証ストアアクション
 */
export interface AuthActions {
  /** 認証成功時の処理 */
  setAuthenticated: (user: Staff) => void
  /** 認証クリア（ログアウト時） */
  clearAuthentication: () => void
  /** ローディング状態設定 */
  setLoading: (loading: boolean) => void
}

/**
 * 認証ストア（ステート + アクション）
 */
export type AuthStore = AuthState & AuthActions

// =============================================================================
// フォーム型
// =============================================================================

/**
 * ログインフォームデータ
 */
export interface LoginFormData {
  email: string
  password: string
}

/**
 * ログインフォームエラー
 */
export interface LoginFormErrors {
  email?: string
  password?: string
}

// =============================================================================
// API エラー種別
// =============================================================================

/**
 * API エラー種別
 */
export type ApiErrorType =
  | 'authentication' // 401: 認証失敗
  | 'validation' // 422: バリデーションエラー
  | 'locked' // 423: アカウントロック
  | 'rate_limit' // 429: レート制限
  | 'server' // 500: サーバーエラー
  | 'network' // ネットワークエラー

/**
 * API エラー
 */
export interface ApiError {
  type: ApiErrorType
  message: string
  errors?: Record<string, string[]>
  retryAfter?: number // レート制限時の待機秒数
}
