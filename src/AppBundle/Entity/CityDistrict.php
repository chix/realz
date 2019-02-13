<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * CityDistrict
 *
 * @ORM\Table(name="city_district")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CityDistrictRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class CityDistrict
{
    const CODE_UNASSIGNED = 'unassigned';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var City
     *
     * @ORM\ManyToOne(targetEntity="City", inversedBy="cityDistricts")
     */
    private $city;

    /**
     * @var Location[]
     *
     * @ORM\OneToMany(targetEntity="Location", mappedBy="cityDistrict")
     */
    private $locations;

    /**
     * @var string[] $queries
     *
     * @ORM\Column(name="queries", type="json_array", nullable=true)
     */
    private $queries;

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
     * Constructor
     */
    public function __construct()
    {
        $this->locations = new ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return CityDistrict
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
     * Set code
     *
     * @param string $code
     *
     * @return CityDistrict
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
     * @return CityDistrict
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
     * Set city
     *
     * @param City $city
     *
     * @return CityDistrict
     */
    public function setCity(City $city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return City
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set queries
     *
     * @param array $queries
     *
     * @return CityDistrict
     */
    public function setQueries($queries)
    {
        $this->queries = $queries;

        return $this;
    }

    /**
     * Get queries
     *
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }
}
