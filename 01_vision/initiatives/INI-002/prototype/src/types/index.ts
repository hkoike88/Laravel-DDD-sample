/**
 * 図書館システムプロトタイプ - 型定義
 *
 * 業務ルール:
 * - BR-001: 貸出上限 1人5冊まで
 * - BR-002: 貸出期間 14日間（新刊・雑誌・AVは7日間）
 * - BR-003: 延滞中は新規貸出停止
 * - BR-004: 予約上限 1人3冊まで
 * - BR-005: 取り置き期限 連絡後7日間
 * - BR-006: 利用者登録資格 市内在住・在勤・在学者
 * - BR-007: カード有効期限 登録から1年間
 * - BR-008: 禁帯出資料
 */

/**
 * 資料区分
 */
export type MaterialType = '一般図書' | '新刊図書' | '雑誌' | 'CD・DVD' | '参考図書';

/**
 * 蔵書の貸出状態
 */
export type BookStatus = '貸出可' | '貸出中' | '予約あり' | '禁帯出';

/**
 * 蔵書情報
 */
export interface Book {
  id: string;
  isbn: string;
  title: string;
  author: string;
  publisher: string;
  publishedDate: string;
  materialType: MaterialType;
  genre: string;
  status: BookStatus;
  location: string;
  registeredAt: string;
}

/**
 * 利用者ステータス
 */
export type UserStatus = '有効' | '期限切れ' | '停止中';

/**
 * 利用者情報
 */
export interface User {
  id: string;
  cardNumber: string;
  name: string;
  nameKana: string;
  birthDate: string;
  address: string;
  phone: string;
  email?: string;
  registeredAt: string;
  expiresAt: string;
  status: UserStatus;
  memo?: string;
}

/**
 * 貸出記録
 */
export interface Lending {
  id: string;
  bookId: string;
  userId: string;
  lentAt: string;
  dueDate: string;
  returnedAt?: string;
  isOverdue: boolean;
}

/**
 * 予約ステータス
 */
export type ReservationStatus = '予約中' | '取り置き中' | '完了' | 'キャンセル' | '期限切れ';

/**
 * 予約情報
 */
export interface Reservation {
  id: string;
  bookId: string;
  userId: string;
  reservedAt: string;
  position: number;
  status: ReservationStatus;
  notifiedAt?: string;
  holdUntil?: string;
}

/**
 * 業務ルール定数
 */
export const BUSINESS_RULES = {
  /** 貸出上限冊数 */
  MAX_LENDING_COUNT: 5,
  /** 一般図書の貸出日数 */
  LENDING_PERIOD_NORMAL: 14,
  /** 新刊・雑誌・AVの貸出日数 */
  LENDING_PERIOD_SHORT: 7,
  /** 予約上限冊数 */
  MAX_RESERVATION_COUNT: 3,
  /** 取り置き期限日数 */
  HOLD_PERIOD: 7,
  /** カード有効期限（年） */
  CARD_VALIDITY_YEARS: 1,
  /** 1タイトルあたりの予約上限 */
  MAX_RESERVATION_PER_TITLE: 3,
} as const;
