/**
 * 共通レイアウトコンポーネント
 *
 * サイドナビゲーションとメインコンテンツ領域を提供
 */
import { NavLink, Outlet } from 'react-router-dom';
import './Layout.css';

/**
 * ナビゲーションメニュー項目の型
 */
type NavItem =
  | { path: string; label: string; icon: string; children?: never }
  | { path?: never; label: string; icon: string; children: { path: string; label: string }[] };

/**
 * ナビゲーションメニュー項目
 */
const navItems: NavItem[] = [
  { path: '/', label: 'ダッシュボード', icon: '🏠' },
  {
    label: '蔵書管理（EP-01）',
    icon: '📚',
    children: [
      { path: '/books/search', label: '蔵書検索' },
      { path: '/books/register', label: '蔵書登録' },
    ],
  },
  {
    label: '貸出・返却（EP-02）',
    icon: '📖',
    children: [
      { path: '/lending', label: '貸出処理' },
      { path: '/return', label: '返却処理' },
    ],
  },
  {
    label: '予約管理（EP-03）',
    icon: '📋',
    children: [
      { path: '/reservations/register', label: '予約登録' },
      { path: '/reservations/manage', label: '予約管理' },
    ],
  },
  {
    label: '利用者管理（EP-04）',
    icon: '👤',
    children: [
      { path: '/users/search', label: '利用者検索' },
      { path: '/users/register', label: '利用者登録' },
    ],
  },
];

export default function Layout() {
  return (
    <div className="layout">
      <aside className="sidebar">
        <div className="sidebar-header">
          <h1>📖 青空市立図書館</h1>
          <p>業務システム プロトタイプ</p>
        </div>
        <nav className="sidebar-nav">
          {navItems.map((item, index) => (
            <div key={index} className="nav-group">
              {item.path !== undefined ? (
                <NavLink
                  to={item.path}
                  className={({ isActive }) =>
                    `nav-item ${isActive ? 'active' : ''}`
                  }
                >
                  <span className="nav-icon">{item.icon}</span>
                  <span className="nav-label">{item.label}</span>
                </NavLink>
              ) : item.children !== undefined ? (
                <>
                  <div className="nav-category">
                    <span className="nav-icon">{item.icon}</span>
                    <span className="nav-label">{item.label}</span>
                  </div>
                  <div className="nav-children">
                    {item.children.map((child) => (
                      <NavLink
                        key={child.path}
                        to={child.path}
                        className={({ isActive }) =>
                          `nav-item nav-child ${isActive ? 'active' : ''}`
                        }
                      >
                        {child.label}
                      </NavLink>
                    ))}
                  </div>
                </>
              ) : null}
            </div>
          ))}
        </nav>
        <div className="sidebar-footer">
          <p>LIB-001 MVP プロトタイプ</p>
          <p>© 2024 青空市</p>
        </div>
      </aside>
      <main className="main-content">
        <Outlet />
      </main>
    </div>
  );
}
