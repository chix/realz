<?php

namespace AppBundle\Entity;

use Gedmo\Timestampable\Traits\TimestampableEntity as TimestampableTrait;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity as SoftDeleteableTrait;

abstract class BaseEntity
{
    use TimestampableTrait;
    use SoftDeleteableTrait;
}
