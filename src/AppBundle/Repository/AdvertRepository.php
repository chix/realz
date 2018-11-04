<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Advert;

class AdvertRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param integer $limit
     * @return Advert[]
     */
    public function getLatestAdverts($limit = 20)
    {
        return $this->findBy(['deletedAt' => null], ['id' => 'desc'], $limit);
    }
}
