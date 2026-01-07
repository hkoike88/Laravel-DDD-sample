import { z } from 'zod'

/**
 * ISBN形式の正規表現パターン
 * ISBN-10: 9桁の数字 + 1桁の数字またはX
 * ISBN-13: 978または979で始まる13桁の数字
 */
const ISBN_PATTERN = /^(97[89]\d{10}|\d{9}[\dX])$/

/**
 * 蔵書登録フォームのバリデーションスキーマ
 *
 * 仕様 FR-003〜FR-006, FR-011〜FR-013 に準拠
 */
export const createBookSchema = z.object({
  /** 書籍タイトル（必須、1〜200文字） */
  title: z
    .string()
    .min(1, 'タイトルは必須です')
    .max(200, 'タイトルは200文字以内で入力してください'),

  /** 著者名（任意、最大100文字） */
  author: z.string().max(100, '著者名は100文字以内で入力してください').optional().or(z.literal('')),

  /** ISBN（任意、ハイフンなし ISBN-10 または ISBN-13） */
  isbn: z
    .string()
    .regex(ISBN_PATTERN, 'ISBNの形式が正しくありません（ハイフンなしで入力してください）')
    .optional()
    .or(z.literal('')),

  /** 出版社名（任意、最大100文字） */
  publisher: z
    .string()
    .max(100, '出版社名は100文字以内で入力してください')
    .optional()
    .or(z.literal('')),

  /** 出版年（任意、1000〜現在年+1） */
  published_year: z
    .number()
    .int('出版年は整数で入力してください')
    .min(1000, '出版年は1000以上で入力してください')
    .max(
      new Date().getFullYear() + 1,
      `出版年は${new Date().getFullYear() + 1}以下で入力してください`
    )
    .optional()
    .nullable(),

  /** ジャンル（任意、最大100文字） */
  genre: z
    .string()
    .max(100, 'ジャンルは100文字以内で入力してください')
    .optional()
    .or(z.literal('')),
})

/**
 * 蔵書登録フォームの入力型
 */
export type CreateBookFormInput = z.infer<typeof createBookSchema>

/**
 * フォーム入力からAPI送信用データへの変換
 * 空文字列を undefined に変換し、API仕様に合わせる
 */
export function toCreateBookInput(formData: CreateBookFormInput) {
  return {
    title: formData.title,
    author: formData.author || undefined,
    isbn: formData.isbn || undefined,
    publisher: formData.publisher || undefined,
    published_year: formData.published_year ?? undefined,
    genre: formData.genre || undefined,
  }
}
