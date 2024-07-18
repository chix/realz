<?php

declare(strict_types=1);

namespace App\Entity;

abstract class BaseEntity
{
    use Traits\SoftDeleteable;
    use Traits\Timestampable;

    public function __construct()
    {
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->setUpdatedAt(new \DateTime());
    }
}
