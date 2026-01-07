/**
 * UC-001-002: 蔵書登録画面
 *
 * 職員が新しい蔵書をシステムに登録する
 */
import { useState } from 'react';
import type { MaterialType } from '../../types';

interface BookForm {
  isbn: string;
  title: string;
  author: string;
  publisher: string;
  publishedDate: string;
  materialType: MaterialType;
  genre: string;
  location: string;
}

const initialForm: BookForm = {
  isbn: '',
  title: '',
  author: '',
  publisher: '',
  publishedDate: '',
  materialType: '一般図書',
  genre: '',
  location: '',
};

export default function BookRegister() {
  const [form, setForm] = useState<BookForm>(initialForm);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  /**
   * フォーム入力変更
   */
  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  /**
   * 登録処理（プロトタイプ: 実際のAPI呼び出しはなし）
   */
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    // バリデーション
    if (!form.isbn || !form.title || !form.author) {
      setMessage({ type: 'error', text: 'ISBN、タイトル、著者名は必須です' });
      return;
    }

    // プロトタイプ: 成功メッセージを表示
    setMessage({
      type: 'success',
      text: `蔵書「${form.title}」を登録しました（プロトタイプ: データは保存されません）`,
    });
    setForm(initialForm);
  };

  /**
   * フォームリセット
   */
  const handleReset = () => {
    setForm(initialForm);
    setMessage(null);
  };

  return (
    <div className="book-register">
      <header className="page-header">
        <h1>📚 蔵書登録</h1>
        <p className="subtitle">UC-001-002 / EP-01 蔵書管理</p>
      </header>

      {/* メッセージ */}
      {message && (
        <div className={`message ${message.type}`}>
          {message.text}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div className="section-box">
          <h3>書籍情報</h3>
          <div className="form-row">
            <div className="form-group">
              <label>ISBN *</label>
              <input
                type="text"
                name="isbn"
                value={form.isbn}
                onChange={handleChange}
                placeholder="978-4-00-000000-0"
              />
            </div>
            <div className="form-group">
              <label>資料区分 *</label>
              <select
                name="materialType"
                value={form.materialType}
                onChange={handleChange}
              >
                <option value="一般図書">一般図書</option>
                <option value="新刊図書">新刊図書</option>
                <option value="雑誌">雑誌</option>
                <option value="CD・DVD">CD・DVD</option>
                <option value="参考図書">参考図書</option>
              </select>
            </div>
          </div>

          <div className="form-group">
            <label>タイトル *</label>
            <input
              type="text"
              name="title"
              value={form.title}
              onChange={handleChange}
              placeholder="書籍のタイトル"
            />
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>著者名 *</label>
              <input
                type="text"
                name="author"
                value={form.author}
                onChange={handleChange}
                placeholder="著者名"
              />
            </div>
            <div className="form-group">
              <label>出版社</label>
              <input
                type="text"
                name="publisher"
                value={form.publisher}
                onChange={handleChange}
                placeholder="出版社名"
              />
            </div>
          </div>

          <div className="form-row">
            <div className="form-group">
              <label>発行日</label>
              <input
                type="date"
                name="publishedDate"
                value={form.publishedDate}
                onChange={handleChange}
              />
            </div>
            <div className="form-group">
              <label>ジャンル</label>
              <input
                type="text"
                name="genre"
                value={form.genre}
                onChange={handleChange}
                placeholder="文学、コンピュータ、etc."
              />
            </div>
          </div>

          <div className="form-group">
            <label>配架場所</label>
            <input
              type="text"
              name="location"
              value={form.location}
              onChange={handleChange}
              placeholder="1階 文学コーナー"
            />
          </div>
        </div>

        <div className="form-actions">
          <button type="submit" className="btn btn-primary">
            📚 登録する
          </button>
          <button type="button" className="btn btn-secondary" onClick={handleReset}>
            リセット
          </button>
        </div>
      </form>

      {/* 業務ルール説明 */}
      <div className="section-box mt-24">
        <h3>📋 資料区分について</h3>
        <div className="info-grid">
          <div className="info-item">
            <h4>一般図書</h4>
            <p>貸出期間: 14日間（延長1回可）</p>
          </div>
          <div className="info-item">
            <h4>新刊図書</h4>
            <p>発売後3ヶ月以内 / 貸出期間: 7日間（延長不可）</p>
          </div>
          <div className="info-item">
            <h4>雑誌</h4>
            <p>最新号は禁帯出 / バックナンバーは7日間</p>
          </div>
          <div className="info-item">
            <h4>参考図書</h4>
            <p>禁帯出（館内閲覧のみ）</p>
          </div>
        </div>
      </div>
    </div>
  );
}
