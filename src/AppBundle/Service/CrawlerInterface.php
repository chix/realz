<?php

namespace AppBundle\Service;

use AppBundle\Entity\Advert;

interface CrawlerInterface
{

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return Advert[]
     */
    public function getNewAdverts();

}
