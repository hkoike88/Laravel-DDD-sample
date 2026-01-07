/**
 * 職員編集フォームバリデーションスキーマ
 *
 * Zod を使用した職員編集フォームのバリデーション定義。
 * React Hook Form の resolver として使用。
 *
 * @feature EPIC-004-staff-account-edit
 */

import { z } from 'zod'

/**
 * 職員編集フォームスキーマ
 *
 * バリデーションルール:
 * - name: 必須、100文字以内
 * - email: 必須、メール形式、255文字以内
 * - role: 必須、'staff' または 'admin'
 */
export const updateStaffSchema = z.object({
  name: z
    .string()
    .min(1, { message: '氏名は必須です' })
    .max(100, { message: '氏名は100文字以内で入力してください' }),
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
 * 職員編集フォームデータ型
 * スキーマから推論された型
 */
export type UpdateStaffFormValues = z.infer<typeof updateStaffSchema>
