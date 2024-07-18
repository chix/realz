<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\AdvertRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'advert')]
#[ORM\Index(name: 'updated_at_idx', columns: ['updated_at'])]
#[ORM\Entity(repositoryClass: AdvertRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['read']],
    paginationItemsPerPage: 20,
)]
#[ApiFilter(SearchFilter::class, properties: ['type.code' => 'exact', 'property.location.city.code' => 'exact'])]
#[ApiFilter(ExistsFilter::class, properties: ['deletedAt'])]
#[ApiFilter(OrderFilter::class, properties: ['id' => 'DESC'])]
class Advert extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: AdvertType::class, inversedBy: 'adverts')]
    #[Groups(['read'])]
    private AdvertType $type;

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255)]
    #[Groups(['read'])]
    private string $title;

    #[ORM\Column(name: 'description', type: Types::TEXT, nullable: true)]
    #[Groups(['read'])]
    private ?string $description;

    #[ORM\Column(name: 'price', type: Types::INTEGER, nullable: true)]
    #[Groups(['read'])]
    private ?int $price;

    #[ORM\Column(name: 'previous_price', type: Types::INTEGER, nullable: true)]
    #[Groups(['read'])]
    private ?int $previousPrice;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 8, nullable: true)]
    #[Groups(['read'])]
    private ?string $currency;

    #[ORM\ManyToOne(targetEntity: Source::class, inversedBy: 'adverts')]
    #[Groups(['read'])]
    private ?Source $source;

    #[ORM\Column(name: 'sourceUrl', type: Types::STRING, length: 1024)]
    #[Groups(['read'])]
    private string $sourceUrl;

    #[ORM\Column(name: 'externalUrl', type: Types::STRING, length: 1024)]
    #[Groups(['read'])]
    private string $externalUrl;

    #[ORM\ManyToOne(targetEntity: Property::class, inversedBy: 'adverts')]
    #[Groups(['read'])]
    private ?Property $property;

    public function getId(): int
    {
        return $this->id;
    }

    public function setType(AdvertType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): AdvertType
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

    public function setPreviousPrice(?int $previousPrice): self
    {
        $this->previousPrice = $previousPrice;

        return $this;
    }

    public function getPreviousPrice(): ?int
    {
        return $this->previousPrice;
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
