<?php

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\Advert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class AdvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advert::class);
    }

    /**
     * @return Advert[]
     */
    public function getLatestAdverts(int $limit = 20): array
    {
        return $this->findBy(['deletedAt' => null], ['id' => 'desc'], $limit);
    }
}
