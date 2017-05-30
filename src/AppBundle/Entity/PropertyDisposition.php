<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * PropertyDisposition
 *
 * @ORM\Table(name="property_disposition")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyDispositionRepository")
 * @UniqueEntity({"code"})
 */
class PropertyDisposition extends BaseEntity
{
    const DISPOSITION_1 = '1';
    const DISPOSITION_1_kk = '1+kk';
    const DISPOSITION_1_1 = '1+1';
    const DISPOSITION_2_kk = '2+kk';
    const DISPOSITION_2_1 = '2+1';
    const DISPOSITION_3_kk = '3+kk';
    const DISPOSITION_3_1 = '3+1';
    const DISPOSITION_4_kk = '4+kk';
    const DISPOSITION_4_1 = '4+1';
    const DISPOSITION_5_kk = '5+kk';
    const DISPOSITION_5_1 = '5+1';
    const DISPOSITION_6 = '6+';
    const DISPOSITION_other = 'other';

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
     * @ORM\OneToMany(targetEntity="Property", mappedBy="disposition")
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
     * @return PropertyDisposition
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
     * @return PropertyDisposition
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
     * @return PropertyDisposition
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
