<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\City;
use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
