<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Property
 *
 * @ORM\Table(name="property")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 */
class Property extends BaseEntity
{

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
     * @var PropertyType
     *
     * @ORM\ManyToOne(targetEntity="PropertyType", inversedBy="properties")
     *
     * @Serializer\Expose
     */
    private $type;

    /**
     * @var PropertyDisposition
     *
     * @ORM\ManyToOne(targetEntity="PropertyDisposition", inversedBy="properties")
     *
     * @Serializer\Expose
     */
    private $disposition;

    /**
     * @var PropertyConstruction
     *
     * @ORM\ManyToOne(targetEntity="PropertyConstruction", inversedBy="properties")
     *
     * @Serializer\Expose
     */
    private $construction;

    /**
     * @var PropertyCondition
     *
     * @ORM\ManyToOne(targetEntity="PropertyCondition", inversedBy="properties")
     *
     * @Serializer\Expose
     */
    private $condition;

    /**
     * @var string
     *
     * @ORM\Column(name="ownership", type="string", length=255, nullable=true)
     *
     * @Serializer\Expose
     */
    private $ownership;

    /**
     * @var int
     *
     * @ORM\Column(name="floor", type="integer", nullable=true)
     *
     * @Serializer\Expose
     */
    private $floor;

    /**
     * @var int
     *
     * @ORM\Column(name="area", type="integer", nullable=true)
     *
     * @Serializer\Expose
     */
    private $area;

    /**
     * @var boolean
     *
     * @ORM\Column(name="balcony", type="boolean", options={"default":"0"})
     *
     * @Serializer\Expose
     */
    private $balcony = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="terrace", type="boolean", options={"default":"0"})
     *
     * @Serializer\Expose
     */
    private $terrace = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="elevator", type="boolean", options={"default":"0"})
     *
     * @Serializer\Expose
     */
    private $elevator = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="parking", type="boolean", options={"default":"0"})
     *
     * @Serializer\Expose
     */
    private $parking = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="loggia", type="boolean", options={"default":"0"})
     *
     * @Serializer\Expose
     */
    private $loggia = false;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="properties")
     *
     * @Serializer\Expose
     */
    private $location;

    /**
     * @var Advert[]
     *
     * @ORM\OneToMany(targetEntity="Advert", mappedBy="property")
     */
    private $adverts;

    /**
     * @var string[] $images
     *
     * @ORM\Column(name="images", type="json_array", nullable=true)
     *
     * @Serializer\Expose
     */
    private $images = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->adverts = new ArrayCollection();
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
     * Set ownership
     *
     * @param string $ownership
     *
     * @return Property
     */
    public function setOwnership($ownership)
    {
        $this->ownership = $ownership;

        return $this;
    }

    /**
     * Get ownership
     *
     * @return string
     */
    public function getOwnership()
    {
        return $this->ownership;
    }

    /**
     * Set floor
     *
     * @param integer $floor
     *
     * @return Property
     */
    public function setFloor($floor)
    {
        $this->floor = $floor;

        return $this;
    }

    /**
     * Get floor
     *
     * @return int
     */
    public function getFloor()
    {
        return $this->floor;
    }

    /**
     * Set area
     *
     * @param integer $area
     *
     * @return Property
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return int
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set balcony
     *
     * @param boolean $balcony
     *
     * @return Property
     */
    public function setBalcony($balcony)
    {
        $this->balcony = $balcony;

        return $this;
    }

    /**
     * Get balcony
     *
     * @return boolean
     */
    public function getBalcony()
    {
        return $this->balcony;
    }

    /**
     * Set terrace
     *
     * @param boolean $terrace
     *
     * @return Property
     */
    public function setTerrace($terrace)
    {
        $this->terrace = $terrace;

        return $this;
    }

    /**
     * Get terrace
     *
     * @return boolean
     */
    public function getTerrace()
    {
        return $this->terrace;
    }

    /**
     * Set elevator
     *
     * @param boolean $elevator
     *
     * @return Property
     */
    public function setElevator($elevator)
    {
        $this->elevator = $elevator;

        return $this;
    }

    /**
     * Get elevator
     *
     * @return boolean
     */
    public function getElevator()
    {
        return $this->elevator;
    }

    /**
     * Set parking
     *
     * @param boolean $parking
     *
     * @return Property
     */
    public function setParking($parking)
    {
        $this->parking = $parking;

        return $this;
    }

    /**
     * Get parking
     *
     * @return boolean
     */
    public function getParking()
    {
        return $this->parking;
    }

    /**
     * Set loggia
     *
     * @param boolean $loggia
     *
     * @return Property
     */
    public function setLoggia($loggia)
    {
        $this->loggia = $loggia;

        return $this;
    }

    /**
     * Get loggia
     *
     * @return boolean
     */
    public function getLoggia()
    {
        return $this->loggia;
    }

    /**
     * Set type
     *
     * @param PropertyType $type
     *
     * @return Property
     */
    public function setType(PropertyType $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return PropertyType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set disposition
     *
     * @param PropertyDisposition $disposition
     *
     * @return Property
     */
    public function setDisposition(PropertyDisposition $disposition = null)
    {
        $this->disposition = $disposition;

        return $this;
    }

    /**
     * Get disposition
     *
     * @return PropertyDisposition
     */
    public function getDisposition()
    {
        return $this->disposition;
    }

    /**
     * Set construction
     *
     * @param PropertyConstruction $construction
     *
     * @return Property
     */
    public function setConstruction(PropertyConstruction $construction = null)
    {
        $this->construction = $construction;

        return $this;
    }

    /**
     * Get construction
     *
     * @return PropertyConstruction
     */
    public function getConstruction()
    {
        return $this->construction;
    }

    /**
     * Set condition
     *
     * @param PropertyCondition $condition
     *
     * @return Property
     */
    public function setCondition(PropertyCondition $condition = null)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * Get condition
     *
     * @return PropertyCondition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Set location
     *
     * @param Location $location
     *
     * @return Property
     */
    public function setLocation(Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Add advert
     *
     * @param Advert $advert
     *
     * @return Property
     */
    public function addAdvert(Advert $advert)
    {
        $this->adverts[] = $advert;

        return $this;
    }

    /**
     * Remove advert
     *
     * @param Advert $advert
     */
    public function removeAdvert(Advert $advert)
    {
        $this->adverts->removeElement($advert);
    }

    /**
     * Get adverts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdverts()
    {
        return $this->adverts;
    }

    /**
     * Set images
     *
     * @param array $images
     *
     * @return Property
     */
    public function setImages($images)
    {
        $this->images = $images;

        return $this;
    }

    /**
     * Get images
     *
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }
}
