<?php

declare(strict_types=1);

namespace AppBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
trait Locatable
{

    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", precision=10, scale=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $latitude;
     
    /**
     * @var float|null
     *
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $longitude;

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }
}
