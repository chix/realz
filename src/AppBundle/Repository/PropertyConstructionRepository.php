<?php

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\PropertyConstruction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class PropertyConstructionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyConstruction::class);
    }
}
