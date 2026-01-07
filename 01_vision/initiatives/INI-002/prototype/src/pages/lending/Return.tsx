/**
 * UC-001-004: 返却処理画面
 *
 * 職員が返却された図書を処理する
 * - 延滞チェック
 * - 予約確認
 * - 取り置き処理
 */
import { useState } from 'react';
import {
  mockBooks,
  mockLendings,
  mockReservations,
  getBookById,
  getUserById,
} from '../../data/mockData';
import type { Book, Lending, Reservation, User } from '../../types';

interface ReturnItem {
  lending: Lending;
  book: Book;
  user: User;
  isOverdue: boolean;
  overdueDays: number;
  hasReservation: boolean;
  reservations: (Reservation & { user: User })[];
}

export default function Return() {
  const [bookId, setBookId] = useState('');
  const [returnItem, setReturnItem] = useState<ReturnItem | null>(null);
  const [message, setMessage] = useState<{ type: 'success' | 'error' | 'warning' | 'info'; text: string } | null>(null);
  const [processedItems, setProcessedItems] = useState<ReturnItem[]>([]);

  /**
   * 蔵書検索・返却情報取得
   */
  const handleSearch = () => {
    setMessage(null);
    setReturnItem(null);

    // 蔵書検索
    const book = mockBooks.find((b) => b.id === bookId || b.isbn.includes(bookId));
    if (!book) {
      setMessage({ type: 'error', text: '蔵書が見つかりません' });
      return;
    }

    // 貸出記録検索
    const lending = mockLendings.find((l) => l.bookId === book.id && !l.returnedAt);
    if (!lending) {
      setMessage({ type: 'warning', text: 'この本は現在貸出されていません' });
      return;
    }

    const user = getUserById(lending.userId);
    if (!user) {
      setMessage({ type: 'error', text: '利用者情報が見つかりません' });
      return;
    }

    // 延滞チェック
    const today = new Date();
    const dueDate = new Date(lending.dueDate);
    const isOverdue = today > dueDate;
    const overdueDays = isOverdue
      ? Math.floor((today.getTime() - dueDate.getTime()) / (1000 * 60 * 60 * 24))
      : 0;

    // 予約確認
    const bookReservations = mockReservations
      .filter((r) => r.bookId === book.id && r.status === '予約中')
      .sort((a, b) => a.position - b.position)
      .map((r) => ({
        ...r,
        user: getUserById(r.userId)!,
      }));

    setReturnItem({
      lending,
      book,
      user,
      isOverdue,
      overdueDays,
      hasReservation: bookReservations.length > 0,
      reservations: bookReservations,
    });

    // メッセージ表示
    if (isOverdue) {
      setMessage({
        type: 'warning',
        text: `⚠️ ${overdueDays}日延滞しています`,
      });
    } else if (bookReservations.length > 0) {
      setMessage({
        type: 'info',
        text: `📋 ${bookReservations.length}件の予約があります`,
      });
    }
  };

  /**
   * 返却処理
   */
  const handleReturn = () => {
    if (!returnItem) return;

    // 処理済みリストに追加
    setProcessedItems([...processedItems, returnItem]);

    // メッセージ
    let msg = `「${returnItem.book.title}」の返却処理が完了しました`;
    if (returnItem.hasReservation) {
      msg += '（予約者への取り置き処理が必要です）';
    }
    setMessage({ type: 'success', text: msg });

    // リセット
    setReturnItem(null);
    setBookId('');
  };

  /**
   * 延滞者への注意喚起メッセージ
   */
  const getOverdueMessage = (days: number): string => {
    if (days <= 7) return '返却期限を過ぎています。今後はお気をつけください。';
    if (days <= 14) return '2週間以上の延滞です。今後の貸出に制限がかかる場合があります。';
    return '長期延滞となっています。館長面談の対象となる場合があります。';
  };

  return (
    <div className="return-page">
      <header className="page-header">
        <h1>↩️ 返却処理</h1>
        <p className="subtitle">UC-001-004 / EP-02 貸出・返却</p>
      </header>

      {/* メッセージ */}
      {message && <div className={`message ${message.type}`}>{message.text}</div>}

      <div className="two-column">
        {/* 左カラム: 返却入力 */}
        <div>
          <div className="section-box">
            <h3>📚 返却する本</h3>
            <div className="form-group">
              <label>蔵書ID / ISBN</label>
              <input
                type="text"
                value={bookId}
                onChange={(e) => setBookId(e.target.value)}
                placeholder="B002 または ISBN"
                onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
              />
            </div>
            <button className="btn btn-primary" onClick={handleSearch}>
              🔍 検索
            </button>
          </div>

          {/* 返却情報表示 */}
          {returnItem && (
            <div className="section-box">
              <h3>📖 返却情報</h3>
              <div className="detail-list">
                <div className="detail-item">
                  <span className="detail-label">タイトル</span>
                  <span className="detail-value">{returnItem.book.title}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">著者</span>
                  <span className="detail-value">{returnItem.book.author}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">貸出者</span>
                  <span className="detail-value">{returnItem.user.name}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">貸出日</span>
                  <span className="detail-value">{returnItem.lending.lentAt}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">返却期限</span>
                  <span className="detail-value">
                    {returnItem.lending.dueDate}
                    {returnItem.isOverdue && (
                      <span className="status-badge suspended" style={{ marginLeft: 8 }}>
                        {returnItem.overdueDays}日延滞
                      </span>
                    )}
                  </span>
                </div>
              </div>

              {/* 延滞警告 */}
              {returnItem.isOverdue && (
                <div className="message warning mt-16">
                  <strong>⚠️ 延滞について</strong>
                  <p className="mb-0">{getOverdueMessage(returnItem.overdueDays)}</p>
                </div>
              )}

              {/* 予約情報 */}
              {returnItem.hasReservation && (
                <div className="message info mt-16">
                  <strong>📋 予約あり</strong>
                  <p className="mb-0">
                    次の予約者: {returnItem.reservations[0].user.name}（
                    {returnItem.reservations[0].user.phone}）
                  </p>
                  <p className="mb-0">
                    → 取り置き棚へ移動し、予約者に連絡してください
                  </p>
                </div>
              )}

              <div className="form-actions">
                <button className="btn btn-primary" onClick={handleReturn}>
                  ✓ 返却処理を完了
                </button>
                <button
                  className="btn btn-secondary"
                  onClick={() => {
                    setReturnItem(null);
                    setBookId('');
                    setMessage(null);
                  }}
                >
                  キャンセル
                </button>
              </div>
            </div>
          )}
        </div>

        {/* 右カラム: 処理済み一覧 */}
        <div>
          <div className="section-box">
            <h3>✓ 本日の処理済み</h3>
            {processedItems.length > 0 ? (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>タイトル</th>
                    <th>貸出者</th>
                    <th>状態</th>
                  </tr>
                </thead>
                <tbody>
                  {processedItems.map((item, index) => (
                    <tr key={index}>
                      <td>{item.book.title}</td>
                      <td>{item.user.name}</td>
                      <td>
                        {item.isOverdue && (
                          <span className="status-badge suspended">延滞</span>
                        )}
                        {item.hasReservation && (
                          <span className="status-badge reserved" style={{ marginLeft: 4 }}>
                            予約あり
                          </span>
                        )}
                        {!item.isOverdue && !item.hasReservation && (
                          <span className="status-badge available">通常</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            ) : (
              <div className="empty-state">
                <div className="empty-state-icon">📚</div>
                <h3>処理済みの返却はありません</h3>
                <p>返却された本のIDを入力してください</p>
              </div>
            )}
          </div>

          {/* 現在の延滞一覧 */}
          <div className="section-box">
            <h3>⚠️ 現在の延滞一覧</h3>
            {mockLendings.filter((l) => l.isOverdue && !l.returnedAt).length > 0 ? (
              <table className="data-table">
                <thead>
                  <tr>
                    <th>タイトル</th>
                    <th>貸出者</th>
                    <th>返却期限</th>
                  </tr>
                </thead>
                <tbody>
                  {mockLendings
                    .filter((l) => l.isOverdue && !l.returnedAt)
                    .map((lending) => {
                      const book = getBookById(lending.bookId);
                      const user = getUserById(lending.userId);
                      return (
                        <tr key={lending.id}>
                          <td>{book?.title}</td>
                          <td>{user?.name}</td>
                          <td style={{ color: '#c00' }}>{lending.dueDate}</td>
                        </tr>
                      );
                    })}
                </tbody>
              </table>
            ) : (
              <p>延滞中の図書はありません</p>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
