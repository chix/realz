<?php

namespace AppBundle\Repository;

use AppBundle\Entity\City;
use AppBundle\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    /**
     * @param City $city
     * @param string|null $street
     * @param float|null $latitude
     * @param float|null $longitude
     * @return Location
     */
    public function findLocation(City $city, $street, $latitude, $longitude)
    {
        return $this->findOneBy([
            'city' => $city,
            'street' => $street,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'deletedAt' => null,
        ]);
    }
}
