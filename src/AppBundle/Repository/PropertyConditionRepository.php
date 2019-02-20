<?php

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\PropertyCondition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class PropertyConditionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyCondition::class);
    }
}
