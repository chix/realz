<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PropertyDisposition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PropertyDisposition> */
final class PropertyDispositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyDisposition::class);
    }
}
