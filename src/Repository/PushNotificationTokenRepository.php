<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\CityDistrict;
use App\Entity\PushNotificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class PushNotificationTokenRepository extends ServiceEntityRepository
{
    /**
     * @var CityRepository
     */
    protected $cityRepository;

    public function __construct(ManagerRegistry $registry, CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;

        parent::__construct($registry, PushNotificationToken::class);
    }

    /**
     * @return PushNotificationToken[]
     */
    public function getActiveAndEnabled(): array
    {
        return $this->findBy(['active' => 1, 'enabled' => 1]);
    }

    /**
     * @return Advert[]
     */
    public function getUnnotifiedAdvertsForToken(PushNotificationToken $token): array
    {
        $oneHourAgo = (new \DateTime())->sub((new \DateInterval('PT1H')))->format('Y-m-d H:i:s');
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('ad.id')
            ->from('App:PushNotificationToken', 'pnt')
            ->leftJoin('pnt.adverts', 'ad')
            ->andWhere('pnt.token = :token')
            ->andWhere('ad.createdAt >= :maxAge');

        $adverts = [];
        $filters = $token->getFilters();
        if (!empty($filters)) {
            foreach ($filters as $cityCode => $cityFilters) {
                $city = $this->cityRepository->findOneByCode($cityCode);
                if ($city === null) {
                    continue;
                }

                $qb = $this->getEntityManager()->createQueryBuilder();
                $qb->select('a')
                    ->from('App:Advert', 'a')
                    ->leftJoin('a.type', 'at')
                    ->leftJoin('a.property', 'p')
                    ->leftJoin('p.location', 'l')
                    ->leftJoin('l.city', 'c')
                    ->andWhere('a.createdAt >= :maxAge')
                    ->andWhere($qb->expr()->notIn('a.id', $ids->getDQL()))
                    ->andWhere('at.code = :advertTypeCode')
                    ->andWhere('c.id = :cityId')
                    ->setParameter('token', $token->getToken())
                    ->setParameter('maxAge', $oneHourAgo)
                    ->setParameter('cityId', $city->getId());

                $advertTypeCode = AdvertType::TYPE_SALE;
                foreach ($cityFilters as $type => $filter) {
                    switch ($type) {
                        case 'advertType':
                            $advertTypeCode = $filter;
                            break;
                        case 'price':
                            if (isset($filter['lte'])) {
                                $qb->andWhere('(a.price IS NULL OR a.price <= :ltePrice)');
                                $qb->setParameter('ltePrice', $filter['lte']);
                            }
                            if (isset($filter['gte'])) {
                                $qb->andWhere('(a.price IS NULL OR a.price >= :gtePrice)');
                                $qb->setParameter('gtePrice', $filter['gte']);
                            }
                            break;
                        case 'disposition':
                            if (!empty($filter)) {
                                $qb->leftJoin('p.disposition', 'pd');
                                $qb->andWhere('pd.code IN (:dispositionCodes)');
                                $qb->setParameter('dispositionCodes', $filter);
                            }
                            break;
                        case 'cityDistrict':
                            if (!empty($filter)) {
                                $qb->leftJoin('l.cityDistrict', 'cd');
                                if (in_array(CityDistrict::CODE_UNASSIGNED, $filter)) {
                                    $qb->andWhere('(l.cityDistrict IS NULL OR cd.code IN (:cityDistrictCodes))');
                                } else {
                                    $qb->andWhere('cd.code IN (:cityDistrictCodes)');
                                }
                                $qb->setParameter('cityDistrictCodes', $filter);
                            }
                            break;
                    }
                }
                $qb->setParameter('advertTypeCode', $advertTypeCode);

                $adverts = array_merge($adverts, $qb->getQuery()->getResult());
            }
        }

        return $adverts;
    }
}
