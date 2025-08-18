<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\CityDistrict;
use App\Entity\PropertySubtype;
use App\Entity\PropertyType;
use App\Entity\PushNotificationToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<PushNotificationToken> */
final class PushNotificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(
        protected ManagerRegistry $registry,
        private CityRepository $cityRepository,
        private DistrictRepository $districtRepository
    ) {
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
        $oneHourAgo = (new \DateTime())->sub(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('ad.id')
            ->from(PushNotificationToken::class, 'pnt')
            ->leftJoin('pnt.adverts', 'ad')
            ->andWhere('pnt.token = :token')
            ->andWhere('ad.createdAt >= :maxAge');

        $adverts = [];
        $filters = $token->getFilters();
        if (!empty($filters)) {
            foreach ($filters as $cityFilters) {
                $qb = $this->getEntityManager()->createQueryBuilder();
                $qb->select('a')
                    ->from(Advert::class, 'a')
                    ->leftJoin('a.type', 'at')
                    ->leftJoin('a.property', 'p')
                    ->leftJoin('p.type', 'pt')
                    ->leftJoin('p.location', 'l')
                    ->andWhere('a.createdAt >= :maxAge')
                    ->andWhere($qb->expr()->notIn('a.id', $ids->getDQL()))
                    ->andWhere('at.code = :advertTypeCode')
                    ->andWhere('pt.code = :propertyTypeCode')
                    ->setParameter('token', $token->getToken())
                    ->setParameter('maxAge', $oneHourAgo);

                $advertTypeCode = AdvertType::TYPE_SALE;
                $propertyTypeCode = PropertyType::TYPE_FLAT;
                foreach ($cityFilters as $type => $filter) {
                    switch ($type) {
                        case 'advertType':
                            $advertTypeCode = $filter;
                            break;
                        case 'propertyType':
                            $propertyTypeCode = $filter;
                            break;
                        case 'propertySubtype':
                            if (!empty($filter)) {
                                $qb->leftJoin('p.subtype', 'pst');
                                if (in_array(PropertySubtype::SUBTYPE_OTHER, $filter)) {
                                    $qb->andWhere('(pst.code IS NULL OR pst.code IN (:subtypeCodes))');
                                } else {
                                    $qb->andWhere('pst.code IN (:subtypeCodes)');
                                }
                                $qb->setParameter('subtypeCodes', $filter);
                            }
                            break;
                        case 'price':
                            $includeNoPrice = $filter['includeNoPrice'] ?? false;
                            if (isset($filter['lte'])) {
                                $qb->andWhere($includeNoPrice ? '(a.price IS NULL OR a.price <= :ltePrice)' : 'a.price <= :ltePrice');
                                $qb->setParameter('ltePrice', $filter['lte']);
                            }
                            if (isset($filter['gte'])) {
                                $qb->andWhere($includeNoPrice ? '(a.price IS NULL OR a.price >= :gtePrice)' : 'a.price >= :gtePrice');
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
                        case 'districtCode':
                            if (!empty($filter)) {
                                $district = $this->districtRepository->findOneByCode($filter);
                                if (null === $district) {
                                    continue 2;
                                }
                                $qb->leftJoin('l.district', 'd');
                                $qb->andWhere('d.id = :districtId');
                                $qb->setParameter('districtId', $district->getId());
                            }
                            break;
                        case 'cityCode':
                            if (!empty($filter)) {
                                $city = $this->cityRepository->findOneByCode($filter);
                                if (null === $city) {
                                    continue 2;
                                }
                                $qb->leftJoin('l.city', 'c');
                                $qb->andWhere('c.id = :cityId');
                                $qb->setParameter('cityId', $city->getId());
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
                $qb->setParameter('propertyTypeCode', $propertyTypeCode);

                $adverts = array_merge($adverts, $qb->getQuery()->getResult());
            }
        }

        return $adverts;
    }
}
