/**
 * UC-001-005: 予約登録画面
 *
 * 職員が利用者からの予約依頼を受け付ける
 * 業務ルール:
 * - BR-004: 予約上限 1人3冊まで、1タイトル3人まで
 */
import { useState } from 'react';
import {
  mockBooks,
  getUserByCardNumber,
  getCurrentReservationCount,
  getBookReservationCount,
} from '../../data/mockData';
import { BUSINESS_RULES } from '../../types';
import type { Book, User } from '../../types';

export default function ReservationRegister() {
  const [cardNumber, setCardNumber] = useState('');
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [bookSearch, setBookSearch] = useState('');
  const [searchResults, setSearchResults] = useState<Book[]>([]);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [message, setMessage] = useState<{ type: 'success' | 'error' | 'warning' | 'info'; text: string } | null>(null);

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

    if (user.status !== '有効') {
      setMessage({
        type: 'warning',
        text: `利用者のステータスが「${user.status}」です。予約を受け付けられない場合があります。`,
      });
    }

    setSelectedUser(user);
  };

  /**
   * 蔵書検索
   */
  const handleBookSearch = () => {
    setMessage(null);
    const query = bookSearch.toLowerCase().trim();
    if (!query) {
      setSearchResults([]);
      return;
    }

    const results = mockBooks.filter(
      (book) =>
        book.title.toLowerCase().includes(query) ||
        book.author.toLowerCase().includes(query) ||
        book.isbn.includes(query)
    );
    setSearchResults(results);
  };

  /**
   * 蔵書選択
   */
  const handleSelectBook = (book: Book) => {
    setSelectedBook(book);
    setSearchResults([]);
    setBookSearch('');

    // 予約可能かチェック
    if (book.status === '禁帯出') {
      setMessage({ type: 'error', text: 'この資料は禁帯出のため予約できません' });
      return;
    }

    if (book.status === '貸出可') {
      setMessage({
        type: 'info',
        text: 'この本は現在貸出可能です。直接貸出することをお勧めします。',
      });
    }

    const reservationCount = getBookReservationCount(book.id);
    if (reservationCount >= BUSINESS_RULES.MAX_RESERVATION_PER_TITLE) {
      setMessage({
        type: 'warning',
        text: `この本は既に${BUSINESS_RULES.MAX_RESERVATION_PER_TITLE}人が予約中です（上限）`,
      });
    }
  };

  /**
   * 予約登録
   */
  const handleReserve = () => {
    if (!selectedUser || !selectedBook) {
      setMessage({ type: 'error', text: '利用者と蔵書を選択してください' });
      return;
    }

    // 利用者の予約上限チェック
    const userReservationCount = getCurrentReservationCount(selectedUser.id);
    if (userReservationCount >= BUSINESS_RULES.MAX_RESERVATION_COUNT) {
      setMessage({
        type: 'error',
        text: `予約上限（${BUSINESS_RULES.MAX_RESERVATION_COUNT}冊）に達しています`,
      });
      return;
    }

    // 蔵書の予約上限チェック
    const bookReservationCount = getBookReservationCount(selectedBook.id);
    if (bookReservationCount >= BUSINESS_RULES.MAX_RESERVATION_PER_TITLE) {
      setMessage({
        type: 'error',
        text: `この本の予約は上限（${BUSINESS_RULES.MAX_RESERVATION_PER_TITLE}人）に達しています`,
      });
      return;
    }

    // 禁帯出チェック
    if (selectedBook.status === '禁帯出') {
      setMessage({ type: 'error', text: 'この資料は禁帯出のため予約できません' });
      return;
    }

    // 予約順番
    const position = bookReservationCount + 1;

    setMessage({
      type: 'success',
      text: `予約を受け付けました。「${selectedBook.title}」の予約順番は${position}番目です（プロトタイプ: データは保存されません）`,
    });

    // リセット
    setSelectedBook(null);
  };

  return (
    <div className="reservation-register">
      <header className="page-header">
        <h1>📋 予約登録</h1>
        <p className="subtitle">UC-001-005 / EP-03 予約管理</p>
      </header>

      {/* メッセージ */}
      {message && <div className={`message ${message.type}`}>{message.text}</div>}

      <div className="two-column">
        {/* 左カラム: 入力フォーム */}
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
                  <span className="detail-label">電話番号</span>
                  <span className="detail-value">{selectedUser.phone}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">メール</span>
                  <span className="detail-value">{selectedUser.email || '未登録'}</span>
                </div>
                <div className="detail-item">
                  <span className="detail-label">現在の予約数</span>
                  <span className="detail-value">
                    {getCurrentReservationCount(selectedUser.id)} /{' '}
                    {BUSINESS_RULES.MAX_RESERVATION_COUNT}
                  </span>
                </div>
              </div>
            )}
          </div>

          {/* 蔵書検索 */}
          <div className="section-box">
            <h3>📚 予約する本</h3>
            <div className="form-group">
              <label>タイトル / 著者 / ISBN</label>
              <input
                type="text"
                value={bookSearch}
                onChange={(e) => setBookSearch(e.target.value)}
                placeholder="キーワードを入力"
                onKeyDown={(e) => e.key === 'Enter' && handleBookSearch()}
              />
            </div>
            <button className="btn" onClick={handleBookSearch}>
              検索
            </button>

            {/* 検索結果 */}
            {searchResults.length > 0 && (
              <div className="mt-16">
                <table className="data-table">
                  <thead>
                    <tr>
                      <th>タイトル</th>
                      <th>状態</th>
                      <th>予約数</th>
                      <th>操作</th>
                    </tr>
                  </thead>
                  <tbody>
                    {searchResults.map((book) => (
                      <tr key={book.id}>
                        <td>{book.title}</td>
                        <td>{book.status}</td>
                        <td>{getBookReservationCount(book.id)}人</td>
                        <td>
                          <button
                            className="btn"
                            onClick={() => handleSelectBook(book)}
                            disabled={book.status === '禁帯出'}
                          >
                            選択
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}

            {/* 選択した蔵書 */}
            {selectedBook && (
              <div className="mt-16 card">
                <div className="card-header">
                  <h4 className="card-title">選択中の蔵書</h4>
                  <button className="btn" onClick={() => setSelectedBook(null)}>
                    取消
                  </button>
                </div>
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
                    <span className="detail-label">状態</span>
                    <span className="detail-value">{selectedBook.status}</span>
                  </div>
                  <div className="detail-item">
                    <span className="detail-label">現在の予約</span>
                    <span className="detail-value">
                      {getBookReservationCount(selectedBook.id)}人待ち
                    </span>
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* 予約ボタン */}
          {selectedUser && selectedBook && (
            <div className="form-actions">
              <button className="btn btn-primary" onClick={handleReserve}>
                📋 予約を登録する
              </button>
            </div>
          )}
        </div>

        {/* 右カラム: 業務ルール */}
        <div>
          <div className="section-box">
            <h3>📋 予約の業務ルール</h3>
            <div className="info-grid">
              <div className="info-item">
                <h4>予約上限</h4>
                <p>1人{BUSINESS_RULES.MAX_RESERVATION_COUNT}冊まで</p>
              </div>
              <div className="info-item">
                <h4>1タイトルあたり</h4>
                <p>最大{BUSINESS_RULES.MAX_RESERVATION_PER_TITLE}人まで</p>
              </div>
              <div className="info-item">
                <h4>取り置き期限</h4>
                <p>連絡後{BUSINESS_RULES.HOLD_PERIOD}日間</p>
              </div>
              <div className="info-item">
                <h4>予約順</h4>
                <p>先着順</p>
              </div>
            </div>
          </div>

          <div className="section-box">
            <h3>💡 ヒント</h3>
            <ul style={{ paddingLeft: 20, margin: 0 }}>
              <li>貸出中の本に対して予約を受け付けます</li>
              <li>「貸出可」の本は直接貸出することをお勧めします</li>
              <li>禁帯出資料は予約できません</li>
              <li>連絡先（電話/メール）を必ず確認してください</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}
