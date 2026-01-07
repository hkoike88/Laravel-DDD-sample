# ST-002: 職員アカウント無効化 UI の実装

最終更新: 2025-12-26

---

## ストーリー

**管理者として**、職員アカウントを無効化したい。
**なぜなら**、退職・異動した職員のシステムアクセスを停止したいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-005: 職員アカウント無効化機能](../epic.md) |
| ポイント | 2 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員アカウント一覧](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-accounts-list.md) |

---

## 受け入れ条件

1. [ ] 職員一覧で有効なアカウントに「無効化」ボタンが表示されること
2. [ ] 自分自身のアカウントには「無効化」ボタンが表示されないこと
3. [ ] 無効化ボタンクリックで確認ダイアログが表示されること
4. [ ] 確認ダイアログで無効化理由を入力できること
5. [ ] 無効化理由が未入力の場合エラーが表示されること
6. [ ] 無効化成功時にメッセージが表示されること
7. [ ] 無効化成功時に一覧が更新されること
8. [ ] 無効化済みアカウントには「無効」バッジが表示されること

---

## 画面仕様

### 職員一覧での表示

| 状態 | 操作ボタン |
|------|----------|
| 有効（自分以外） | [編集] [無効化] |
| 有効（自分） | [編集] |
| 無効 | [再有効化] |

### 無効化確認ダイアログ

```
┌───────────────────────────────────────────┐
│  ⚠️ 職員アカウント無効化の確認            │
├───────────────────────────────────────────┤
│                                           │
│  「田中 花子」さんのアカウントを          │
│  無効化しますか？                         │
│                                           │
│  無効化されたアカウントは                 │
│  システムにログインできなくなります。     │
│                                           │
│  無効化理由 *                             │
│  ┌───────────────────────────────────┐   │
│  │ 退職のため                        │   │
│  └───────────────────────────────────┘   │
│                                           │
│              [キャンセル] [無効化する]    │
│                                           │
└───────────────────────────────────────────┘
```

---

## 技術仕様

### コンポーネント構成

```
StaffAccountsListPage
├── StaffTable
│   └── StaffRow
│       ├── StatusBadge
│       └── ActionButtons
│           ├── EditButton
│           ├── DeactivateButton
│           └── ReactivateButton
└── DeactivateDialog
    ├── StaffInfo
    ├── ReasonInput
    └── DialogActions
```

### useDeactivateStaff Hook

```typescript
// useDeactivateStaff.ts
export const useDeactivateStaff = () => {
  const queryClient = useQueryClient();
  const [showDialog, setShowDialog] = useState(false);
  const [targetStaff, setTargetStaff] = useState<Staff | null>(null);

  const mutation = useMutation({
    mutationFn: ({ id, reason }: { id: string; reason: string }) =>
      staffApi.deactivate(id, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['staff', 'accounts'] });
      setShowDialog(false);
      toast.success('職員アカウントを無効化しました');
    },
  });

  const openDialog = (staff: Staff) => {
    setTargetStaff(staff);
    setShowDialog(true);
  };

  return {
    ...mutation,
    showDialog,
    targetStaff,
    openDialog,
    closeDialog: () => setShowDialog(false),
  };
};
```

### DeactivateDialog コンポーネント

```tsx
// DeactivateDialog.tsx
interface DeactivateDialogProps {
  staff: Staff;
  open: boolean;
  onClose: () => void;
  onConfirm: (reason: string) => void;
  isPending: boolean;
}

export const DeactivateDialog: React.FC<DeactivateDialogProps> = ({
  staff,
  open,
  onClose,
  onConfirm,
  isPending,
}) => {
  const [reason, setReason] = useState('');
  const [error, setError] = useState('');

  const handleConfirm = () => {
    if (!reason.trim()) {
      setError('無効化理由を入力してください');
      return;
    }
    onConfirm(reason);
  };

  return (
    <Dialog open={open} onClose={onClose}>
      <DialogTitle>職員アカウント無効化の確認</DialogTitle>
      <DialogContent>
        <p>「{staff.name}」さんのアカウントを無効化しますか？</p>
        <p className="warning">
          無効化されたアカウントはシステムにログインできなくなります。
        </p>
        <TextField
          label="無効化理由"
          required
          value={reason}
          onChange={(e) => setReason(e.target.value)}
          error={error}
          maxLength={200}
        />
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

### ActionButtons コンポーネント

```tsx
// ActionButtons.tsx
export const ActionButtons: React.FC<{ staff: Staff }> = ({ staff }) => {
  const currentUser = useAuthStore((state) => state.user);
  const { openDialog } = useDeactivateStaff();
  const navigate = useNavigate();

  const isSelf = currentUser?.id === staff.id;

  return (
    <div className="action-buttons">
      <Button size="sm" onClick={() => navigate(`/staff/accounts/${staff.id}/edit`)}>
        編集
      </Button>
      {staff.isActive && !isSelf && (
        <Button size="sm" variant="danger" onClick={() => openDialog(staff)}>
          無効化
        </Button>
      )}
      {!staff.isActive && (
        <Button size="sm" variant="secondary" onClick={() => /* 再有効化 */}>
          再有効化
        </Button>
      )}
    </div>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DeactivateDialog | frontend/src/features/staff/components/DeactivateDialog.tsx |
| ActionButtons（更新） | frontend/src/features/staff/components/ActionButtons.tsx |
| useDeactivateStaff | frontend/src/features/staff/hooks/useDeactivateStaff.ts |
| staffApi（更新） | frontend/src/features/staff/api/staffApi.ts |
| テスト | frontend/src/features/staff/\_\_tests\_\_/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ダイアログデザインの確定
- [ ] エラーメッセージの確定
- [ ] ボタン配置の確定

### Spec Tasks（詳細設計）

- [ ] staffApi に deactivate メソッド追加
- [ ] useDeactivateStaff フック実装
- [ ] DeactivateDialog コンポーネント実装
- [ ] ActionButtons コンポーネント更新
- [ ] StaffTable コンポーネント更新
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
