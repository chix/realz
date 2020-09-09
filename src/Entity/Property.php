<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="property")
 * @ORM\Entity(repositoryClass="App\Repository\PropertyRepository")
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
     * @Groups({"read"})
     */
    private $id;

    /**
     * @var PropertyType|null
     *
     * @ORM\ManyToOne(targetEntity="PropertyType", inversedBy="properties")
     *
     * @Groups({"read"})
     */
    private $type;

    /**
     * @var PropertyDisposition|null
     *
     * @ORM\ManyToOne(targetEntity="PropertyDisposition", inversedBy="properties")
     *
     * @Groups({"read"})
     */
    private $disposition;

    /**
     * @var PropertyConstruction|null
     *
     * @ORM\ManyToOne(targetEntity="PropertyConstruction", inversedBy="properties")
     *
     * @Groups({"read"})
     */
    private $construction;

    /**
     * @var PropertyCondition|null
     *
     * @ORM\ManyToOne(targetEntity="PropertyCondition", inversedBy="properties")
     *
     * @Groups({"read"})
     */
    private $condition;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ownership", type="string", length=255, nullable=true)
     *
     * @Groups({"read"})
     */
    private $ownership;

    /**
     * @var int|null
     *
     * @ORM\Column(name="floor", type="integer", nullable=true)
     *
     * @Groups({"read"})
     */
    private $floor;

    /**
     * @var int|null
     *
     * @ORM\Column(name="area", type="integer", nullable=true)
     *
     * @Groups({"read"})
     */
    private $area;

    /**
     * @var bool
     *
     * @ORM\Column(name="balcony", type="boolean", options={"default":"0"})
     *
     * @Groups({"read"})
     */
    private $balcony = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="terrace", type="boolean", options={"default":"0"})
     *
     * @Groups({"read"})
     */
    private $terrace = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="elevator", type="boolean", options={"default":"0"})
     *
     * @Groups({"read"})
     */
    private $elevator = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="parking", type="boolean", options={"default":"0"})
     *
     * @Groups({"read"})
     */
    private $parking = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="loggia", type="boolean", options={"default":"0"})
     *
     * @Groups({"read"})
     */
    private $loggia = false;

    /**
     * @var Location|null
     *
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="properties")
     *
     * @Groups({"read"})
     */
    private $location;

    /**
     * @var ArrayCollection<int, Advert>
     *
     * @ORM\OneToMany(targetEntity="Advert", mappedBy="property")
     */
    private $adverts;

    /**
     * @var array<mixed>|null $images
     *
     * @ORM\Column(name="images", type="json_array", nullable=true)
     *
     * @Groups({"read"})
     */
    private $images = [];

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
