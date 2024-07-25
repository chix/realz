<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'location')]
#[ORM\Entity(repositoryClass: LocationRepository::class)]
class Location extends BaseEntity
{
    use Traits\Locatable;

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    private int $id;

    #[ORM\Column(name: 'street', type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['read'])]
    private ?string $street = null;

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'locations')]
    #[Groups(['read'])]
    private ?City $city = null;

    #[ORM\ManyToOne(targetEntity: CityDistrict::class, inversedBy: 'locations')]
    #[Groups(['read'])]
    private ?CityDistrict $cityDistrict = null;

    /**
     * @var ArrayCollection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'location')]
    private Collection $properties;

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
