<?php

namespace AppBundle\Repository;

use AppBundle\Entity\City;
use AppBundle\Entity\Location;

/**
 * LocationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository
{
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
