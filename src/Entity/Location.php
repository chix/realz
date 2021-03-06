<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="location")
 * @ORM\Entity(repositoryClass="App\Repository\LocationRepository")
 */
class Location extends BaseEntity
{
    use Traits\Locatable;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"read"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="street", type="string", length=255, nullable=true)
     *
     * @Groups({"read"})
     */
    private $street;

    /**
     * @var City|null
     *
     * @ORM\ManyToOne(targetEntity="City", inversedBy="locations")
     *
     * @Groups({"read"})
     */
    private $city;

    /**
     * @var CityDistrict|null
     *
     * @ORM\ManyToOne(targetEntity="CityDistrict", inversedBy="locations")
     *
     * @Groups({"read"})
     */
    private $cityDistrict;

    /**
     * @var ArrayCollection<int, Property>
     *
     * @ORM\OneToMany(targetEntity="Property", mappedBy="location")
     */
    private $properties;

    public function __construct()
    {
        parent::__construct();

        $this->properties = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
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

    /**
     * @return ArrayCollection<int, Property>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function setCityDistrict(?CityDistrict $cityDistrict): self
    {
        $this->cityDistrict = $cityDistrict;

        return $this;
    }

    public function getCityDistrict(): ?CityDistrict
    {
        return $this->cityDistrict;
    }
}
