# ST-002: 職員アカウント編集 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**管理者として**、職員アカウント編集画面で職員情報を更新したい。
**なぜなら**、職員の異動や役割変更に対応したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-004: 職員アカウント編集機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員アカウント編集](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-edit.md) |

---

## 受け入れ条件

1. [ ] 既存の職員情報がフォームに表示されること
2. [ ] 氏名、メールアドレス、権限を編集できること
3. [ ] 自分自身の編集時は権限選択が無効化されること
4. [ ] 保存ボタンで更新が実行されること
5. [ ] 更新成功時に職員一覧画面にリダイレクトされること
6. [ ] バリデーションエラー時にフィールドごとにエラーが表示されること
7. [ ] 競合エラー時に再読み込みを促すダイアログが表示されること
8. [ ] キャンセルボタンで一覧画面に戻れること
9. [ ] パスワードリセットボタンが表示されること

---

## 画面仕様

### UI 要素

| 要素ID | 種類 | 要素名 | 必須 | 説明 |
|--------|------|--------|------|------|
| name | input[text] | 氏名 | ○ | 職員の氏名 |
| email | input[email] | メールアドレス | ○ | ログイン用メールアドレス |
| role | radio | 権限 | ○ | 一般職員 / 管理者 |
| btn-reset-password | button | パスワードリセット | - | パスワードをリセット |
| btn-submit | button | 保存 | - | 更新を実行 |
| btn-cancel | button | キャンセル | - | 一覧画面に戻る |

### 状態による制御

| 条件 | 制御内容 |
|------|---------|
| 自分自身を編集 | 権限選択を無効化、注記を表示 |
| 最後の管理者 | 権限選択を無効化、注記を表示 |
| 無効化済みアカウント | 全フィールド編集不可、注記を表示 |

### 競合エラーダイアログ

```
┌───────────────────────────────────────────┐
│  ⚠️ 更新の競合                            │
├───────────────────────────────────────────┤
│                                           │
│  この職員情報は他のユーザーによって       │
│  更新されています。                       │
│                                           │
│  最新の情報を読み込みますか？             │
│                                           │
│              [キャンセル] [再読み込み]    │
│                                           │
└───────────────────────────────────────────┘
```

---

## 技術仕様

### コンポーネント構成

```
StaffAccountsEditPage
├── PageHeader
├── StaffEditForm
│   ├── TextField (氏名)
│   ├── TextField (メールアドレス)
│   ├── RadioGroup (権限)
│   ├── ResetPasswordButton
│   └── FormActions
│       ├── CancelButton
│       └── SubmitButton
├── ConflictDialog
└── PasswordDialog
```

### useStaffDetail Hook

```typescript
// useStaffDetail.ts
export const useStaffDetail = (id: string) => {
  return useQuery({
    queryKey: ['staff', 'accounts', id],
    queryFn: () => staffApi.getDetail(id),
  });
};
```

### useUpdateStaff Hook

```typescript
// useUpdateStaff.ts
export const useUpdateStaff = (id: string) => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [showConflictDialog, setShowConflictDialog] = useState(false);

  const mutation = useMutation({
    mutationFn: (data: UpdateStaffFormData) => staffApi.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['staff', 'accounts'] });
      navigate('/staff/accounts');
    },
    onError: (error: ApiError) => {
      if (error.status === 409) {
        setShowConflictDialog(true);
      }
    },
  });

  const handleReload = () => {
    queryClient.invalidateQueries({ queryKey: ['staff', 'accounts', id] });
    setShowConflictDialog(false);
  };

  return {
    ...mutation,
    showConflictDialog,
    setShowConflictDialog,
    handleReload,
  };
};
```

### StaffAccountsEditPage コンポーネント

```tsx
// StaffAccountsEditPage.tsx
export const StaffAccountsEditPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { data: staffDetail, isLoading } = useStaffDetail(id!);
  const currentUser = useAuthStore((state) => state.user);
  const isSelf = currentUser?.id === id;

  const { register, handleSubmit, formState: { errors } } = useForm<UpdateStaffFormData>({
    resolver: zodResolver(updateStaffSchema),
    values: staffDetail?.staff,
  });

  const { mutate: updateStaff, isPending } = useUpdateStaff(id!);

  if (isLoading) {
    return <LoadingSpinner />;
  }

  return (
    <div className="staff-edit">
      <form onSubmit={handleSubmit((data) => updateStaff(data))}>
        <TextField label="氏名" {...register('name')} error={errors.name?.message} />
        <TextField label="メールアドレス" {...register('email')} error={errors.email?.message} />
        <RadioGroup
          label="権限"
          {...register('role')}
          disabled={isSelf}
          options={[
            { value: 'staff', label: '一般職員' },
            { value: 'admin', label: '管理者' },
          ]}
        />
        {isSelf && <p className="note">自分自身の権限は変更できません</p>}

        <FormActions>
          <Button variant="secondary" onClick={() => navigate('/staff/accounts')}>
            キャンセル
          </Button>
          <Button type="submit" loading={isPending}>保存</Button>
        </FormActions>
      </form>
    </div>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| StaffAccountsEditPage | frontend/src/pages/staff/StaffAccountsEditPage.tsx |
| StaffEditForm | frontend/src/features/staff/components/StaffEditForm.tsx |
| ConflictDialog | frontend/src/features/staff/components/ConflictDialog.tsx |
| updateStaffSchema | frontend/src/features/staff/schemas/updateStaffSchema.ts |
| useStaffDetail | frontend/src/features/staff/hooks/useStaffDetail.ts |
| useUpdateStaff | frontend/src/features/staff/hooks/useUpdateStaff.ts |
| テスト | frontend/src/features/staff/\_\_tests\_\_/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ワイヤーフレームの確認
- [ ] 競合ダイアログデザインの確定
- [ ] 制限事項の表示方法確定

### Spec Tasks（詳細設計）

- [ ] updateStaffSchema 実装
- [ ] staffApi に getDetail/update メソッド追加
- [ ] useStaffDetail フック実装
- [ ] useUpdateStaff フック実装
- [ ] StaffEditForm コンポーネント実装
- [ ] ConflictDialog コンポーネント実装
- [ ] StaffAccountsEditPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
