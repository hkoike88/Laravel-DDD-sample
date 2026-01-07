<?php

declare(strict_types=1);

namespace Packages\Domain\Staff\Domain\Exceptions;

use Exception;

/**
 * Staff ドメイン例外の基底クラス
 *
 * Staff ドメイン内で発生するすべての例外の親クラス。
 */
abstract class StaffDomainException extends Exception {}
