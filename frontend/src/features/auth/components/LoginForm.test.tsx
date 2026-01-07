/**
 * LoginForm コンポーネントのユニットテスト
 *
 * ログインフォームの表示、バリデーション、送信動作をテスト
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { LoginForm } from './LoginForm'

describe('LoginForm', () => {
  const mockOnSubmit = vi.fn()

  beforeEach(() => {
    mockOnSubmit.mockClear()
  })

  /**
   * 基本表示テスト
   */
  describe('基本表示', () => {
    it('メールアドレス入力欄が表示される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} />)

      expect(screen.getByLabelText('メールアドレス')).toBeInTheDocument()
    })

    it('パスワード入力欄が表示される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} />)

      expect(screen.getByLabelText('パスワード')).toBeInTheDocument()
    })

    it('ログインボタンが表示される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} />)

      expect(screen.getByRole('button', { name: 'ログイン' })).toBeInTheDocument()
    })

    it('パスワード入力欄がマスク表示される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} />)

      const passwordInput = screen.getByLabelText('パスワード')
      expect(passwordInput).toHaveAttribute('type', 'password')
    })
  })

  /**
   * フォーム送信テスト
   */
  describe('フォーム送信', () => {
    it('有効な入力で送信が成功する', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
      await user.type(screen.getByLabelText('パスワード'), 'password123')
      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(mockOnSubmit).toHaveBeenCalled()
        const callArgs = mockOnSubmit.mock.calls[0][0]
        expect(callArgs).toEqual({
          email: 'test@example.com',
          password: 'password123',
        })
      })
    })

    it('Enter キーでフォームが送信される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
      await user.type(screen.getByLabelText('パスワード'), 'password123{enter}')

      await waitFor(() => {
        expect(mockOnSubmit).toHaveBeenCalled()
        const callArgs = mockOnSubmit.mock.calls[0][0]
        expect(callArgs).toEqual({
          email: 'test@example.com',
          password: 'password123',
        })
      })
    })
  })

  /**
   * ローディング状態テスト
   */
  describe('ローディング状態', () => {
    it('isLoading=true でボタンが無効化される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} isLoading={true} />)

      const button = screen.getByRole('button', { name: 'ログイン中...' })
      expect(button).toBeDisabled()
    })

    it('isLoading=true で「ログイン中...」と表示される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} isLoading={true} />)

      expect(screen.getByRole('button')).toHaveTextContent('ログイン中...')
    })

    it('isLoading=true で入力フィールドが無効化される', () => {
      render(<LoginForm onSubmit={mockOnSubmit} isLoading={true} />)

      expect(screen.getByLabelText('メールアドレス')).toBeDisabled()
      expect(screen.getByLabelText('パスワード')).toBeDisabled()
    })
  })

  /**
   * バリデーションエラーテスト
   */
  describe('バリデーションエラー', () => {
    it('空のメールアドレスでエラーが表示される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('パスワード'), 'password123')
      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(screen.getByText('メールアドレスを入力してください')).toBeInTheDocument()
      })
      expect(mockOnSubmit).not.toHaveBeenCalled()
    })

    it('無効なメール形式でエラーが表示される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('メールアドレス'), 'invalid-email')
      await user.type(screen.getByLabelText('パスワード'), 'password123')
      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(screen.getByText('有効なメールアドレスを入力してください')).toBeInTheDocument()
      })
      expect(mockOnSubmit).not.toHaveBeenCalled()
    })

    it('空のパスワードでエラーが表示される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(screen.getByText('パスワードを入力してください')).toBeInTheDocument()
      })
      expect(mockOnSubmit).not.toHaveBeenCalled()
    })

    it('8文字未満のパスワードでエラーが表示される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.type(screen.getByLabelText('メールアドレス'), 'test@example.com')
      await user.type(screen.getByLabelText('パスワード'), 'short')
      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(screen.getByText('パスワードは8文字以上で入力してください')).toBeInTheDocument()
      })
      expect(mockOnSubmit).not.toHaveBeenCalled()
    })
  })

  /**
   * API エラー表示テスト
   */
  describe('API エラー表示', () => {
    it('apiError が表示される', () => {
      render(
        <LoginForm
          onSubmit={mockOnSubmit}
          apiError="メールアドレスまたはパスワードが正しくありません"
        />
      )

      expect(
        screen.getByText('メールアドレスまたはパスワードが正しくありません')
      ).toBeInTheDocument()
    })

    it('apiError が role=alert で表示される', () => {
      render(
        <LoginForm
          onSubmit={mockOnSubmit}
          apiError="メールアドレスまたはパスワードが正しくありません"
        />
      )

      expect(screen.getByRole('alert')).toHaveTextContent(
        'メールアドレスまたはパスワードが正しくありません'
      )
    })
  })

  /**
   * アクセシビリティテスト
   */
  describe('アクセシビリティ', () => {
    it('Tab キーでフォーカスが移動する', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      const emailInput = screen.getByLabelText('メールアドレス')
      const passwordInput = screen.getByLabelText('パスワード')
      const submitButton = screen.getByRole('button', { name: 'ログイン' })

      await user.tab()
      expect(emailInput).toHaveFocus()

      await user.tab()
      expect(passwordInput).toHaveFocus()

      await user.tab()
      expect(submitButton).toHaveFocus()
    })

    it('エラー時に aria-invalid が設定される', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        expect(screen.getByLabelText('メールアドレス')).toHaveAttribute('aria-invalid', 'true')
      })
    })

    it('エラーメッセージが aria-describedby で関連付けられる', async () => {
      const user = userEvent.setup()
      render(<LoginForm onSubmit={mockOnSubmit} />)

      await user.click(screen.getByRole('button', { name: 'ログイン' }))

      await waitFor(() => {
        const emailInput = screen.getByLabelText('メールアドレス')
        const describedBy = emailInput.getAttribute('aria-describedby')
        expect(describedBy).toBeTruthy()
      })
    })
  })
})
