# ST-002: ダッシュボード UI の実装

最終更新: 2025-12-26

---

## ストーリー

**図書館職員として**、ログイン後にダッシュボードを確認したい。
**なぜなら**、本日の業務状況を把握し、必要な機能にすぐアクセスしたいからだ。

---

## 関連情報

| 項目 | 値 |
|------|-----|
| Epic | [EPIC-002: 職員ダッシュボード機能](../epic.md) |
| ポイント | 3 |
| 優先度 | Must |
| ステータス | Planned |
| ワイヤーフレーム | [職員ダッシュボード](../../../../../01_vision/initiatives/INI-001/ui/wireframes/staff-dashboard.md) |

---

## 受け入れ条件

1. [ ] ヘッダーにログイン職員名が表示されること
2. [ ] ヘッダーにログアウトボタンが表示されること
3. [ ] 本日のサマリー（貸出・返却・延滞・予約）がカード形式で表示されること
4. [ ] 業務メニューがグリッド形式で表示されること
5. [ ] 各メニューをクリックすると対応する画面に遷移すること
6. [ ] データ取得中はローディング表示されること
7. [ ] データ取得エラー時はエラーメッセージが表示されること
8. [ ] キーボードナビゲーションに対応していること

---

## 画面仕様

### UI 要素

| 要素ID | 種類 | 要素名 | 説明 |
|--------|------|--------|------|
| header | header | ヘッダー | ロゴ、職員名、ログアウト |
| user-name | text | 職員名 | ログイン中の職員名 |
| btn-logout | button | ログアウト | ログアウト処理を実行 |
| summary-cards | section | サマリーカード | 業務サマリー表示 |
| card-loans | div | 貸出件数 | 本日の貸出件数 |
| card-returns | div | 返却件数 | 本日の返却件数 |
| card-overdue | div | 延滞件数 | 延滞中の図書数 |
| card-reservations | div | 予約件数 | 予約待ち件数 |
| menu-grid | section | 業務メニュー | 業務機能へのナビゲーション |
| admin-menu | section | 管理メニュー | 管理者向けメニュー |

### サマリーカード

| カード | アイコン | ラベル | 値の例 |
|--------|---------|--------|--------|
| 貸出 | 📤 | 本日の貸出 | 15件 |
| 返却 | 📥 | 本日の返却 | 12件 |
| 延滞 | ⚠️ | 延滞中 | 3件 |
| 予約 | 📅 | 予約待ち | 8件 |

### 業務メニュー

| メニュー | アイコン | ラベル | 遷移先 |
|---------|---------|--------|--------|
| 蔵書検索 | 🔍 | 蔵書検索 | `/staff/books/search` |
| 蔵書登録 | 📚 | 蔵書登録 | `/staff/books/new` |
| 貸出処理 | 📤 | 貸出処理 | `/staff/loans/new` |
| 返却処理 | 📥 | 返却処理 | `/staff/returns` |
| 予約管理 | 📅 | 予約管理 | `/staff/reservations` |
| 利用者管理 | 👤 | 利用者管理 | `/staff/patrons` |

---

## 技術仕様

### コンポーネント構成

```
DashboardPage
├── StaffLayout
│   ├── Header
│   │   ├── Logo
│   │   ├── UserMenu
│   │   └── LogoutButton
│   └── Main
│       ├── SummarySection
│       │   └── SummaryCard (x4)
│       ├── MenuSection
│       │   └── MenuCard (x6)
│       └── AdminMenuSection (条件付き)
│           └── MenuCard
```

### useDashboard Hook

```typescript
// useDashboard.ts
import { useQuery } from '@tanstack/react-query';
import { dashboardApi } from '@/features/dashboard/api';

export const useDashboard = () => {
  return useQuery({
    queryKey: ['dashboard'],
    queryFn: dashboardApi.getDashboard,
    staleTime: 0, // 常に最新データを取得
  });
};
```

### DashboardPage コンポーネント

```tsx
// DashboardPage.tsx
export const DashboardPage: React.FC = () => {
  const { data, isLoading, error } = useDashboard();
  const user = useAuthStore((state) => state.user);

  if (isLoading) {
    return <LoadingSpinner />;
  }

  if (error) {
    return <ErrorMessage message="データの取得に失敗しました" />;
  }

  return (
    <div className="dashboard">
      <SummarySection summary={data.summary} />
      <MenuSection />
      {user?.role === 'admin' && <AdminMenuSection />}
    </div>
  );
};
```

### SummaryCard コンポーネント

```tsx
// SummaryCard.tsx
interface SummaryCardProps {
  icon: string;
  label: string;
  value: number;
  variant?: 'default' | 'warning';
}

export const SummaryCard: React.FC<SummaryCardProps> = ({
  icon,
  label,
  value,
  variant = 'default',
}) => {
  return (
    <div className={`summary-card summary-card--${variant}`}>
      <span className="summary-card__icon">{icon}</span>
      <span className="summary-card__label">{label}</span>
      <span className="summary-card__value">{value}件</span>
    </div>
  );
};
```

---

## 成果物

| 成果物 | 配置場所 |
|--------|---------|
| DashboardPage | frontend/src/pages/staff/DashboardPage.tsx |
| SummarySection | frontend/src/features/dashboard/components/SummarySection.tsx |
| SummaryCard | frontend/src/features/dashboard/components/SummaryCard.tsx |
| MenuSection | frontend/src/features/dashboard/components/MenuSection.tsx |
| MenuCard | frontend/src/features/dashboard/components/MenuCard.tsx |
| useDashboard | frontend/src/features/dashboard/hooks/useDashboard.ts |
| dashboardApi | frontend/src/features/dashboard/api/dashboardApi.ts |
| StaffLayout | frontend/src/components/layout/StaffLayout.tsx |
| テスト | frontend/src/features/dashboard/\_\_tests\_\_/ |

---

## タスク

### Design Tasks（外部設計）

- [ ] ワイヤーフレームの確認
- [ ] カラースキームの確定
- [ ] アイコンの選定

### Spec Tasks（詳細設計）

- [ ] dashboardApi 実装
- [ ] useDashboard フック実装
- [ ] SummaryCard コンポーネント実装
- [ ] SummarySection コンポーネント実装
- [ ] MenuCard コンポーネント実装
- [ ] MenuSection コンポーネント実装
- [ ] StaffLayout 実装
- [ ] DashboardPage 実装
- [ ] ルーティング設定
- [ ] コンポーネントテスト作成

---

## 変更履歴

| 日付 | 変更内容 |
|------|---------|
| 2025-12-26 | 初版作成 |
