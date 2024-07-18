<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PropertyConstruction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PropertyConstruction> */
final class PropertyConstructionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyConstruction::class);
    }
}
