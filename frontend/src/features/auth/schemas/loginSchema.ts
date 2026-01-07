/**
 * ログインフォームバリデーションスキーマ
 *
 * Zod を使用したログインフォームのバリデーション定義。
 * React Hook Form の resolver として使用。
 */

import { z } from 'zod'

/**
 * ログインフォームスキーマ
 *
 * バリデーションルール:
 * - email: 必須、メール形式
 * - password: 必須、8文字以上
 */
export const loginSchema = z.object({
  email: z
    .string()
    .min(1, { message: 'メールアドレスを入力してください' })
    .email({ message: '有効なメールアドレスを入力してください' })
    .max(255, { message: 'メールアドレスは255文字以内で入力してください' }),
  password: z
    .string()
    .min(1, { message: 'パスワードを入力してください' })
    .min(8, { message: 'パスワードは8文字以上で入力してください' }),
})

/**
 * ログインフォームデータ型
 * スキーマから推論された型
 */
export type LoginFormValues = z.infer<typeof loginSchema>
