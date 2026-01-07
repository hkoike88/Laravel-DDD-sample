/**
 * BookStatusBadgeコンポーネントのユニットテスト
 * TC-N006, TC-N007, TC-N008
 */
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import { BookStatusBadge } from './BookStatusBadge'

describe('BookStatusBadge', () => {
  /**
   * TC-N006: 貸出可能な蔵書の状態バッジ表示
   */
  describe('status: available', () => {
    it('「貸出可」と表示される', () => {
      render(<BookStatusBadge status="available" />)

      expect(screen.getByText('貸出可')).toBeInTheDocument()
    })

    it('緑色のスタイルが適用される', () => {
      render(<BookStatusBadge status="available" />)

      const badge = screen.getByText('貸出可')
      expect(badge).toHaveClass('bg-green-100')
      expect(badge).toHaveClass('text-green-800')
    })
  })

  /**
   * TC-N007: 貸出中の蔵書の状態バッジ表示
   */
  describe('status: borrowed', () => {
    it('「貸出中」と表示される', () => {
      render(<BookStatusBadge status="borrowed" />)

      expect(screen.getByText('貸出中')).toBeInTheDocument()
    })

    it('赤色のスタイルが適用される', () => {
      render(<BookStatusBadge status="borrowed" />)

      const badge = screen.getByText('貸出中')
      expect(badge).toHaveClass('bg-red-100')
      expect(badge).toHaveClass('text-red-800')
    })
  })

  /**
   * TC-N008: 予約ありの蔵書の状態バッジ表示
   */
  describe('status: reserved', () => {
    it('「予約あり」と表示される', () => {
      render(<BookStatusBadge status="reserved" />)

      expect(screen.getByText('予約あり')).toBeInTheDocument()
    })

    it('黄色のスタイルが適用される', () => {
      render(<BookStatusBadge status="reserved" />)

      const badge = screen.getByText('予約あり')
      expect(badge).toHaveClass('bg-yellow-100')
      expect(badge).toHaveClass('text-yellow-800')
    })
  })

  /**
   * 共通スタイルの検証
   */
  describe('共通スタイル', () => {
    it('バッジの基本スタイルが適用される', () => {
      render(<BookStatusBadge status="available" />)

      const badge = screen.getByText('貸出可')
      expect(badge).toHaveClass('rounded-full')
      expect(badge).toHaveClass('text-xs')
      expect(badge).toHaveClass('font-medium')
    })
  })
})
