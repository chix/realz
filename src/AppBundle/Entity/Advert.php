<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Advert
 *
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
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @Serializer\Expose
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\Column(name="price", type="integer", nullable=true)
     *
     * @Serializer\Expose
     */
    private $price;

    /**
     * @var string $currency
     *
     * @ORM\Column(name="currency", type="string", length=8, nullable=true)
     *
     * @Serializer\Expose
     */
    private $currency;

    /**
     * @var Source
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
     * @var Property
     *
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="adverts")
     *
     * @Serializer\Expose
     */
    private $property;

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
     * Set title
     *
     * @param string $title
     *
     * @return Advert
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Advert
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return Advert
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set sourceUrl
     *
     * @param string $sourceUrl
     *
     * @return Advert
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    /**
     * Get sourceUrl
     *
     * @return string
     */
    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }

    /**
     * Set currency
     *
     * @param string $currency
     * @return Advert
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set property
     *
     * @param Property $property
     *
     * @return Advert
     */
    public function setProperty(Property $property = null)
    {
        $this->property = $property;

        return $this;
    }

    /**
     * Get property
     *
     * @return Property
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set source
     *
     * @param Source $source
     *
     * @return Advert
     */
    public function setSource(Source $source = null)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set externalUrl
     *
     * @param string $externalUrl
     *
     * @return Advert
     */
    public function setExternalUrl($externalUrl)
    {
        $this->externalUrl = $externalUrl;

        return $this;
    }

    /**
     * Get externalUrl
     *
     * @return string
     */
    public function getExternalUrl()
    {
        return $this->externalUrl;
    }
}
