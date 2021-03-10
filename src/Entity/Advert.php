<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="advert", indexes={@ORM\Index(name="updated_at_idx", columns={"updated_at"})})
 * @ORM\Entity(repositoryClass="App\Repository\AdvertRepository")
 *
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"read"}},
 *     attributes={"pagination_items_per_page"=20},
 * )
 * @ApiFilter(SearchFilter::class, properties={"type.code": "exact"})
 * @ApiFilter(ExistsFilter::class, properties={"deletedAt"})
 * @ApiFilter(OrderFilter::class, properties={"id": "DESC"})
 */
class Advert extends BaseEntity
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
     * @var AdvertType|null
     *
     * @ORM\ManyToOne(targetEntity="AdvertType", inversedBy="adverts")
     *
     * @Groups({"read"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Groups({"read"})
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @Groups({"read"})
     */
    private $description;

    /**
     * @var int|null
     *
     * @ORM\Column(name="price", type="integer", nullable=true)
     *
     * @Groups({"read"})
     */
    private $price;

    /**
     * @var string|null $currency
     *
     * @ORM\Column(name="currency", type="string", length=8, nullable=true)
     *
     * @Groups({"read"})
     */
    private $currency;

    /**
     * @var Source|null
     *
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="adverts")
     *
     * @Groups({"read"})
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceUrl", type="string", length=1024)
     *
     * @Groups({"read"})
     */
    private $sourceUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="externalUrl", type="string", length=1024)
     *
     * @Groups({"read"})
     */
    private $externalUrl;

    /**
     * @var Property|null
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="adverts")
     *
     * @Groups({"read"})
     */
    private $property;

    public function getId(): int
    {
        return $this->id;
    }

    public function setType(?AdvertType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?AdvertType
    {
        return $this->type;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setPrice(?int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setSourceUrl(string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setProperty(?Property $property): self
    {
        $this->property = $property;

        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setSource(?Source $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setExternalUrl(string $externalUrl): self
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    public function getExternalUrl(): string
    {
        return $this->externalUrl;
    }
}
