/**
 * モックデータ - 図書館システムプロトタイプ
 */
import type { Book, User, Lending, Reservation } from '../types';

/**
 * 蔵書データ
 */
export const mockBooks: Book[] = [
  {
    id: 'B001',
    isbn: '978-4-10-101010-1',
    title: '吾輩は猫である',
    author: '夏目漱石',
    publisher: '新潮社',
    publishedDate: '2003-06-01',
    materialType: '一般図書',
    genre: '文学',
    status: '貸出可',
    location: '1階 文学コーナー',
    registeredAt: '2020-04-01',
  },
  {
    id: 'B002',
    isbn: '978-4-10-101010-2',
    title: '坊っちゃん',
    author: '夏目漱石',
    publisher: '新潮社',
    publishedDate: '2003-06-01',
    materialType: '一般図書',
    genre: '文学',
    status: '貸出中',
    location: '1階 文学コーナー',
    registeredAt: '2020-04-01',
  },
  {
    id: 'B003',
    isbn: '978-4-10-101010-3',
    title: 'こころ',
    author: '夏目漱石',
    publisher: '新潮社',
    publishedDate: '2004-03-01',
    materialType: '一般図書',
    genre: '文学',
    status: '予約あり',
    location: '1階 文学コーナー',
    registeredAt: '2020-04-01',
  },
  {
    id: 'B004',
    isbn: '978-4-00-310101-1',
    title: '銀河鉄道の夜',
    author: '宮沢賢治',
    publisher: '岩波書店',
    publishedDate: '2010-09-01',
    materialType: '一般図書',
    genre: '文学',
    status: '貸出可',
    location: '1階 文学コーナー',
    registeredAt: '2020-05-15',
  },
  {
    id: 'B005',
    isbn: '978-4-06-521234-5',
    title: 'React入門',
    author: '田中太郎',
    publisher: '講談社',
    publishedDate: '2024-10-01',
    materialType: '新刊図書',
    genre: 'コンピュータ',
    status: '貸出可',
    location: '2階 IT書籍コーナー',
    registeredAt: '2024-10-15',
  },
  {
    id: 'B006',
    isbn: '978-4-06-521234-6',
    title: 'TypeScript実践ガイド',
    author: '山田花子',
    publisher: '技術評論社',
    publishedDate: '2024-09-15',
    materialType: '新刊図書',
    genre: 'コンピュータ',
    status: '貸出中',
    location: '2階 IT書籍コーナー',
    registeredAt: '2024-09-20',
  },
  {
    id: 'B007',
    isbn: '978-4-88-888888-1',
    title: '青空市郷土史',
    author: '青空市教育委員会',
    publisher: '青空市',
    publishedDate: '2015-03-01',
    materialType: '参考図書',
    genre: '郷土資料',
    status: '禁帯出',
    location: '3階 郷土資料室',
    registeredAt: '2015-04-01',
  },
  {
    id: 'B008',
    isbn: '978-4-12-345678-9',
    title: '週刊文春 2024年12月号',
    author: '文藝春秋',
    publisher: '文藝春秋',
    publishedDate: '2024-12-01',
    materialType: '雑誌',
    genre: '雑誌',
    status: '貸出可',
    location: '1階 雑誌コーナー',
    registeredAt: '2024-12-05',
  },
];

/**
 * 利用者データ
 */
