<?php

declare(strict_types=1);

namespace AppBundle\Repository;

use AppBundle\Entity\City;
use AppBundle\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

final class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    public function findLocation(City $city, ?string $street, ?float $latitude, ?float $longitude): ?Location
    {
        /** @var Location|null $location */
        $location = $this->findOneBy([
            'city' => $city,
            'street' => $street,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'deletedAt' => null,
        ]);

        return $location;
    }
}
