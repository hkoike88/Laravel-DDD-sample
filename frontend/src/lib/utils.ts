/**
 * 共通ユーティリティ関数
 * アプリケーション全体で使用される汎用関数を提供
 */

/**
 * クラス名を結合するユーティリティ
 * @param classes - 結合するクラス名の配列
 * @returns 結合されたクラス名文字列
 */
export function cn(...classes: (string | undefined | null | false)[]): string {
  return classes.filter(Boolean).join(' ')
}
