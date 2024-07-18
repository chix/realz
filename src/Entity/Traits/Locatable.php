<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait Locatable
{
    #[ORM\Column(name: 'latitude', type: Types::DECIMAL, precision: 10, scale: 8, nullable: true)]
    private ?string $latitude;

    #[ORM\Column(name: 'longitude', type: Types::DECIMAL, precision: 11, scale: 8, nullable: true)]
    private ?string $longitude;

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }
}
