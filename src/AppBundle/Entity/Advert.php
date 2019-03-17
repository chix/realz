<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="advert")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AdvertRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"sourceUrl"})
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
     * @Serializer\Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     *
     * @Serializer\Expose
     */
    private $title;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @Serializer\Expose
     */
    private $description;

    /**
     * @var int|null
     *
     * @ORM\Column(name="price", type="integer", nullable=true)
     *
     * @Serializer\Expose
     */
    private $price;

    /**
     * @var string|null $currency
     *
     * @ORM\Column(name="currency", type="string", length=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $currency;

    /**
     * @var Source|null
     *
     * @ORM\ManyToOne(targetEntity="Source", inversedBy="adverts")
     *
     * @Serializer\Expose
     */
    private $source;

    /**
     * @var string
     *
     * @ORM\Column(name="sourceUrl", type="string", length=1024, unique=true)
     *
     * @Serializer\Expose
     */
    private $sourceUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="externalUrl", type="string", length=1024)
     *
     * @Serializer\Expose
     */
    private $externalUrl;

    /**
     * @var Property|null
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="adverts")
     *
     * @Serializer\Expose
     */
    private $property;

    public function getId(): int
    {
        return $this->id;
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
