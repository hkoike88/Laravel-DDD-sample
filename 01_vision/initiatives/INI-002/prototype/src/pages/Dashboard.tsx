/**
 * ダッシュボード画面
 *
 * 業務の概要と統計情報を表示
 */
import { Link } from 'react-router-dom';
import {
  mockBooks,
  mockUsers,
  mockLendings,
  mockReservations,
} from '../data/mockData';

export default function Dashboard() {
  // 統計情報の計算
  const stats = {
    totalBooks: mockBooks.length,
    availableBooks: mockBooks.filter((b) => b.status === '貸出可').length,
    lentBooks: mockBooks.filter((b) => b.status === '貸出中').length,
    totalUsers: mockUsers.length,
    activeUsers: mockUsers.filter((u) => u.status === '有効').length,
    activeLendings: mockLendings.filter((l) => !l.returnedAt).length,
    overdueCount: mockLendings.filter((l) => l.isOverdue && !l.returnedAt).length,
    pendingReservations: mockReservations.filter(
      (r) => r.status === '予約中' || r.status === '取り置き中'
    ).length,
    holdingReservations: mockReservations.filter(
      (r) => r.status === '取り置き中'
    ).length,
  };

  return (
    <div className="dashboard">
      <header className="page-header">
        <h1>ダッシュボード</h1>
        <p className="subtitle">青空市立中央図書館 業務システム</p>
      </header>

      <section className="dashboard-stats">
        <h2>📊 本日の状況</h2>
        <div className="stats-grid">
          <div className="stat-card">
            <h3>蔵書</h3>
            <div className="stat-value">{stats.totalBooks}</div>
            <div className="stat-detail">
              貸出可: {stats.availableBooks} / 貸出中: {stats.lentBooks}
            </div>
          </div>
          <div className="stat-card">
            <h3>利用者</h3>
            <div className="stat-value">{stats.totalUsers}</div>
            <div className="stat-detail">有効: {stats.activeUsers}</div>
          </div>
          <div className="stat-card">
            <h3>現在の貸出</h3>
            <div className="stat-value">{stats.activeLendings}</div>
            <div className="stat-detail warning">
              延滞: {stats.overdueCount}件
            </div>
          </div>
          <div className="stat-card">
            <h3>予約</h3>
            <div className="stat-value">{stats.pendingReservations}</div>
            <div className="stat-detail">
              取り置き中: {stats.holdingReservations}
            </div>
          </div>
        </div>
      </section>

      <section className="dashboard-actions">
        <h2>🚀 クイックアクセス</h2>
        <div className="action-grid">
          <Link to="/books/search" className="action-card">
            <div className="action-icon">📚</div>
            <h3>蔵書検索</h3>
            <p>蔵書を検索して所蔵状況を確認</p>
          </Link>
          <Link to="/lending" className="action-card">
            <div className="action-icon">📖</div>
            <h3>貸出処理</h3>
            <p>利用者への貸出を処理</p>
          </Link>
          <Link to="/return" className="action-card">
            <div className="action-icon">↩️</div>
            <h3>返却処理</h3>
            <p>返却された本の処理</p>
          </Link>
          <Link to="/reservations/manage" className="action-card">
            <div className="action-icon">📋</div>
            <h3>予約管理</h3>
            <p>予約の確認と取り置き管理</p>
          </Link>
          <Link to="/users/search" className="action-card">
            <div className="action-icon">👤</div>
            <h3>利用者検索</h3>
            <p>利用者情報の検索と確認</p>
          </Link>
          <Link to="/users/register" className="action-card">
            <div className="action-icon">➕</div>
            <h3>利用者登録</h3>
            <p>新規利用者の登録</p>
          </Link>
        </div>
      </section>

      <section className="dashboard-alerts">
        <h2>⚠️ 対応が必要な項目</h2>
        <div className="alert-list">
          {stats.overdueCount > 0 && (
            <div className="alert-item warning">
              <span className="alert-badge">{stats.overdueCount}</span>
              延滞中の貸出があります
              <Link to="/return">→ 返却処理へ</Link>
            </div>
          )}
          {stats.holdingReservations > 0 && (
            <div className="alert-item info">
              <span className="alert-badge">{stats.holdingReservations}</span>
              取り置き中の予約があります
              <Link to="/reservations/manage">→ 予約管理へ</Link>
            </div>
          )}
        </div>
      </section>

      <section className="dashboard-info">
        <h2>📋 業務ルール</h2>
        <div className="info-grid">
          <div className="info-item">
            <h4>貸出上限</h4>
            <p>1人5冊まで</p>
          </div>
          <div className="info-item">
            <h4>貸出期間</h4>
            <p>一般図書: 14日間 / 新刊・雑誌: 7日間</p>
          </div>
          <div className="info-item">
            <h4>予約上限</h4>
            <p>1人3冊まで</p>
          </div>
          <div className="info-item">
            <h4>取り置き期限</h4>
            <p>連絡後7日間</p>
          </div>
        </div>
      </section>
    </div>
  );
}
