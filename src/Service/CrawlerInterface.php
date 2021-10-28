<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;

interface CrawlerInterface
{
    public function getIdentifier(): string;

    /**
     * @return Advert[]
     */
    public function getNewAdverts(string $advertType, string $propertyType, ?int $cityCode): array;
}
