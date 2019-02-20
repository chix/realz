<?php

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\PropertyDisposition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class PropertyDispositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PropertyDisposition::class);
    }
}
