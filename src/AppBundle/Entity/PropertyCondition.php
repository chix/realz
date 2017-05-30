<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * PropertyCondition
 *
 * @ORM\Table(name="property_condition")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyConditionRepository")
 * @UniqueEntity({"code"})
 */
class PropertyCondition extends BaseEntity
{
    const CONDITION_DEVELOPMENT = 'development';
    const CONDITION_NEW = 'new';
    const CONDITION_GOOD = 'good';
    const CONDITION_POOR = 'poor';
    const CONDITION_RENOVATED = 'renovated';
    const CONDITION_UNDER_CONSTRUCTION = 'under_construction';
    const CONDITION_DEMOLITION = 'demolition';
 
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
     * @ORM\OneToMany(targetEntity="Property", mappedBy="condition")
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
     * @return PropertyCondition
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
     * @return PropertyCondition
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
     * @return PropertyCondition
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
