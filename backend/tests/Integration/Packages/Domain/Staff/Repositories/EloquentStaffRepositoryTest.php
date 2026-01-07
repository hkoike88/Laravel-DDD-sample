<?php

declare(strict_types=1);

namespace Tests\Integration\Packages\Domain\Staff\Repositories;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packages\Domain\Staff\Application\Repositories\EloquentStaffRepository;
use Packages\Domain\Staff\Domain\Exceptions\StaffNotFoundException;
use Packages\Domain\Staff\Domain\Model\Staff;
use Packages\Domain\Staff\Domain\ValueObjects\Email;
use Packages\Domain\Staff\Domain\ValueObjects\Password;
use Packages\Domain\Staff\Domain\ValueObjects\StaffId;
use Packages\Domain\Staff\Domain\ValueObjects\StaffName;
use Tests\TestCase;

/**
 * EloquentStaffRepository 統合テスト
 */
class EloquentStaffRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentStaffRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentStaffRepository;
    }

    /**
     * テスト用の職員エンティティを作成
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createStaff(array $overrides = []): Staff
    {
        return Staff::create(
            id: $overrides['id'] ?? StaffId::generate(),
            email: $overrides['email'] ?? Email::create('test@example.com'),
            password: $overrides['password'] ?? Password::fromPlainText('password123'),
            name: $overrides['name'] ?? StaffName::create('テスト職員'),
        );
    }

    // =========================================================================
    // User Story 1: 職員の永続化
    // =========================================================================

    /**
     * @test
     * 職員を保存して取得できる
     */
    public function test_職員を保存して取得できる(): void
    {
        // Arrange
        $staff = $this->createStaff();

        // Act
        $this->repository->save($staff);
        $found = $this->repository->find($staff->id());

        // Assert
        $this->assertTrue($staff->id()->equals($found->id()));
        $this->assertTrue($staff->email()->equals($found->email()));
        $this->assertSame($staff->password()->hashedValue(), $found->password()->hashedValue());
        $this->assertTrue($staff->name()->equals($found->name()));
        $this->assertSame($staff->isLocked(), $found->isLocked());
        $this->assertSame($staff->failedLoginAttempts(), $found->failedLoginAttempts());
    }

    /**
     * @test
     * 存在しない職員IDで検索するとnullが返る（findOrNull）
     */
    public function test_存在しない職員_i_dで検索するとnullが返る(): void
    {
        // Act
        $result = $this->repository->findOrNull(StaffId::generate());

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     * 存在しない職員IDで検索すると例外がスローされる（find）
     */
    public function test_存在しない職員_i_dで検索すると例外がスローされる(): void
    {
        // Assert
        $this->expectException(StaffNotFoundException::class);

        // Act
        $this->repository->find(StaffId::generate());
    }

    // =========================================================================
    // User Story 2: メールアドレスによる検索
    // =========================================================================

    /**
     * @test
     * メールアドレスで職員を検索できる
     */
    public function test_メールアドレスで職員を検索できる(): void
    {
        // Arrange
        $email = Email::create('unique@example.com');
        $staff = $this->createStaff(['email' => $email]);
        $this->repository->save($staff);

        // Act
        $found = $this->repository->findByEmail($email);

        // Assert
        $this->assertNotNull($found);
        $this->assertTrue($staff->id()->equals($found->id()));
        $this->assertTrue($email->equals($found->email()));
    }

    /**
     * @test
     * 存在しないメールアドレスで検索するとnullが返る
     */
    public function test_存在しないメールアドレスで検索するとnullが返る(): void
    {
        // Act
        $result = $this->repository->findByEmail(Email::create('notfound@example.com'));

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     * メールアドレスの存在確認ができる
     */
    public function test_メールアドレスの存在確認ができる(): void
    {
        // Arrange
        $email = Email::create('exists@example.com');
        $staff = $this->createStaff(['email' => $email]);
        $this->repository->save($staff);

        // Act & Assert
        $this->assertTrue($this->repository->existsByEmail($email));
        $this->assertFalse($this->repository->existsByEmail(Email::create('notexists@example.com')));
    }

    // =========================================================================
    // User Story 3: 職員情報の更新
    // =========================================================================

    /**
     * @test
     * ロック状態を持つ職員を保存して復元できる
     */
    public function test_ロック状態を持つ職員を保存して復元できる(): void
    {
        // Arrange
        $lockedAt = new DateTimeImmutable('2024-01-01 12:00:00');
        $staff = Staff::reconstruct(
            id: StaffId::generate(),
            email: Email::create('locked@example.com'),
            password: Password::fromPlainText('password123'),
            name: StaffName::create('ロック済み職員'),
            isAdmin: false,
            isLocked: true,
            failedLoginAttempts: 5,
            lockedAt: $lockedAt,
        );

        // Act
        $this->repository->save($staff);
        $found = $this->repository->find($staff->id());

        // Assert
        $this->assertTrue($found->isLocked());
        $this->assertSame(5, $found->failedLoginAttempts());
        $this->assertNotNull($found->lockedAt());
        $this->assertSame($lockedAt->getTimestamp(), $found->lockedAt()->getTimestamp());
    }

    /**
     * @test
     * 既存の職員を更新しても新しいレコードは作成されない
     */
    public function test_既存の職員を更新しても新しいレコードは作成されない(): void
    {
        // Arrange
        $staff = $this->createStaff();
        $this->repository->save($staff);

        // Act
        $lockedStaff = Staff::reconstruct(
            id: $staff->id(),
            email: $staff->email(),
            password: $staff->password(),
            name: $staff->name(),
            isAdmin: false,
            isLocked: true,
            failedLoginAttempts: 5,
            lockedAt: new DateTimeImmutable,
        );
        $this->repository->save($lockedStaff);

        // Assert - メールアドレスで検索して1件だけ存在することを確認
        $found = $this->repository->findByEmail($staff->email());
        $this->assertNotNull($found);
        $this->assertTrue($staff->id()->equals($found->id()));
    }

    // =========================================================================
    // User Story 4: 職員の削除
    // =========================================================================

    /**
     * @test
     * 職員を削除できる
     */
    public function test_職員を削除できる(): void
    {
        // Arrange
        $staff = $this->createStaff();
        $this->repository->save($staff);

        // Act
        $this->repository->delete($staff->id());

        // Assert
        $this->assertNull($this->repository->findOrNull($staff->id()));
    }

    /**
     * @test
     * 存在しない職員IDで削除してもエラーにならない（冪等性）
     */
    public function test_存在しない職員_i_dで削除してもエラーにならない(): void
    {
        // Arrange
        $nonExistentId = StaffId::generate();

        // Act & Assert (例外がスローされないことを確認)
        $this->repository->delete($nonExistentId);
        $this->assertTrue(true);
    }
}
