/**
 * 管理者専用メニュー項目定義
 *
 * @feature 003-role-based-menu
 */

import type { MenuItem } from '../types/menu'
import { StaffIcon } from '../components/icons/MenuIcons'

/**
 * 管理者専用メニュー項目
 *
 * 管理者権限を持つ職員のみがアクセス可能な機能のメニュー項目。
 */
export const adminMenuItems: MenuItem[] = [
  {
    id: 'staff-accounts',
    label: '職員管理',
    icon: <StaffIcon />,
    path: '/staff/accounts',
    enabled: true,
    description: '職員アカウントの管理',
  },
]
