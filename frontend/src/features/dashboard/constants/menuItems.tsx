/**
 * 業務メニュー項目定義
 *
 * @feature 004-dashboard-ui
 */

import type { MenuItem } from '../types/menu'
import {
  BookIcon,
  LendingIcon,
  ReturnIcon,
  UsersIcon,
  ReservationIcon,
} from '../components/icons/MenuIcons'

/**
 * ダッシュボードに表示する業務メニュー項目
 */
export const menuItems: MenuItem[] = [
  {
    id: 'books',
    label: '蔵書管理',
    icon: <BookIcon />,
    path: '/books/manage',
    enabled: true,
    description: '蔵書の検索・登録・編集',
  },
  {
    id: 'lending',
    label: '貸出処理',
    icon: <LendingIcon />,
    path: '/loans/checkout',
    enabled: true,
    description: '図書の貸出手続き',
  },
  {
    id: 'return',
    label: '返却処理',
    icon: <ReturnIcon />,
    path: '/loans/return',
    enabled: true,
    description: '図書の返却手続き',
  },
  {
    id: 'users',
    label: '利用者管理',
    icon: <UsersIcon />,
    path: '/users',
    enabled: true,
    description: '利用者の検索・登録・編集',
  },
  {
    id: 'reservations',
    label: '予約管理',
    icon: <ReservationIcon />,
    path: '/reservations',
    enabled: true,
    description: '予約の確認・管理',
  },
]
