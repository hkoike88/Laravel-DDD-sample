# ST-002: 職員アカウント作成 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**管理者として**、職員アカウント作成画面から新しい職員を登録したい。
**なぜなら**、新しい職員にシステムへのアクセス権を付与したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-003: 職員アカウント作成機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員アカウント登録](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-new.md) |

---

## 受け入れ条件

1. [ ] 氏名入力フィールドが表示されること
2. [ ] メールアドレス入力フィールドが表示されること
3. [ ] 権限選択（一般職員/管理者）が表示されること
4. [ ] 作成ボタンをクリックすると職員が作成されること
5. [ ] 作成成功時に職員一覧画面にリダイレクトされること
6. [ ] 作成成功時に初期パスワードがダイアログで表示されること
7. [ ] バリデーションエラー時にフィールドごとにエラーが表示されること
8. [ ] 処理中はローディング状態が表示されること
9. [ ] キャンセルボタンで一覧画面に戻れること

---

## 画面仕様

### UI 要素

| 要素ID | 種類 | 要素名 | 必須 | 説明 |
|--------|------|--------|------|------|
| name | input[text] | 氏名 | ○ | 職員の氏名 |
| email | input[email] | メールアドレス | ○ | ログイン用メールアドレス |
| role | radio | 権限 | ○ | 一般職員 / 管理者 |
| btn-submit | button | 作成 | - | 職員作成を実行 |
| btn-cancel | button | キャンセル | - | 一覧画面に戻る |

### バリデーション

| フィールド | ルール | エラーメッセージ |
|-----------|--------|-----------------|
| 氏名 | 必須 | 氏名を入力してください |
| 氏名 | 50文字以内 | 氏名は50文字以内で入力してください |
| メールアドレス | 必須 | メールアドレスを入力してください |
| メールアドレス | メール形式 | 正しいメールアドレス形式で入力してください |
| 権限 | 必須 | 権限を選択してください |

### 成功ダイアログ

```
┌───────────────────────────────────────────┐
│  ✅ 職員アカウントを作成しました          │
├───────────────────────────────────────────┤
│                                           │
│  田中 花子 さんのアカウントを             │
│  作成しました。                           │
│                                           │
│  初期パスワード:                          │
│  ┌───────────────────────────────────┐   │
│  │ Abc123!@#xyz        [コピー]      │   │
│  └───────────────────────────────────┘   │
│                                           │
│  ※ このパスワードは再表示できません       │
│  ※ 初回ログイン時に変更を推奨します       │
│                                           │
│                              [閉じる]     │
│                                           │
└───────────────────────────────────────────┘
```

---

## 技術仕様

### コンポーネント構成

```
StaffAccountsNewPage
├── PageHeader
├── StaffCreateForm
│   ├── TextField (氏名)
│   ├── TextField (メールアドレス)
│   ├── RadioGroup (権限)
│   └── FormActions
│       ├── CancelButton
│       └── SubmitButton
└── PasswordDialog
```

### Zod スキーマ

```typescript
// createStaffSchema.ts
import { z } from 'zod';

export const createStaffSchema = z.object({
  name: z
    .string()
    .min(1, '氏名を入力してください')
    .max(50, '氏名は50文字以内で入力してください'),
  email: z
    .string()
    .min(1, 'メールアドレスを入力してください')
    .email('正しいメールアドレス形式で入力してください'),
  role: z.enum(['staff', 'admin'], {
    required_error: '権限を選択してください',
  }),
});

export type CreateStaffFormData = z.infer<typeof createStaffSchema>;
```

### useCreateStaff Hook

```typescript
// useCreateStaff.ts
export const useCreateStaff = () => {
  const navigate = useNavigate();
  const [showPasswordDialog, setShowPasswordDialog] = useState(false);
  const [temporaryPassword, setTemporaryPassword] = useState('');

  const mutation = useMutation({
    mutationFn: staffApi.create,
    onSuccess: (data) => {
      setTemporaryPassword(data.temporaryPassword);
      setShowPasswordDialog(true);
    },
  });

  const handleDialogClose = () => {
    setShowPasswordDialog(false);
    navigate('/staff/accounts');
  };

  return {
    ...mutation,
    showPasswordDialog,
    temporaryPassword,
    handleDialogClose,
  };
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| StaffAccountsNewPage | frontend/src/pages/staff/StaffAccountsNewPage.tsx |
| StaffCreateForm | frontend/src/features/staff/components/StaffCreateForm.tsx |
| PasswordDialog | frontend/src/features/staff/components/PasswordDialog.tsx |
| createStaffSchema | frontend/src/features/staff/schemas/createStaffSchema.ts |
| useCreateStaff | frontend/src/features/staff/hooks/useCreateStaff.ts |
| staffApi | frontend/src/features/staff/api/staffApi.ts |
| テスト | frontend/src/features/staff/\_\_tests\_\_/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ワイヤーフレームの確認
- [ ] 成功ダイアログデザインの確定
- [ ] エラーメッセージの確定

### Spec Tasks（詳細設計）

- [ ] createStaffSchema 実装
- [ ] staffApi 実装
- [ ] useCreateStaff フック実装
- [ ] StaffCreateForm コンポーネント実装
- [ ] PasswordDialog コンポーネント実装
- [ ] StaffAccountsNewPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
