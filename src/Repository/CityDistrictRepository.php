<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CityDistrict;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CityDistrict> */
final class CityDistrictRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CityDistrict::class);
    }
}
