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
    public function getLatestAdverts(string $type, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('qb')
            ->select('a')
            ->from('AppBundle:Advert', 'a')
            ->leftJoin('a.type', 'at')
            ->leftJoin('a.property', 'p')
            ->andWhere('a.deletedAt IS NULL')
            ->andWhere('at.code = :advertTypeCode')
            ->setParameter('advertTypeCode', $type)
            ->orderBy('a.updatedAt', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}
