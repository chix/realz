<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'property')]
#[ORM\Entity(repositoryClass: PropertyRepository::class)]
class Property extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: PropertyType::class, inversedBy: 'properties')]
    #[Groups(['read'])]
    private ?PropertyType $type = null;

    #[ORM\ManyToOne(targetEntity: PropertyDisposition::class, inversedBy: 'properties')]
    #[Groups(['read'])]
    private ?PropertyDisposition $disposition = null;

    #[ORM\ManyToOne(targetEntity: PropertyConstruction::class, inversedBy: 'properties')]
    #[Groups(['read'])]
    private ?PropertyConstruction $construction = null;

    #[ORM\ManyToOne(targetEntity: PropertyCondition::class, inversedBy: 'properties')]
    #[Groups(['read'])]
    private ?PropertyCondition $condition = null;

    #[ORM\Column(name: 'ownership', type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['read'])]
    private ?string $ownership = null;

    #[ORM\Column(name: 'floor', type: Types::INTEGER, nullable: true)]
    #[Groups(['read'])]
    private ?int $floor = null;

    #[ORM\Column(name: 'area', type: Types::INTEGER, nullable: true)]
    #[Groups(['read'])]
    private ?int $area = null;

    #[ORM\Column(name: 'balcony', type: Types::BOOLEAN, options: ['deafult' => false])]
    #[Groups(['read'])]
    private bool $balcony = false;

    #[ORM\Column(name: 'terrace', type: Types::BOOLEAN, options: ['deafult' => false])]
    #[Groups(['read'])]
    private bool $terrace = false;

    #[ORM\Column(name: 'elevator', type: Types::BOOLEAN, options: ['deafult' => false])]
    #[Groups(['read'])]
    private bool $elevator = false;

    #[ORM\Column(name: 'parking', type: Types::BOOLEAN, options: ['deafult' => false])]
    #[Groups(['read'])]
    private bool $parking = false;

    #[ORM\Column(name: 'loggia', type: Types::BOOLEAN, options: ['deafult' => false])]
    #[Groups(['read'])]
    private bool $loggia = false;

    #[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'properties')]
    #[Groups(['read'])]
    private ?Location $location = null;

    /**
     * @var ArrayCollection<int, Advert>
     */
    #[ORM\OneToMany(targetEntity: Advert::class, mappedBy: 'property')]
    private Collection $adverts;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(name: 'images', type: Types::JSON, nullable: true)]
    #[Groups(['read'])]
    private ?array $images = [];

    public function __construct()
    {
        parent::__construct();

        $this->adverts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setOwnership(?string $ownership): self
    {
        $this->ownership = $ownership;

        return $this;
    }

    public function getOwnership(): ?string
    {
        return $this->ownership;
    }

    public function setFloor(?int $floor): self
    {
        $this->floor = $floor;

        return $this;
    }

    public function getFloor(): ?int
    {
        return $this->floor;
    }

    public function setArea(?int $area): self
    {
        $this->area = $area;

        return $this;
    }

    public function getArea(): ?int
    {
        return $this->area;
    }

    public function setBalcony(bool $balcony): self
    {
        $this->balcony = $balcony;

        return $this;
    }

    public function getBalcony(): bool
    {
        return $this->balcony;
    }

    public function setTerrace(bool $terrace): self
    {
        $this->terrace = $terrace;

        return $this;
    }

    public function getTerrace(): bool
    {
        return $this->terrace;
    }

    public function setElevator(bool $elevator): self
    {
        $this->elevator = $elevator;

        return $this;
    }

    public function getElevator(): bool
    {
        return $this->elevator;
    }

    public function setParking(bool $parking): self
    {
        $this->parking = $parking;

        return $this;
    }

    public function getParking(): bool
    {
        return $this->parking;
    }

    public function setLoggia(bool $loggia): self
    {
        $this->loggia = $loggia;

        return $this;
    }

    public function getLoggia(): bool
    {
        return $this->loggia;
    }

    public function setType(?PropertyType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?PropertyType
    {
        return $this->type;
    }

    public function setDisposition(?PropertyDisposition $disposition): self
    {
        $this->disposition = $disposition;

        return $this;
    }

    public function getDisposition(): ?PropertyDisposition
    {
        return $this->disposition;
    }

    public function setConstruction(?PropertyConstruction $construction = null): self
    {
        $this->construction = $construction;

        return $this;
    }

    public function getConstruction(): ?PropertyConstruction
    {
        return $this->construction;
    }

    public function setCondition(?PropertyCondition $condition): self
    {
        $this->condition = $condition;

        return $this;
    }

    public function getCondition(): ?PropertyCondition
    {
        return $this->condition;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function addAdvert(Advert $advert): self
    {
        $this->adverts[] = $advert;

        return $this;
    }

    public function removeAdvert(Advert $advert): void
    {
        $this->adverts->removeElement($advert);
    }

    /**
     * @return ArrayCollection<int, Advert>
     */
    public function getAdverts(): Collection
    {
        return $this->adverts;
    }

    /**
     * @param array<mixed> $images
     */
    public function setImages(?array $images): self
    {
        $this->images = $images;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getImages(): ?array
    {
        return $this->images;
    }
}
