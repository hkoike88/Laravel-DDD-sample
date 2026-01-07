/**
 * 職員アカウント管理の型定義
 *
 * 職員アカウント作成・一覧表示機能（007-staff-account-create）で使用する型定義。
 * バックエンド API のレスポンス型と対応。
 */

// =============================================================================
// API リクエスト型
// =============================================================================

/**
 * 職員作成リクエスト
 */
export interface CreateStaffRequest {
  /** 職員名（必須、50文字以内） */
  name: string
  /** メールアドレス（必須、メール形式、255文字以内、一意） */
  email: string
  /** 権限（'staff' = 一般職員, 'admin' = 管理者） */
  role: 'staff' | 'admin'
}

// =============================================================================
// API レスポンス型
// =============================================================================

/**
 * 職員一覧アイテム
 */
export interface StaffListItem {
  /** 職員ID（ULID形式、26文字） */
  id: string
  /** 職員名 */
  name: string
  /** メールアドレス */
  email: string
  /** 権限 */
  role: 'staff' | 'admin'
  /** 作成日時（ISO 8601） */
  createdAt: string
}

/**
 * ページネーションリンク
 */
export interface PaginationLinks {
  /** 最初のページへのURL */
  first: string | null
  /** 最後のページへのURL */
  last: string | null
  /** 前のページへのURL */
  prev: string | null
  /** 次のページへのURL */
  next: string | null
}

/**
 * ページネーションメタ情報
 */
export interface PaginationMeta {
  /** 現在のページ番号 */
  currentPage: number
  /** 最終ページ番号 */
  lastPage: number
  /** 1ページあたりの件数 */
  perPage: number
  /** 総件数 */
  total: number
  /** 現在のページの開始レコード番号 */
  from: number | null
  /** 現在のページの終了レコード番号 */
  to: number | null
}

/**
 * 職員一覧レスポンス
 */
export interface StaffListResponse {
  /** 職員一覧データ */
  data: StaffListItem[]
  /** ページネーションメタ情報 */
  meta: PaginationMeta
  /** ページネーションリンク */
  links: PaginationLinks
}

/**
 * 職員作成レスポンス
 */
export interface CreateStaffResponse {
  /** 成功メッセージ */
  message: string
  /** 作成された職員情報 */
  staff: StaffListItem
  /** 自動生成された初期パスワード */
  temporaryPassword: string
}

// =============================================================================
// エラーレスポンス型
// =============================================================================

/**
 * バリデーションエラー詳細
 */
export interface ValidationErrorDetail {
  /** エラーが発生したフィールド名 */
  field: string
  /** バリデーションエラーコード */
  code: string
  /** エラーメッセージ */
  message: string
}

/**
 * エラーレスポンス
 */
export interface ErrorResponse {
  error: {
    /** エラーコード */
    code: string
    /** エラーメッセージ */
    message: string
    /** バリデーションエラー詳細（422の場合） */
    details?: ValidationErrorDetail[]
  }
}

// =============================================================================
// API エラー種別
// =============================================================================

/**
 * API エラー種別
 */
export type StaffAccountApiErrorType =
  | 'authentication' // 401: 認証エラー
  | 'permission' // 403: 権限エラー
  | 'notFound' // 404: 職員が見つからない
  | 'conflict' // 409: 楽観的ロック競合
  | 'validation' // 422: バリデーションエラー
  | 'businessRule' // 422: ビジネスルール違反（自己権限変更、最後の管理者保護等）
  | 'server' // 500: サーバーエラー
  | 'network' // ネットワークエラー

/**
 * API エラー
 */
export interface StaffAccountApiError {
  type: StaffAccountApiErrorType
  code: string
  message: string
  details?: ValidationErrorDetail[]
}

// =============================================================================
// フォーム型
// =============================================================================

/**
 * 職員作成フォームデータ
 */
export interface CreateStaffFormData {
  name: string
  email: string
  role: 'staff' | 'admin'
}

/**
 * 職員作成結果（フォーム送信後の状態管理用）
 */
export interface CreateStaffResult {
  /** 作成された職員情報 */
  staff: StaffListItem
  /** 初期パスワード */
  temporaryPassword: string
}

// =============================================================================
// EPIC-004: 職員アカウント編集機能
// =============================================================================

/**
 * 職員詳細情報
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface StaffDetail {
  /** 職員ID（ULID形式、26文字） */
  id: string
  /** 職員名 */
  name: string
  /** メールアドレス */
  email: string
  /** 権限 */
  role: 'staff' | 'admin'
  /** ログイン中のユーザーかどうか */
  isCurrentUser: boolean
  /** 更新日時（ISO 8601、楽観的ロック用） */
  updatedAt: string
  /** 作成日時（ISO 8601） */
  createdAt: string
}

/**
 * 職員詳細レスポンス
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface StaffDetailResponse {
  /** 職員詳細データ */
  data: StaffDetail
}

/**
 * 職員更新リクエスト
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface UpdateStaffRequest {
  /** 職員名（必須、100文字以内） */
  name: string
  /** メールアドレス（必須、メール形式、255文字以内） */
  email: string
  /** 権限（'staff' = 一般職員, 'admin' = 管理者） */
  role: 'staff' | 'admin'
  /** 更新日時（ISO 8601、楽観的ロック用） */
  updatedAt: string
}

/**
 * 職員更新レスポンス
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface UpdateStaffResponse {
  /** 成功メッセージ */
  message: string
  /** 更新された職員情報 */
  staff: {
    id: string
    name: string
    email: string
    role: 'staff' | 'admin'
    updatedAt: string
  }
}

/**
 * パスワードリセットレスポンス
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface ResetPasswordResponse {
  /** 成功メッセージ */
  message: string
  /** 新しい一時パスワード */
  temporaryPassword: string
}

/**
 * ビジネスルールエラーレスポンス
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface BusinessRuleErrorResponse {
  /** エラーメッセージ */
  message: string
  /** エラーコード */
  code: 'SELF_ROLE_CHANGE' | 'LAST_ADMIN_PROTECTION' | 'DUPLICATE_EMAIL'
}

/**
 * 競合エラーレスポンス（楽観的ロック）
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface ConflictErrorResponse {
  /** エラーメッセージ */
  message: string
}

/**
 * 職員編集フォームデータ
 *
 * @feature EPIC-004-staff-account-edit
 */
export interface UpdateStaffFormData {
  name: string
  email: string
  role: 'staff' | 'admin'
}
