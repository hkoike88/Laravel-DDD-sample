/**
 * UC-001-001: 蔵書検索画面
 *
 * 職員または利用者が、タイトル・著者名・ISBN等の条件で蔵書を検索し、
 * 所蔵情報を確認する
 */
import { useState } from 'react';
import { mockBooks } from '../../data/mockData';
import type { Book, BookStatus } from '../../types';

/**
 * ステータスバッジのスタイルクラスを取得
 */
function getStatusClass(status: BookStatus): string {
  const classes: Record<BookStatus, string> = {
    貸出可: 'available',
    貸出中: 'borrowed',
    予約あり: 'reserved',
    禁帯出: 'forbidden',
  };
  return classes[status] || '';
}

export default function BookSearch() {
  const [searchQuery, setSearchQuery] = useState('');
  const [searchType, setSearchType] = useState<'title' | 'author' | 'isbn' | 'all'>('all');
  const [results, setResults] = useState<Book[]>([]);
  const [hasSearched, setHasSearched] = useState(false);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);

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

    const filtered = mockBooks.filter((book) => {
      switch (searchType) {
        case 'title':
          return book.title.toLowerCase().includes(query);
        case 'author':
          return book.author.toLowerCase().includes(query);
        case 'isbn':
          return book.isbn.includes(query);
        case 'all':
        default:
          return (
            book.title.toLowerCase().includes(query) ||
            book.author.toLowerCase().includes(query) ||
            book.isbn.includes(query)
          );
      }
    });

    setResults(filtered);
    setHasSearched(true);
  };

  /**
   * Enterキーで検索
   */
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      handleSearch();
    }
  };

  return (
    <div className="book-search">
      <header className="page-header">
        <h1>📚 蔵書検索</h1>
        <p className="subtitle">UC-001-001 / EP-01 蔵書管理</p>
      </header>

      {/* 検索フォーム */}
      <div className="section-box">
        <h3>検索条件</h3>
        <div className="form-row">
          <div className="form-group">
            <label>検索対象</label>
            <select
              value={searchType}
              onChange={(e) => setSearchType(e.target.value as typeof searchType)}
            >
              <option value="all">すべて</option>
              <option value="title">タイトル</option>
              <option value="author">著者名</option>
              <option value="isbn">ISBN</option>
            </select>
          </div>
          <div className="form-group" style={{ flex: 2 }}>
            <label>キーワード</label>
            <input
              type="text"
              placeholder="検索キーワードを入力..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={handleKeyDown}
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
                  <th>タイトル</th>
                  <th>著者</th>
                  <th>出版社</th>
                  <th>資料区分</th>
                  <th>状態</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                {results.map((book) => (
                  <tr key={book.id}>
                    <td>{book.title}</td>
                    <td>{book.author}</td>
                    <td>{book.publisher}</td>
                    <td>{book.materialType}</td>
                    <td>
                      <span className={`status-badge ${getStatusClass(book.status)}`}>
                        {book.status}
                      </span>
                    </td>
                    <td>
                      <button
                        className="btn"
                        onClick={() => setSelectedBook(book)}
                      >
                        詳細
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <div className="empty-state">
              <div className="empty-state-icon">📖</div>
              <h3>該当する蔵書がありません</h3>
              <p>検索条件を変更して再度お試しください</p>
            </div>
          )}
        </div>
      )}

      {/* 詳細表示 */}
      {selectedBook && (
        <div className="section-box">
          <div className="card-header">
            <h3 className="card-title">📖 蔵書詳細</h3>
            <button className="btn" onClick={() => setSelectedBook(null)}>
              閉じる
            </button>
          </div>
          <div className="detail-list">
            <div className="detail-item">
              <span className="detail-label">蔵書ID</span>
              <span className="detail-value">{selectedBook.id}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">ISBN</span>
              <span className="detail-value">{selectedBook.isbn}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">タイトル</span>
              <span className="detail-value">{selectedBook.title}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">著者</span>
              <span className="detail-value">{selectedBook.author}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">出版社</span>
              <span className="detail-value">{selectedBook.publisher}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">発行日</span>
              <span className="detail-value">{selectedBook.publishedDate}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">資料区分</span>
              <span className="detail-value">{selectedBook.materialType}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">ジャンル</span>
              <span className="detail-value">{selectedBook.genre}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">配架場所</span>
              <span className="detail-value">{selectedBook.location}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">状態</span>
              <span className="detail-value">
                <span className={`status-badge ${getStatusClass(selectedBook.status)}`}>
                  {selectedBook.status}
                </span>
              </span>
            </div>
            <div className="detail-item">
              <span className="detail-label">登録日</span>
              <span className="detail-value">{selectedBook.registeredAt}</span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
