/**
 * UC-001-008: 利用者検索画面
 *
 * 職員が利用者情報を検索・確認する
 */
import { useState } from 'react';
import { mockUsers, mockLendings, mockReservations, getBookById } from '../../data/mockData';
import type { User, UserStatus } from '../../types';

/**
 * ステータスバッジのスタイルクラスを取得
 */
function getStatusClass(status: UserStatus): string {
  const classes: Record<UserStatus, string> = {
    有効: 'active',
    期限切れ: 'expired',
    停止中: 'suspended',
  };
  return classes[status] || '';
}

export default function UserSearch() {
  const [searchQuery, setSearchQuery] = useState('');
  const [results, setResults] = useState<User[]>([]);
  const [hasSearched, setHasSearched] = useState(false);
  const [selectedUser, setSelectedUser] = useState<User | null>(null);

  /**
   * 検索実行
   */
  const handleSearch = () => {
    const query = searchQuery.toLowerCase().trim();
    if (!query) {
      setResults([]);
      setHasSearched(true);
      return;
    }

    const filtered = mockUsers.filter(
      (user) =>
        user.name.toLowerCase().includes(query) ||
        user.nameKana.toLowerCase().includes(query) ||
        user.cardNumber.includes(query) ||
        user.phone.includes(query)
    );

    setResults(filtered);
    setHasSearched(true);
  };

  /**
   * 利用者の貸出情報を取得
   */
  const getUserLendings = (userId: string) => {
    return mockLendings
      .filter((l) => l.userId === userId && !l.returnedAt)
      .map((l) => ({
        ...l,
        book: getBookById(l.bookId),
      }));
  };

  /**
   * 利用者の予約情報を取得
   */
  const getUserReservations = (userId: string) => {
    return mockReservations
      .filter((r) => r.userId === userId && (r.status === '予約中' || r.status === '取り置き中'))
      .map((r) => ({
        ...r,
        book: getBookById(r.bookId),
      }));
  };

  return (
    <div className="user-search">
      <header className="page-header">
        <h1>👤 利用者検索</h1>
        <p className="subtitle">UC-001-008 / EP-04 利用者管理</p>
      </header>

      {/* 検索フォーム */}
      <div className="section-box">
        <h3>検索条件</h3>
        <div className="form-row">
          <div className="form-group" style={{ flex: 2 }}>
            <label>氏名 / カード番号 / 電話番号</label>
            <input
              type="text"
              placeholder="検索キーワードを入力..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
            />
          </div>
        </div>
        <div className="form-actions">
          <button className="btn btn-primary" onClick={handleSearch}>
            🔍 検索
          </button>
          <button
            className="btn btn-secondary"
            onClick={() => {
              setSearchQuery('');
              setResults([]);
              setHasSearched(false);
              setSelectedUser(null);
            }}
          >
            クリア
          </button>
        </div>
      </div>

      {/* 検索結果 */}
      {hasSearched && (
        <div className="section-box">
          <h3>検索結果 ({results.length}件)</h3>
          {results.length > 0 ? (
            <table className="data-table">
              <thead>
                <tr>
                  <th>カード番号</th>
                  <th>氏名</th>
                  <th>電話番号</th>
                  <th>有効期限</th>
                  <th>ステータス</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                {results.map((user) => (
                  <tr key={user.id}>
                    <td>{user.cardNumber}</td>
                    <td>
                      {user.name}
                      <br />
                      <small style={{ color: '#666' }}>{user.nameKana}</small>
                    </td>
                    <td>{user.phone}</td>
                    <td>{user.expiresAt}</td>
                    <td>
                      <span className={`status-badge ${getStatusClass(user.status)}`}>
                        {user.status}
                      </span>
                    </td>
                    <td>
                      <button className="btn" onClick={() => setSelectedUser(user)}>
                        詳細
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="empty-state">
              <div className="empty-state-icon">👤</div>
              <h3>該当する利用者がいません</h3>
              <p>検索条件を変更して再度お試しください</p>
            </div>
          )}
        </div>
      )}

      {/* 利用者詳細 */}
      {selectedUser && (
        <div className="section-box">
          <div className="card-header">
            <h3 className="card-title">👤 利用者詳細</h3>
            <button className="btn" onClick={() => setSelectedUser(null)}>
              閉じる
            </button>
          </div>

          <div className="two-column">
            {/* 基本情報 */}
            <div>
              <h4>基本情報</h4>
              <div className="detail-list">
                <div className="detail-item">
                  <span className="detail-label">利用者ID</span>
                  <span className="detail-value">{selectedUser.id}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">カード番号</span>
                  <span className="detail-value">{selectedUser.cardNumber}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">氏名</span>
                  <span className="detail-value">
                    {selectedUser.name}（{selectedUser.nameKana}）
                  </span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">生年月日</span>
                  <span className="detail-value">{selectedUser.birthDate}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">住所</span>
                  <span className="detail-value">{selectedUser.address}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">電話番号</span>
                  <span className="detail-value">{selectedUser.phone}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">メール</span>
                  <span className="detail-value">{selectedUser.email || '未登録'}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">登録日</span>
                  <span className="detail-value">{selectedUser.registeredAt}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">有効期限</span>
                  <span className="detail-value">{selectedUser.expiresAt}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">ステータス</span>
                  <span className="detail-value">
                    <span className={`status-badge ${getStatusClass(selectedUser.status)}`}>
                      {selectedUser.status}
                    </span>
                  </span>
                </div>
                {selectedUser.memo && (
                  <div className="detail-item">
                    <span className="detail-label">備考</span>
                    <span className="detail-value">{selectedUser.memo}</span>
                  </div>
                )}
              </div>

              <div className="form-actions mt-16">
                {selectedUser.status === '期限切れ' && (
                  <button className="btn btn-primary">🔄 カード更新</button>
                )}
                <button className="btn">✏️ 情報編集</button>
              </div>
            </div>

            {/* 利用状況 */}
            <div>
              <h4>現在の貸出 ({getUserLendings(selectedUser.id).length}冊)</h4>
              {getUserLendings(selectedUser.id).length > 0 ? (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>タイトル</th>
                      <th>返却期限</th>
                    </tr>
                  </thead>
                  <tbody>
                    {getUserLendings(selectedUser.id).map((l) => (
                      <tr key={l.id}>
                        <td>{l.book?.title}</td>
                        <td style={{ color: l.isOverdue ? '#c00' : 'inherit' }}>
                          {l.dueDate}
                          {l.isOverdue && ' (延滞)'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <p>現在貸出中の本はありません</p>
              )}

              <h4 className="mt-16">予約 ({getUserReservations(selectedUser.id).length}件)</h4>
              {getUserReservations(selectedUser.id).length > 0 ? (
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>タイトル</th>
                      <th>状態</th>
                    </tr>
                  </thead>
                  <tbody>
                    {getUserReservations(selectedUser.id).map((r) => (
                      <tr key={r.id}>
                        <td>{r.book?.title}</td>
                        <td>{r.status}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              ) : (
                <p>現在の予約はありません</p>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
