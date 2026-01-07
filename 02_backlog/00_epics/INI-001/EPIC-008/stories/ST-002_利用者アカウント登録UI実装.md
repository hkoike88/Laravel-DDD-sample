# ST-002: 利用者アカウント登録 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、利用者情報を入力して登録したい。
**なぜなら**、新規利用者が図書貸出サービスを利用できるようにしたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-008: 利用者アカウント登録機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] 利用者管理画面から「新規登録」ボタンで登録画面に遷移できること
2. [ ] 必須項目（氏名、ふりがな、生年月日、住所、電話番号、利用者区分）が入力できること
3. [ ] 利用者区分で「児童」を選択すると保護者情報入力欄が表示されること
4. [ ] 入力エラー時にエラーメッセージが表示されること
5. [ ] 登録成功時に利用者詳細画面に遷移すること
6. [ ] 登録成功時に「利用者を登録しました」と表示されること
7. [ ] キャンセルボタンで一覧画面に戻ること

---

## 画面仕様

### 利用者登録フォーム

```
┌─────────────────────────────────────────────────────────────┐
│  利用者登録                                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  基本情報                                                   │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  氏名 *                                                     │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 山田 太郎                                              │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  ふりがな *                                                 │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ やまだ たろう                                          │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  生年月日 *                                                 │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 1990-05-15                                             │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  住所 *                                                     │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 〒123-4567 東京都○○区...                              │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  電話番号 *                                                 │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 090-1234-5678                                          │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  利用者区分 *                                               │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 一般                                               ▼  │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  備考                                                       │
│  ┌───────────────────────────────────────────────────────┐ │
│  │                                                        │ │
│  │                                                        │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│                          [キャンセル]  [登録]               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 児童選択時の保護者情報

```
│  保護者情報（児童の場合は必須）                             │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  保護者氏名 *                                               │
│  ┌───────────────────────────────────────────────────────┐ │
│  │                                                        │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  保護者電話番号 *                                           │
│  ┌───────────────────────────────────────────────────────┐ │
│  │                                                        │ │
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  続柄 *                                                     │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ 父                                                 ▼  │ │
│  └───────────────────────────────────────────────────────┘ │
```

---

## 技術仕様

### コンポーネント構成

```
PatronRegistrationPage
├── PageHeader
├── PatronForm
│   ├── BasicInfoSection
│   │   ├── NameInput
│   │   ├── NameKanaInput
│   │   ├── BirthDateInput
│   │   ├── AddressInput
│   │   ├── PhoneNumberInput
│   │   ├── PatronTypeSelect
│   │   └── NotesTextarea
│   ├── GuardianSection（条件付き表示）
│   │   ├── GuardianNameInput
│   │   ├── GuardianPhoneInput
│   │   └── RelationshipSelect
│   └── FormActions
│       ├── CancelButton
│       └── SubmitButton
└── SuccessToast
```

### フォームスキーマ（Zod）

```typescript
// patronSchema.ts
export const guardianSchema = z.object({
  name: z.string().min(1, '保護者氏名を入力してください').max(50),
  phoneNumber: z.string().min(1, '保護者電話番号を入力してください'),
  relationship: z.string().min(1, '続柄を選択してください'),
});

export const patronSchema = z.object({
  name: z.string().min(1, '氏名を入力してください').max(50),
  nameKana: z.string()
    .min(1, 'ふりがなを入力してください')
    .max(50)
    .regex(/^[ぁ-んー\s]+$/, 'ふりがなはひらがなで入力してください'),
  birthDate: z.string().min(1, '生年月日を入力してください'),
  address: z.string().min(1, '住所を入力してください').max(200),
  phoneNumber: z.string().min(1, '電話番号を入力してください'),
  patronType: z.enum(['general', 'student', 'child'], {
    required_error: '利用者区分を選択してください',
  }),
  notes: z.string().max(500).optional(),
  guardian: guardianSchema.nullable(),
}).refine(
  (data) => data.patronType !== 'child' || data.guardian !== null,
  { message: '児童の場合は保護者情報が必要です', path: ['guardian'] }
);
```

### useCreatePatron Hook

```typescript
// useCreatePatron.ts
export const useCreatePatron = () => {
  const navigate = useNavigate();

  return useMutation({
    mutationFn: (data: PatronFormData) => patronApi.create(data),
    onSuccess: (response) => {
      toast.success('利用者を登録しました');
      navigate(`/staff/patrons/${response.patron.id}`);
    },
    onError: (error: AxiosError<ApiError>) => {
      if (error.response?.status === 422) {
        toast.error('入力内容に誤りがあります');
      } else {
        toast.error('登録に失敗しました');
      }
    },
  });
};
```

### PatronForm コンポーネント

```tsx
// PatronForm.tsx
export const PatronForm: React.FC = () => {
  const { mutate, isPending } = useCreatePatron();
  const form = useForm<PatronFormData>({
    resolver: zodResolver(patronSchema),
    defaultValues: {
      patronType: 'general',
      guardian: null,
    },
  });

  const patronType = form.watch('patronType');
  const showGuardian = patronType === 'child';

  const onSubmit = (data: PatronFormData) => {
    mutate(data);
  };

  return (
    <form onSubmit={form.handleSubmit(onSubmit)}>
      <BasicInfoSection form={form} />
      {showGuardian && <GuardianSection form={form} />}
      <FormActions isPending={isPending} />
    </form>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| PatronRegistrationPage | frontend/src/pages/staff/patrons/PatronRegistrationPage.tsx |
| PatronForm | frontend/src/features/patron/components/PatronForm.tsx |
| BasicInfoSection | frontend/src/features/patron/components/BasicInfoSection.tsx |
| GuardianSection | frontend/src/features/patron/components/GuardianSection.tsx |
| patronSchema | frontend/src/features/patron/schemas/patronSchema.ts |
| useCreatePatron | frontend/src/features/patron/hooks/useCreatePatron.ts |
| patronApi | frontend/src/features/patron/api/patronApi.ts |
| テスト | frontend/src/features/patron/__tests__/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] フォームレイアウトの確定
- [ ] 入力項目の配置確認
- [ ] エラー表示方法の確定

### Spec Tasks（詳細設計）

- [ ] patronApi 実装
- [ ] patronSchema 実装
- [ ] useCreatePatron フック実装
- [ ] PatronForm コンポーネント実装
- [ ] BasicInfoSection コンポーネント実装
- [ ] GuardianSection コンポーネント実装
- [ ] PatronRegistrationPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
