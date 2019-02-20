<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="property_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyTypeRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
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
     * @var Property[]
     *
     * @ORM\OneToMany(targetEntity="Property", mappedBy="type")
     */
    private $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addProperty(Property $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function removeProperty(Property $property): void
    {
        $this->properties->removeElement($property);
    }

    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
