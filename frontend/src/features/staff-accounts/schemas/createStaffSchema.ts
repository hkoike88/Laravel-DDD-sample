/**
 * 職員作成フォームバリデーションスキーマ
 *
 * Zod を使用した職員作成フォームのバリデーション定義。
 * React Hook Form の resolver として使用。
 *
 * @feature EPIC-003-staff-account-create
 */

import { z } from 'zod'

/**
 * 職員作成フォームスキーマ
 *
 * バリデーションルール:
 * - name: 必須、50文字以内
 * - email: 必須、メール形式、255文字以内
 * - role: 必須、'staff' または 'admin'
 */
export const createStaffSchema = z.object({
  name: z
    .string()
    .min(1, { message: '氏名は必須です' })
    .max(50, { message: '氏名は50文字以内で入力してください' }),
  email: z
    .string()
    .min(1, { message: 'メールアドレスは必須です' })
    .email({ message: '有効なメールアドレスを入力してください' })
    .max(255, { message: 'メールアドレスは255文字以内で入力してください' }),
  role: z.enum(['staff', 'admin'], {
    message: '権限を選択してください',
  }),
})

/**
 * 職員作成フォームデータ型
 * スキーマから推論された型
 */
export type CreateStaffFormValues = z.infer<typeof createStaffSchema>
