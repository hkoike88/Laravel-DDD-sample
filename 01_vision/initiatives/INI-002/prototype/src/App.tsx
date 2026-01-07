/**
 * 図書館業務システム プロトタイプ - メインアプリケーション
 *
 * LIB-001 MVP: 青空市立中央図書館 図書貸出業務システム
 */
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import Dashboard from './pages/Dashboard';
import BookSearch from './pages/books/BookSearch';
import BookRegister from './pages/books/BookRegister';
import Lending from './pages/lending/Lending';
import Return from './pages/lending/Return';
import ReservationRegister from './pages/reservations/ReservationRegister';
import ReservationManagement from './pages/reservations/ReservationManagement';
import UserSearch from './pages/users/UserSearch';
import UserRegister from './pages/users/UserRegister';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Layout />}>
          <Route index element={<Dashboard />} />
          {/* EP-01: 蔵書管理 */}
          <Route path="books">
            <Route path="search" element={<BookSearch />} />
            <Route path="register" element={<BookRegister />} />
          </Route>
          {/* EP-02: 貸出・返却 */}
          <Route path="lending" element={<Lending />} />
          <Route path="return" element={<Return />} />
          {/* EP-03: 予約管理 */}
          <Route path="reservations">
            <Route path="register" element={<ReservationRegister />} />
            <Route path="manage" element={<ReservationManagement />} />
          </Route>
          {/* EP-04: 利用者管理 */}
          <Route path="users">
            <Route path="search" element={<UserSearch />} />
            <Route path="register" element={<UserRegister />} />
          </Route>
        </Route>
      </Routes>
    </BrowserRouter>
  );
}