export const mockUsers: User[] = [
  {
    id: 'U001',
    cardNumber: '0001-0001',
    name: '松本洋子',
    nameKana: 'マツモトヨウコ',
    birthDate: '1962-05-15',
    address: '青空市緑町1-2-3',
    phone: '090-1234-5678',
    email: 'matsumoto@example.com',
    registeredAt: '2023-04-01',
    expiresAt: '2025-03-31',
    status: '有効',
    memo: '読書会主宰',
  },
  {
    id: 'U002',
    cardNumber: '0001-0002',
    name: '田中一郎',
    nameKana: 'タナカイチロウ',
    birthDate: '1985-08-20',
    address: '青空市青葉2-3-4',
    phone: '080-2345-6789',
    registeredAt: '2024-01-15',
    expiresAt: '2025-01-14',
    status: '有効',
  },
  {
    id: 'U003',
    cardNumber: '0001-0003',
    name: '鈴木花子',
    nameKana: 'スズキハナコ',
    birthDate: '1995-12-10',
    address: '青空市桜町3-4-5',
    phone: '070-3456-7890',
    email: 'suzuki.hanako@example.com',
    registeredAt: '2023-06-01',
    expiresAt: '2024-05-31',
    status: '期限切れ',
  },
  {
    id: 'U004',
    cardNumber: '0001-0004',
    name: '佐藤次郎',
    nameKana: 'サトウジロウ',
    birthDate: '1978-03-25',
    address: '青空市若葉4-5-6',
    phone: '090-4567-8901',
    registeredAt: '2024-06-10',
    expiresAt: '2025-06-09',
    status: '停止中',
    memo: '延滞中のため貸出停止',
  },
  {
    id: 'U005',
    cardNumber: '0001-0005',
    name: '高橋美穂',
    nameKana: 'タカハシミホ',
    birthDate: '2010-07-08',
    address: '青空市緑町5-6-7',
    phone: '080-5678-9012',
    registeredAt: '2024-08-01',
    expiresAt: '2025-07-31',
    status: '有効',
    memo: '小学生（保護者：高橋太郎）',
  },
];

/**
 * 貸出データ
 */
export const mockLendings: Lending[] = [
  {
    id: 'L001',
    bookId: 'B002',
    userId: 'U001',
    lentAt: '2024-12-10',
    dueDate: '2024-12-24',
    isOverdue: false,
  },
  {
    id: 'L002',
    bookId: 'B006',
    userId: 'U002',
    lentAt: '2024-12-15',
    dueDate: '2024-12-22',
    isOverdue: false,
  },
  {
    id: 'L003',
    bookId: 'B003',
    userId: 'U004',
    lentAt: '2024-11-20',
    dueDate: '2024-12-04',
    isOverdue: true,
  },
];

/**
 * 予約データ
 */
export const mockReservations: Reservation[] = [
  {
    id: 'R001',
    bookId: 'B002',
    userId: 'U002',
    reservedAt: '2024-12-12',
    position: 1,
    status: '予約中',
  },
  {
    id: 'R002',
    bookId: 'B002',
    userId: 'U005',
    reservedAt: '2024-12-14',
    position: 2,
    status: '予約中',
  },
  {
    id: 'R003',
    bookId: 'B003',
    userId: 'U001',
    reservedAt: '2024-12-08',
    position: 1,
    status: '予約中',
  },
  {
    id: 'R004',
    bookId: 'B006',
    userId: 'U001',
    reservedAt: '2024-12-16',
    position: 1,
    status: '取り置き中',
    notifiedAt: '2024-12-18',
    holdUntil: '2024-12-25',
  },
];

/**
 * 蔵書IDから蔵書情報を取得
 */
export function getBookById(id: string): Book | undefined {
  return mockBooks.find((book) => book.id === id);
}

/**
 * 利用者IDから利用者情報を取得
 */
export function getUserById(id: string): User | undefined {
  return mockUsers.find((user) => user.id === id);
}

/**
 * カード番号から利用者情報を取得
 */
export function getUserByCardNumber(cardNumber: string): User | undefined {
  return mockUsers.find((user) => user.cardNumber === cardNumber);
}

/**
 * 利用者の現在の貸出数を取得
 */
export function getCurrentLendingCount(userId: string): number {
  return mockLendings.filter(
    (lending) => lending.userId === userId && !lending.returnedAt
  ).length;
}

/**
 * 利用者の現在の予約数を取得
 */
export function getCurrentReservationCount(userId: string): number {
  return mockReservations.filter(
    (reservation) =>
      reservation.userId === userId &&
      (reservation.status === '予約中' || reservation.status === '取り置き中')
  ).length;
}

/**
 * 蔵書の予約数を取得
 */
export function getBookReservationCount(bookId: string): number {
  return mockReservations.filter(
    (reservation) =>
      reservation.bookId === bookId &&
      (reservation.status === '予約中' || reservation.status === '取り置き中')
  ).length;
}
