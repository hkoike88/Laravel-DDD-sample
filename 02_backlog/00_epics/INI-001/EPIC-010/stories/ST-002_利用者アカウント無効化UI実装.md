# ST-002: 利用者アカウント無効化 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、利用者アカウントを無効化したい。
**なぜなら**、転出や規約違反などで利用資格を失った利用者のアクセスを停止したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-010: 利用者アカウント無効化機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |

---

## 受け入れ条件

1. [ ] 利用者詳細画面に有効なアカウントの場合「無効化」ボタンが表示されること
2. [ ] 無効化ボタンクリックで確認ダイアログが表示されること
3. [ ] 確認ダイアログで無効化理由を選択できること
4. [ ] 「その他」選択時は備考入力が必須であること
5. [ ] 未返却図書がある場合は警告が表示されること
6. [ ] 無効化成功時にメッセージが表示されること
7. [ ] 無効化成功時に一覧画面に遷移すること
8. [ ] 無効化済みアカウントには「無効」バッジが表示されること

---

## 画面仕様

### 無効化確認ダイアログ

```
┌───────────────────────────────────────────┐
│  ⚠️ 利用者アカウント無効化の確認          │
├───────────────────────────────────────────┤
│                                           │
│  「山田 太郎」さん（P2025000001）の        │
│  アカウントを無効化しますか？             │
│                                           │
│  無効化されたアカウントは                 │
│  図書の貸出ができなくなります。           │
│                                           │
│  無効化理由 *                             │
│  ┌───────────────────────────────────┐   │
│  │ 転出                           ▼  │   │
│  └───────────────────────────────────┘   │
│                                           │
│  備考                                     │
│  ┌───────────────────────────────────┐   │
│  │ 転出届確認済み                     │   │
│  │                                    │   │
│  └───────────────────────────────────┘   │
│                                           │
│              [キャンセル] [無効化する]    │
│                                           │
└───────────────────────────────────────────┘
```

### 未返却警告表示

```
│  ⚠️ 未返却図書があります                  │
│  ┌───────────────────────────────────┐   │
│  │ ・プログラミング入門（返却期限: 12/20）│
│  │ ・データベース設計（返却期限: 12/25）  │
│  └───────────────────────────────────┘   │
│                                           │
│  未返却のまま無効化を続行しますか？       │
```

---

## 技術仕様

### コンポーネント構成

```
PatronDetailPage
├── PatronInfo
├── ActionButtons
│   ├── EditButton
│   ├── RenewButton
│   └── DeactivateButton（有効時のみ）
└── DeactivatePatronDialog
    ├── PatronSummary
    ├── UnreturnedBooksWarning（該当時）
    ├── ReasonSelect
    ├── NotesTextarea
    └── DialogActions
```

### 無効化理由選択肢

```typescript
// deactivationReasons.ts
export const DEACTIVATION_REASONS = [
  { value: 'relocation', label: '転出' },
  { value: 'request', label: '本人希望' },
  { value: 'expired', label: '有効期限切れ（長期未更新）' },
  { value: 'violation', label: '規約違反' },
  { value: 'other', label: 'その他' },
] as const;
```

### useDeactivatePatron Hook

```typescript
// useDeactivatePatron.ts
export const useDeactivatePatron = () => {
  const queryClient = useQueryClient();
  const navigate = useNavigate();
  const [showDialog, setShowDialog] = useState(false);
  const [targetPatron, setTargetPatron] = useState<Patron | null>(null);

  const mutation = useMutation({
    mutationFn: ({ id, reason, notes }: DeactivateParams) =>
      patronApi.deactivate(id, reason, notes),
    onSuccess: (response) => {
      queryClient.invalidateQueries({ queryKey: ['patrons'] });
      setShowDialog(false);

      if (response.warning) {
        toast.warning(response.warning);
      }
      toast.success('利用者アカウントを無効化しました');
      navigate('/staff/patrons');
    },
    onError: (error: AxiosError<ApiError>) => {
      if (error.response?.status === 422) {
        toast.error(error.response.data.message);
      } else {
        toast.error('無効化に失敗しました');
      }
    },
  });

  const openDialog = (patron: Patron) => {
    setTargetPatron(patron);
    setShowDialog(true);
  };

  return {
    ...mutation,
    showDialog,
    targetPatron,
    openDialog,
    closeDialog: () => setShowDialog(false),
  };
};
```

### DeactivatePatronDialog コンポーネント

```tsx
// DeactivatePatronDialog.tsx
export const DeactivatePatronDialog: React.FC<DeactivatePatronDialogProps> = ({
  patron,
  open,
  onClose,
  onConfirm,
  isPending,
}) => {
  const [reason, setReason] = useState<string>('');
  const [notes, setNotes] = useState('');
  const [error, setError] = useState('');

  const handleConfirm = () => {
    if (!reason) {
      setError('無効化理由を選択してください');
      return;
    }
    if (reason === 'other' && !notes.trim()) {
      setError('その他を選択した場合は備考を入力してください');
      return;
    }
    onConfirm(reason, notes);
  };

  return (
    <Dialog open={open} onClose={onClose}>
      <DialogTitle>利用者アカウント無効化の確認</DialogTitle>
      <DialogContent>
        <p>
          「{patron.name}」さん（{patron.patronNumber}）の
          アカウントを無効化しますか？
        </p>
        <p className="warning">
          無効化されたアカウントは図書の貸出ができなくなります。
        </p>

        <Select
          label="無効化理由"
          required
          value={reason}
          onChange={(e) => setReason(e.target.value)}
          options={DEACTIVATION_REASONS}
        />

        <TextField
          label="備考"
          required={reason === 'other'}
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          multiline
          rows={3}
          maxLength={500}
        />

        {error && <FormError message={error} />}
      </DialogContent>
      <DialogActions>
        <Button variant="secondary" onClick={onClose}>
          キャンセル
        </Button>
        <Button variant="danger" onClick={handleConfirm} loading={isPending}>
          無効化する
        </Button>
      </DialogActions>
    </Dialog>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DeactivatePatronDialog | frontend/src/features/patron/components/DeactivatePatronDialog.tsx |
| useDeactivatePatron | frontend/src/features/patron/hooks/useDeactivatePatron.ts |
| deactivationReasons | frontend/src/features/patron/constants/deactivationReasons.ts |
| patronApi（更新） | frontend/src/features/patron/api/patronApi.ts |
| PatronDetailPage（更新） | frontend/src/pages/staff/patrons/PatronDetailPage.tsx |
| テスト | frontend/src/features/patron/__tests__/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ダイアログデザインの確定
- [ ] 警告表示方法の確定
- [ ] ボタン配置の確認

### Spec Tasks（詳細設計）

- [ ] deactivationReasons 定数定義
- [ ] patronApi に deactivate メソッド追加
- [ ] useDeactivatePatron フック実装
- [ ] DeactivatePatronDialog コンポーネント実装
- [ ] PatronDetailPage にボタン追加
- [ ] StatusBadge コンポーネント（有効/無効表示）
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
