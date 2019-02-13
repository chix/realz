<?php

namespace AppBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
trait Locatable
{

    /**
     * @var float $latitude
     *
     * @ORM\Column(type="decimal", precision=10, scale=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $latitude;
     
    /**
     * @var float $longitude
     *
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $longitude;

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set latitude
     *
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }
}
