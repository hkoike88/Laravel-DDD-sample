# ST-002: 利用者アカウント編集 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、利用者情報を編集したい。
**なぜなら**、利用者の住所変更や連絡先変更に対応したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-009: 利用者アカウント編集機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] 利用者詳細画面から「編集」ボタンで編集画面に遷移できること
2. [ ] 既存の利用者情報がフォームに表示されること
3. [ ] 利用者番号は表示のみで編集不可であること
4. [ ] 入力エラー時にエラーメッセージが表示されること
5. [ ] 保存成功時に利用者詳細画面に遷移すること
6. [ ] 保存成功時に「利用者情報を更新しました」と表示されること
7. [ ] キャンセルボタンで詳細画面に戻ること

---

## 画面仕様

### 利用者編集フォーム

```
┌─────────────────────────────────────────────────────────────┐
│  利用者編集                                                 │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  利用者番号                                                 │
│  ┌───────────────────────────────────────────────────────┐ │
│  │ P2025000001                              （変更不可） │ │
│  └───────────────────────────────────────────────────────┘ │
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
│  └───────────────────────────────────────────────────────┘ │
│                                                             │
│  有効期限: 2026年12月26日                                   │
│                                                             │
│                          [キャンセル]  [保存]               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 技術仕様

### コンポーネント構成

```
PatronEditPage
├── PageHeader
├── PatronEditForm
│   ├── PatronNumberDisplay（読み取り専用）
│   ├── BasicInfoSection
│   │   ├── NameInput
│   │   ├── NameKanaInput
│   │   ├── BirthDateInput
│   │   ├── AddressInput
│   │   ├── PhoneNumberInput
│   │   ├── PatronTypeSelect
│   │   └── NotesTextarea
│   ├── GuardianSection（条件付き表示）
│   ├── ExpiryDateDisplay
│   └── FormActions
│       ├── CancelButton
│       └── SubmitButton
└── SuccessToast
```

### usePatron Hook（詳細取得）

```typescript
// usePatron.ts
export const usePatron = (id: string) => {
  return useQuery({
    queryKey: ['patron', id],
    queryFn: () => patronApi.getById(id),
  });
};
```

### useUpdatePatron Hook

```typescript
// useUpdatePatron.ts
export const useUpdatePatron = (id: string) => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();

  return useMutation({
    mutationFn: (data: PatronFormData) => patronApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['patron', id] });
      toast.success('利用者情報を更新しました');
      navigate(`/staff/patrons/${id}`);
    },
    onError: (error: AxiosError<ApiError>) => {
      if (error.response?.status === 422) {
        toast.error('入力内容に誤りがあります');
      } else if (error.response?.status === 404) {
        toast.error('利用者が見つかりません');
      } else {
        toast.error('更新に失敗しました');
      }
    },
  });
};
```

### PatronEditForm コンポーネント

```tsx
// PatronEditForm.tsx
export const PatronEditForm: React.FC<{ patron: Patron }> = ({ patron }) => {
  const { mutate, isPending } = useUpdatePatron(patron.id);
  const form = useForm<PatronFormData>({
    resolver: zodResolver(patronSchema),
    defaultValues: {
      name: patron.name,
      nameKana: patron.nameKana,
      birthDate: patron.birthDate,
      address: patron.address,
      phoneNumber: patron.phoneNumber,
      patronType: patron.patronType,
      notes: patron.notes ?? '',
      guardian: patron.guardian,
    },
  });

  const patronType = form.watch('patronType');
  const showGuardian = patronType === 'child';

  const onSubmit = (data: PatronFormData) => {
    mutate(data);
  };

  return (
    <form onSubmit={form.handleSubmit(onSubmit)}>
      <PatronNumberDisplay value={patron.patronNumber} />
      <BasicInfoSection form={form} />
      {showGuardian && <GuardianSection form={form} />}
      <ExpiryDateDisplay value={patron.expiresAt} />
      <FormActions isPending={isPending} />
    </form>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| PatronEditPage | frontend/src/pages/staff/patrons/PatronEditPage.tsx |
| PatronEditForm | frontend/src/features/patron/components/PatronEditForm.tsx |
| PatronNumberDisplay | frontend/src/features/patron/components/PatronNumberDisplay.tsx |
| ExpiryDateDisplay | frontend/src/features/patron/components/ExpiryDateDisplay.tsx |
| usePatron | frontend/src/features/patron/hooks/usePatron.ts |
| useUpdatePatron | frontend/src/features/patron/hooks/useUpdatePatron.ts |
| patronApi（更新） | frontend/src/features/patron/api/patronApi.ts |
| テスト | frontend/src/features/patron/__tests__/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] 編集フォームレイアウトの確定
- [ ] 読み取り専用項目の表示方法確定

### Spec Tasks（詳細設計）

- [ ] patronApi に getById, update メソッド追加
- [ ] usePatron フック実装
- [ ] useUpdatePatron フック実装
- [ ] PatronNumberDisplay コンポーネント実装
- [ ] ExpiryDateDisplay コンポーネント実装
- [ ] PatronEditForm コンポーネント実装
- [ ] PatronEditPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
