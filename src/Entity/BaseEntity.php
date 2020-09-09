<?php

declare(strict_types=1);

namespace App\Entity;

abstract class BaseEntity
{
    use Traits\SoftDeleteable;
    use Traits\Timestampable;

    public function __construct()
    {
        $now = new \DateTime();
        $this->setCreatedAt($now);
        $this->setUpdatedAt($now);
    }
}
