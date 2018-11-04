<?php

namespace AppBundle\Repository;

use AppBundle\Entity\City;
use AppBundle\Entity\Location;

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
