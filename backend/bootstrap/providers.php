<?php

declare(strict_types=1);

return [
    App\Providers\AppServiceProvider::class,
    Packages\Domain\Book\Application\Providers\BookServiceProvider::class,
    Packages\Domain\Staff\Application\Providers\StaffServiceProvider::class,
];
