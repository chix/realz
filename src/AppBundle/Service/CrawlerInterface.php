<?php

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Advert;

interface CrawlerInterface
{
    public function getIdentifier(): string;

    /**
     * @return Advert[]
     */
    public function getNewAdverts(string $advertType, string $propertyType): array;
}
