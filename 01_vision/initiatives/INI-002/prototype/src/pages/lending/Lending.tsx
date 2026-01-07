/**
 * UC-001-003: 貸出処理画面
 *
 * 職員が利用者への図書貸出を処理する
 * 業務ルール:
 * - BR-001: 貸出上限 1人5冊まで
 * - BR-002: 貸出期間 14日間（新刊・雑誌・AVは7日間）
 * - BR-003: 延滞中は新規貸出停止
 */
import { useState } from 'react';
import {
  mockBooks,
  getUserByCardNumber,
  getCurrentLendingCount,
} from '../../data/mockData';
import { BUSINESS_RULES } from '../../types';
import type { Book, User } from '../../types';

export default function Lending() {
  const [cardNumber, setCardNumber] = useState('');
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [bookId, setBookId] = useState('');
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [message, setMessage] = useState<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null);
  const [lendingList, setLendingList] = useState<{ book: Book; dueDate: string }[]>([]);

  /**
   * 利用者検索
   */
  const handleUserSearch = () => {
    setMessage(null);
    const user = getUserByCardNumber(cardNumber);
    if (!user) {
      setMessage({ type: 'error', text: '利用者が見つかりません' });
      setSelectedUser(null);
      return;
    }

    // ステータスチェック
    if (user.status === '期限切れ') {
      setMessage({ type: 'warning', text: 'カードの有効期限が切れています。更新手続きが必要です。' });
    } else if (user.status === '停止中') {
      setMessage({ type: 'error', text: '貸出停止中です。延滞図書の返却が必要です。' });
    }

    setSelectedUser(user);
  };

  /**
   * 蔵書検索
   */
  const handleBookSearch = () => {
    setMessage(null);
    const book = mockBooks.find((b) => b.id === bookId || b.isbn.includes(bookId));
    if (!book) {
      setMessage({ type: 'error', text: '蔵書が見つかりません' });
      setSelectedBook(null);
      return;
    }

    // 貸出可能チェック
    if (book.status !== '貸出可') {
      setMessage({ type: 'error', text: `この本は現在「${book.status}」のため貸出できません` });
      setSelectedBook(null);
      return;
    }

    setSelectedBook(book);
  };

  /**
   * 貸出処理
   */
  const handleLend = () => {
    if (!selectedUser || !selectedBook) {
      setMessage({ type: 'error', text: '利用者と蔵書を選択してください' });
      return;
    }

    // 貸出停止チェック
    if (selectedUser.status === '停止中') {
      setMessage({ type: 'error', text: '貸出停止中のため貸出できません' });
      return;
    }

    // 貸出上限チェック
    const currentCount = getCurrentLendingCount(selectedUser.id) + lendingList.length;
    if (currentCount >= BUSINESS_RULES.MAX_LENDING_COUNT) {
      setMessage({
        type: 'error',
        text: `貸出上限（${BUSINESS_RULES.MAX_LENDING_COUNT}冊）に達しています`,
      });
      return;
    }

    // 返却期限計算
    const today = new Date();
    const lendingPeriod =
      selectedBook.materialType === '一般図書'
        ? BUSINESS_RULES.LENDING_PERIOD_NORMAL
        : BUSINESS_RULES.LENDING_PERIOD_SHORT;
    const dueDate = new Date(today);
    dueDate.setDate(dueDate.getDate() + lendingPeriod);
    const dueDateStr = dueDate.toISOString().split('T')[0];

    // 貸出リストに追加
    setLendingList([...lendingList, { book: selectedBook, dueDate: dueDateStr }]);
    setMessage({
      type: 'success',
      text: `「${selectedBook.title}」を貸出リストに追加しました（返却期限: ${dueDateStr}）`,
    });
    setSelectedBook(null);
    setBookId('');
  };

  /**
   * 貸出確定
   */
  const handleConfirm = () => {
    if (lendingList.length === 0) {
      setMessage({ type: 'error', text: '貸出する本がありません' });
      return;
    }

    setMessage({
      type: 'success',
      text: `${lendingList.length}冊の貸出処理が完了しました（プロトタイプ: データは保存されません）`,
    });
    setLendingList([]);
    setSelectedUser(null);
    setCardNumber('');
  };

  /**
   * 貸出リストから削除
   */
  const handleRemoveFromList = (index: number) => {
    setLendingList(lendingList.filter((_, i) => i !== index));
  };

  return (
    <div className="lending-page">
      <header className="page-header">
        <h1>📖 貸出処理</h1>
        <p className="subtitle">UC-001-003 / EP-02 貸出・返却</p>
      </header>

      {/* メッセージ */}
      {message && <div className={`message ${message.type}`}>{message.text}</div>}

      <div className="two-column">
        {/* 左カラム: 利用者・蔵書入力 */}
        <div>
          {/* 利用者検索 */}
          <div className="section-box">
            <h3>👤 利用者確認</h3>
            <div className="form-group">
              <label>カード番号</label>
              <input
                type="text"
                value={cardNumber}
                onChange={(e) => setCardNumber(e.target.value)}
                placeholder="0001-0001"
                onKeyDown={(e) => e.key === 'Enter' && handleUserSearch()}
              />
            </div>
            <button className="btn" onClick={handleUserSearch}>
              検索
            </button>

            {selectedUser && (
              <div className="detail-list mt-16">
                <div className="detail-item">
                  <span className="detail-label">氏名</span>
                  <span className="detail-value">{selectedUser.name}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">ステータス</span>
                  <span className="detail-value">
                    <span
                      className={`status-badge ${
                        selectedUser.status === '有効'
                          ? 'active'
                          : selectedUser.status === '期限切れ'
                          ? 'expired'
                          : 'suspended'
                      }`}
                    >
                      {selectedUser.status}
                    </span>
                  </span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">現在の貸出数</span>
                  <span className="detail-value">
                    {getCurrentLendingCount(selectedUser.id) + lendingList.length} /{' '}
                    {BUSINESS_RULES.MAX_LENDING_COUNT}
                  </span>
                </div>
              </div>
            )}
          </div>

          {/* 蔵書検索 */}
          <div className="section-box">
            <h3>📚 蔵書選択</h3>
            <div className="form-group">
              <label>蔵書ID / ISBN</label>
              <input
                type="text"
                value={bookId}
                onChange={(e) => setBookId(e.target.value)}
                placeholder="B001 または ISBN"
                onKeyDown={(e) => e.key === 'Enter' && handleBookSearch()}
              />
            </div>
            <button className="btn" onClick={handleBookSearch}>
              検索
            </button>

            {selectedBook && (
              <div className="mt-16">
                <div className="detail-list">
                  <div className="detail-item">
                    <span className="detail-label">タイトル</span>
                    <span className="detail-value">{selectedBook.title}</span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">著者</span>
                    <span className="detail-value">{selectedBook.author}</span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">資料区分</span>
                    <span className="detail-value">{selectedBook.materialType}</span>
                  </div>
                </div>
                <button
                  className="btn btn-primary mt-16"
                  onClick={handleLend}
                  disabled={selectedUser?.status === '停止中'}
                >
                  ➕ 貸出リストに追加
                </button>
              </div>
            )}
          </div>
        </div>

        {/* 右カラム: 貸出リスト */}
        <div>
          <div className="section-box">
            <h3>📋 貸出リスト</h3>
            {lendingList.length > 0 ? (
              <>
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>タイトル</th>
                      <th>返却期限</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    {lendingList.map((item, index) => (
                      <tr key={index}>
                        <td>{item.book.title}</td>
                        <td>{item.dueDate}</td>
                        <td>
                          <button
                            className="btn btn-danger"
                            onClick={() => handleRemoveFromList(index)}
                          >
                            削除
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                <div className="form-actions">
                  <button className="btn btn-primary" onClick={handleConfirm}>
                    ✓ 貸出確定
                  </button>
                </div>
              </>
            ) : (
              <div className="empty-state">
                <div className="empty-state-icon">📚</div>
                <h3>貸出する本を追加してください</h3>
                <p>左側から蔵書を検索して追加します</p>
              </div>
            )}
          </div>

          {/* 業務ルール */}
          <div className="section-box">
            <h3>📋 業務ルール</h3>
            <div className="info-grid">
              <div className="info-item">
                <h4>貸出上限</h4>
                <p>1人{BUSINESS_RULES.MAX_LENDING_COUNT}冊まで</p>
              </div>
              <div className="info-item">
                <h4>貸出期間</h4>
                <p>一般: {BUSINESS_RULES.LENDING_PERIOD_NORMAL}日 / 新刊: {BUSINESS_RULES.LENDING_PERIOD_SHORT}日</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
