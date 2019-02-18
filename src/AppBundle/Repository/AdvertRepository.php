<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Advert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AdvertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advert::class);
    }

    /**
     * @param integer $limit
     * @return Advert[]
     */
    public function getLatestAdverts($limit = 20)
    {
        return $this->findBy(['deletedAt' => null], ['id' => 'desc'], $limit);
    }
}
