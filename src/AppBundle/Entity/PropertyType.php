<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * PropertyType
 *
 * @ORM\Table(name="property_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyTypeRepository")
 * @UniqueEntity({"code"})
 */
class PropertyType extends BaseEntity
{
    const TYPE_FLAT = 'flat';
    const TYPE_HOUSE = 'house';
    const TYPE_LAND = 'land';

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
     */
    private $name;

    /**
     * @var string
     * 
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @var Property[]
     * 
     * @ORM\OneToMany(targetEntity="Property", mappedBy="type")
     */
    private $properties;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->properties = new ArrayCollection();
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
     * @return PropertyType
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
     * Add property
     *
     * @param Property $property
     *
     * @return PropertyType
     */
    public function addProperty(Property $property)
    {
        $this->properties[] = $property;

        return $this;
    }

    /**
     * Remove property
     *
     * @param Property $property
     */
    public function removeProperty(Property $property)
    {
        $this->properties->removeElement($property);
    }

    /**
     * Get properties
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return PropertyType
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
}
