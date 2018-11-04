<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Property;

class PropertyRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @return Property|null
     */
    public function findProperty()
    {
        return null;
    }
}
