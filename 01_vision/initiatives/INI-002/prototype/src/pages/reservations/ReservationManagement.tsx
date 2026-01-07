/**
 * UC-001-006: 予約管理画面
 *
 * 職員が予約の確認・取り置き管理を行う
 * - 予約一覧表示
 * - 取り置き状況確認
 * - 予約キャンセル
 */
import { useState } from 'react';
import { mockReservations, getBookById, getUserById } from '../../data/mockData';
import { BUSINESS_RULES } from '../../types';
import type { Reservation, ReservationStatus } from '../../types';

/**
 * ステータスのバッジクラス
 */
function getStatusClass(status: ReservationStatus): string {
  const classes: Record<ReservationStatus, string> = {
    予約中: 'reserved',
    取り置き中: 'active',
    完了: 'available',
    キャンセル: 'forbidden',
    期限切れ: 'expired',
  };
  return classes[status] || '';
}

interface ReservationWithDetails extends Reservation {
  bookTitle: string;
  bookAuthor: string;
  userName: string;
  userPhone: string;
  userEmail?: string;
}

export default function ReservationManagement() {
  const [filterStatus, setFilterStatus] = useState<ReservationStatus | 'all'>('all');
  const [message, setMessage] = useState<{ type: 'success' | 'error' | 'info'; text: string } | null>(null);

  // 予約データに詳細情報を付加
  const reservationsWithDetails: ReservationWithDetails[] = mockReservations.map((r) => {
    const book = getBookById(r.bookId);
    const user = getUserById(r.userId);
    return {
      ...r,
      bookTitle: book?.title || '不明',
      bookAuthor: book?.author || '不明',
      userName: user?.name || '不明',
      userPhone: user?.phone || '不明',
      userEmail: user?.email,
    };
  });

  // フィルタリング
  const filteredReservations = reservationsWithDetails.filter((r) =>
    filterStatus === 'all' ? true : r.status === filterStatus
  );

  // ステータス別カウント
  const statusCounts = {
    all: mockReservations.length,
    予約中: mockReservations.filter((r) => r.status === '予約中').length,
    取り置き中: mockReservations.filter((r) => r.status === '取り置き中').length,
    完了: mockReservations.filter((r) => r.status === '完了').length,
    キャンセル: mockReservations.filter((r) => r.status === 'キャンセル').length,
    期限切れ: mockReservations.filter((r) => r.status === '期限切れ').length,
  };

  /**
   * 取り置き処理（連絡完了）
   */
  const handleNotify = (reservation: ReservationWithDetails) => {
    const today = new Date();
    const holdUntil = new Date(today);
    holdUntil.setDate(holdUntil.getDate() + BUSINESS_RULES.HOLD_PERIOD);
    const holdUntilStr = holdUntil.toISOString().split('T')[0];

    setMessage({
      type: 'success',
      text: `${reservation.userName}様に連絡完了。取り置き期限: ${holdUntilStr}（プロトタイプ: データは保存されません）`,
    });
  };

  /**
   * 貸出完了処理
   */
  const handleComplete = (reservation: ReservationWithDetails) => {
    setMessage({
      type: 'success',
      text: `「${reservation.bookTitle}」の予約→貸出が完了しました（プロトタイプ: データは保存されません）`,
    });
  };

  /**
   * キャンセル処理
   */
  const handleCancel = (reservation: ReservationWithDetails) => {
    if (window.confirm(`${reservation.userName}様の「${reservation.bookTitle}」の予約をキャンセルしますか？`)) {
      setMessage({
        type: 'info',
        text: `予約をキャンセルしました（プロトタイプ: データは保存されません）`,
      });
    }
  };

  return (
    <div className="reservation-management">
      <header className="page-header">
        <h1>📋 予約管理</h1>
        <p className="subtitle">UC-001-006 / EP-03 予約管理</p>
      </header>

      {/* メッセージ */}
      {message && <div className={`message ${message.type}`}>{message.text}</div>}

      {/* ステータスフィルタ */}
      <div className="section-box">
        <h3>フィルタ</h3>
        <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
          <button
            className={`btn ${filterStatus === 'all' ? 'btn-primary' : ''}`}
            onClick={() => setFilterStatus('all')}
          >
            すべて ({statusCounts.all})
          </button>
          <button
            className={`btn ${filterStatus === '予約中' ? 'btn-primary' : ''}`}
            onClick={() => setFilterStatus('予約中')}
          >
            予約中 ({statusCounts.予約中})
          </button>
          <button
            className={`btn ${filterStatus === '取り置き中' ? 'btn-primary' : ''}`}
            onClick={() => setFilterStatus('取り置き中')}
          >
            取り置き中 ({statusCounts.取り置き中})
          </button>
          <button
            className={`btn ${filterStatus === '完了' ? 'btn-primary' : ''}`}
            onClick={() => setFilterStatus('完了')}
          >
            完了 ({statusCounts.完了})
          </button>
          <button
            className={`btn ${filterStatus === 'キャンセル' ? 'btn-primary' : ''}`}
            onClick={() => setFilterStatus('キャンセル')}
          >
            キャンセル ({statusCounts.キャンセル})
          </button>
        </div>
      </div>

      {/* 予約一覧 */}
      <div className="section-box">
        <h3>予約一覧 ({filteredReservations.length}件)</h3>
        {filteredReservations.length > 0 ? (
          <table className="data-table">
            <thead>
              <tr>
                <th>書籍</th>
                <th>予約者</th>
                <th>連絡先</th>
                <th>順番</th>
                <th>状態</th>
                <th>取り置き期限</th>
                <th>操作</th>
              </tr>
            </thead>
            <tbody>
              {filteredReservations.map((r) => (
                <tr key={r.id}>
                  <td>
                    <strong>{r.bookTitle}</strong>
                    <br />
                    <small>{r.bookAuthor}</small>
                  </td>
                  <td>{r.userName}</td>
                  <td>
                    {r.userPhone}
                    {r.userEmail && (
                      <>
                        <br />
                        <small>{r.userEmail}</small>
                      </>
                    )}
                  </td>
                  <td>{r.position}番目</td>
                  <td>
                    <span className={`status-badge ${getStatusClass(r.status)}`}>
                      {r.status}
                    </span>
                  </td>
                  <td>
                    {r.holdUntil ? (
                      <span
                        style={{
                          color: new Date(r.holdUntil) < new Date() ? '#c00' : 'inherit',
                        }}
                      >
                        {r.holdUntil}
                      </span>
                    ) : (
                      '-'
                    )}
                  </td>
                  <td>
                    <div style={{ display: 'flex', gap: 4, flexWrap: 'wrap' }}>
                      {r.status === '予約中' && (
                        <button className="btn" onClick={() => handleNotify(r)}>
                          連絡完了
                        </button>
                      )}
                      {r.status === '取り置き中' && (
                        <button className="btn btn-primary" onClick={() => handleComplete(r)}>
                          貸出完了
                        </button>
                      )}
                      {(r.status === '予約中' || r.status === '取り置き中') && (
                        <button className="btn btn-danger" onClick={() => handleCancel(r)}>
                          取消
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <div className="empty-state">
            <div className="empty-state-icon">📋</div>
            <h3>該当する予約がありません</h3>
            <p>フィルタ条件を変更してください</p>
          </div>
        )}
      </div>

      {/* 業務ルール */}
      <div className="two-column">
        <div className="section-box">
          <h3>📋 予約フロー</h3>
          <ol style={{ paddingLeft: 20, margin: 0 }}>
            <li>
              <strong>予約中</strong>: 貸出中の本に予約が入っている状態
            </li>
            <li>
              <strong>連絡完了</strong>: 本が返却され、予約者に連絡した状態
            </li>
            <li>
              <strong>取り置き中</strong>: 取り置き棚で{BUSINESS_RULES.HOLD_PERIOD}日間保管
            </li>
            <li>
              <strong>貸出完了</strong>: 予約者が来館し、貸出処理完了
            </li>
          </ol>
        </div>
        <div className="section-box">
          <h3>⚠️ 注意事項</h3>
          <ul style={{ paddingLeft: 20, margin: 0 }}>
            <li>取り置き期限は連絡後{BUSINESS_RULES.HOLD_PERIOD}日間です</li>
            <li>期限切れの場合、次の予約者に回すか書架へ戻します</li>
            <li>キャンセル時は次の予約者の順番を繰り上げます</li>
            <li>連絡がつかない場合は3回まで試行してください</li>
          </ul>
        </div>
      </div>
    </div>
  );
}
