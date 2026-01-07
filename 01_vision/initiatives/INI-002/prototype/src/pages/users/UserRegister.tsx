/**
 * UC-001-007: 利用者登録画面
 *
 * 職員が新規利用者を登録する
 * 業務ルール:
 * - BR-006: 利用者登録資格 市内在住・在勤・在学者
 * - BR-007: カード有効期限 登録から1年間
 */
import { useState } from 'react';
import { BUSINESS_RULES } from '../../types';

interface UserForm {
  name: string;
  nameKana: string;
  birthDate: string;
  address: string;
  phone: string;
  email: string;
  registrationType: 'resident' | 'commuter' | 'student';
  workplace?: string;
  memo: string;
}

const initialForm: UserForm = {
  name: '',
  nameKana: '',
  birthDate: '',
  address: '',
  phone: '',
  email: '',
  registrationType: 'resident',
  workplace: '',
  memo: '',
};

export default function UserRegister() {
  const [form, setForm] = useState<UserForm>(initialForm);
  const [message, setMessage] = useState<{ type: 'success' | 'error' | 'warning'; text: string } | null>(null);
  const [step, setStep] = useState<'input' | 'confirm'>('input');

  /**
   * フォーム入力変更
   */
  const handleChange = (
    e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>
  ) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  /**
   * バリデーション
   */
  const validate = (): boolean => {
    if (!form.name || !form.nameKana) {
      setMessage({ type: 'error', text: '氏名（漢字・フリガナ）は必須です' });
      return false;
    }
    if (!form.birthDate) {
      setMessage({ type: 'error', text: '生年月日は必須です' });
      return false;
    }
    if (!form.address) {
      setMessage({ type: 'error', text: '住所は必須です' });
      return false;
    }
    if (!form.phone) {
      setMessage({ type: 'error', text: '電話番号は必須です' });
      return false;
    }
    if (form.registrationType !== 'resident' && !form.workplace) {
      setMessage({ type: 'error', text: '在勤・在学の場合は勤務先/学校名が必須です' });
      return false;
    }
    return true;
  };

  /**
   * 確認画面へ
   */
  const handleConfirm = () => {
    setMessage(null);
    if (!validate()) return;
    setStep('confirm');
  };

  /**
   * 登録処理
   */
  const handleSubmit = () => {
    // 有効期限の計算
    const today = new Date();
    const expiresAt = new Date(today);
    expiresAt.setFullYear(expiresAt.getFullYear() + BUSINESS_RULES.CARD_VALIDITY_YEARS);
    const expiresAtStr = expiresAt.toISOString().split('T')[0];

    // カード番号の生成（プロトタイプ用）
    const cardNumber = `0001-${String(Math.floor(Math.random() * 9000) + 1000)}`;

    setMessage({
      type: 'success',
      text: `利用者「${form.name}」を登録しました。カード番号: ${cardNumber}、有効期限: ${expiresAtStr}（プロトタイプ: データは保存されません）`,
    });
    setForm(initialForm);
    setStep('input');
  };

  return (
    <div className="user-register">
      <header className="page-header">
        <h1>👤 利用者登録</h1>
        <p className="subtitle">UC-001-007 / EP-04 利用者管理</p>
      </header>

      {/* メッセージ */}
      {message && <div className={`message ${message.type}`}>{message.text}</div>}

      {step === 'input' ? (
        /* 入力フォーム */
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleConfirm();
          }}
        >
          <div className="two-column">
            <div>
              <div className="section-box">
                <h3>本人情報</h3>
                <div className="form-row">
                  <div className="form-group">
                    <label>氏名 *</label>
                    <input
                      type="text"
                      name="name"
                      value={form.name}
                      onChange={handleChange}
                      placeholder="山田 太郎"
                    />
                  </div>
                  <div className="form-group">
                    <label>フリガナ *</label>
                    <input
                      type="text"
                      name="nameKana"
                      value={form.nameKana}
                      onChange={handleChange}
                      placeholder="ヤマダ タロウ"
                    />
                  </div>
                </div>

                <div className="form-group">
                  <label>生年月日 *</label>
                  <input
                    type="date"
                    name="birthDate"
                    value={form.birthDate}
                    onChange={handleChange}
                  />
                </div>

                <div className="form-group">
                  <label>住所 *</label>
                  <input
                    type="text"
                    name="address"
                    value={form.address}
                    onChange={handleChange}
                    placeholder="青空市○○町1-2-3"
                  />
                </div>

                <div className="form-row">
                  <div className="form-group">
                    <label>電話番号 *</label>
                    <input
                      type="tel"
                      name="phone"
                      value={form.phone}
                      onChange={handleChange}
                      placeholder="090-1234-5678"
                    />
                  </div>
                  <div className="form-group">
                    <label>メールアドレス</label>
                    <input
                      type="email"
                      name="email"
                      value={form.email}
                      onChange={handleChange}
                      placeholder="example@mail.com"
                    />
                  </div>
                </div>
              </div>

              <div className="section-box">
                <h3>登録資格</h3>
                <div className="form-group">
                  <label>登録種別 *</label>
                  <select
                    name="registrationType"
                    value={form.registrationType}
                    onChange={handleChange}
                  >
                    <option value="resident">青空市在住</option>
                    <option value="commuter">青空市在勤</option>
                    <option value="student">青空市在学</option>
                  </select>
                </div>

                {form.registrationType !== 'resident' && (
                  <div className="form-group">
                    <label>
                      {form.registrationType === 'commuter' ? '勤務先' : '学校名'} *
                    </label>
                    <input
                      type="text"
                      name="workplace"
                      value={form.workplace}
                      onChange={handleChange}
                      placeholder={
                        form.registrationType === 'commuter'
                          ? '株式会社○○'
                          : '青空市立○○小学校'
                      }
                    />
                  </div>
                )}

                <div className="form-group">
                  <label>備考</label>
                  <textarea
                    name="memo"
                    value={form.memo}
                    onChange={handleChange}
                    rows={3}
                    placeholder="特記事項があれば記入"
                  />
                </div>
              </div>

              <div className="form-actions">
                <button type="submit" className="btn btn-primary">
                  確認画面へ →
                </button>
                <button
                  type="button"
                  className="btn btn-secondary"
                  onClick={() => {
                    setForm(initialForm);
                    setMessage(null);
                  }}
                >
                  リセット
                </button>
              </div>
            </div>

            {/* 右カラム: ヘルプ */}
            <div>
              <div className="section-box">
                <h3>📋 必要書類</h3>
                <h4>本人確認書類（いずれか1点）</h4>
                <ul style={{ paddingLeft: 20 }}>
                  <li>運転免許証</li>
                  <li>マイナンバーカード</li>
                  <li>保険証</li>
                  <li>パスポート</li>
                </ul>

                <h4 className="mt-16">在勤・在学の場合（追加書類）</h4>
                <ul style={{ paddingLeft: 20 }}>
                  <li>社員証</li>
                  <li>学生証</li>
                  <li>在職証明書</li>
                </ul>
              </div>

              <div className="section-box">
                <h3>📋 業務ルール</h3>
                <div className="info-grid">
                  <div className="info-item">
                    <h4>登録資格</h4>
                    <p>市内在住・在勤・在学者</p>
                  </div>
                  <div className="info-item">
                    <h4>有効期限</h4>
                    <p>登録日から{BUSINESS_RULES.CARD_VALIDITY_YEARS}年間</p>
                  </div>
                  <div className="info-item">
                    <h4>貸出上限</h4>
                    <p>{BUSINESS_RULES.MAX_LENDING_COUNT}冊</p>
                  </div>
                  <div className="info-item">
                    <h4>予約上限</h4>
                    <p>{BUSINESS_RULES.MAX_RESERVATION_COUNT}冊</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>
      ) : (
        /* 確認画面 */
        <div className="section-box">
          <h3>登録内容の確認</h3>
          <div className="detail-list">
            <div className="detail-item">
              <span className="detail-label">氏名</span>
              <span className="detail-value">
                {form.name}（{form.nameKana}）
              </span>
            </div>
            <div className="detail-item">
              <span className="detail-label">生年月日</span>
              <span className="detail-value">{form.birthDate}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">住所</span>
              <span className="detail-value">{form.address}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">電話番号</span>
              <span className="detail-value">{form.phone}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">メール</span>
              <span className="detail-value">{form.email || '未登録'}</span>
            </div>
            <div className="detail-item">
              <span className="detail-label">登録種別</span>
              <span className="detail-value">
                {form.registrationType === 'resident'
                  ? '青空市在住'
                  : form.registrationType === 'commuter'
                  ? '青空市在勤'
                  : '青空市在学'}
              </span>
            </div>
            {form.workplace && (
              <div className="detail-item">
                <span className="detail-label">
                  {form.registrationType === 'commuter' ? '勤務先' : '学校名'}
                </span>
                <span className="detail-value">{form.workplace}</span>
              </div>
            )}
            {form.memo && (
              <div className="detail-item">
                <span className="detail-label">備考</span>
                <span className="detail-value">{form.memo}</span>
              </div>
            )}
          </div>

          <div className="message info mt-16">
            <strong>確認事項</strong>
            <ul style={{ margin: '8px 0 0 0', paddingLeft: 20 }}>
              <li>本人確認書類を確認しましたか？</li>
              <li>住所が青空市内であることを確認しましたか？</li>
              <li>連絡先（電話番号）が正しいことを確認しましたか？</li>
            </ul>
          </div>

          <div className="form-actions">
            <button className="btn btn-primary" onClick={handleSubmit}>
              ✓ 登録する
            </button>
            <button className="btn btn-secondary" onClick={() => setStep('input')}>
              ← 入力に戻る
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
