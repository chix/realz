<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * City
 *
 * @ORM\Table(name="city")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CityRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class City extends BaseEntity
{
    use Traits\Locatable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Serializer\Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Serializer\Expose
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     *
     * @Serializer\Expose
     */
    private $code;

    /**
     * @var District
     *
     * @ORM\ManyToOne(targetEntity="District", inversedBy="cities")
     *
     * @Serializer\Expose
     */
    private $district;

    /**
     * @var CityDistrict[]
     *
     * @ORM\OneToMany(targetEntity="CityDistrict", mappedBy="city")
     */
    private $cityDistricts;

    /**
     * @var Location[]
     *
     * @ORM\OneToMany(targetEntity="Location", mappedBy="city")
     */
    private $locations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->cityDistricts = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return City
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set district
     *
     * @param District $district
     *
     * @return City
     */
    public function setDistrict(District $district = null)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district
     *
     * @return District
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return City
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Add location
     *
     * @param Location $location
     *
     * @return City
     */
    public function addLocation(Location $location)
    {
        $this->locations[] = $location;

        return $this;
    }

    /**
     * Remove location
     *
     * @param Location $location
     */
    public function removeLocation(Location $location)
    {
        $this->locations->removeElement($location);
    }

    /**
     * Get locations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLocations()
    {
        return $this->locations;
    }


    /**
     * Add cityDistrict
     *
     * @param CityDistrict $cityDistrict
     *
     * @return City
     */
    public function addCityDistrict(CityDistrict $cityDistrict)
    {
        $this->cityDistricts[] = $cityDistrict;

        return $this;
    }

    /**
     * Remove cityDistrict
     *
     * @param CityDistrict $cityDistrict
     */
    public function removeCityDistrict(CityDistrict $cityDistrict)
    {
        $this->cityDistricts->removeElement($cityDistrict);
    }

    /**
     * Get cityDistricts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCityDistricts()
    {
        return $this->cityDistricts;
    }

    /**
     * @return array
     */
    public function getCityDistrictCodes()
    {
        return array_map(
            function ($cityDistrict) {
                return $cityDistrict->getCode();
            },
            $this->getCityDistricts()->toArray()
        );
    }
}
