<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="advert_type")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AdvertTypeRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class AdvertType extends BaseEntity
{
    const TYPE_RENT = 'rent';
    const TYPE_SALE = 'sale';

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
     * @var ArrayCollection<Advert>
     *
     * @ORM\OneToMany(targetEntity="Advert", mappedBy="type")
     */
    private $adverts;

    public function __construct()
    {
        $this->adverts = new ArrayCollection();
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

    public function addAdvert(Advert $advert): self
    {
        $this->adverts[] = $advert;

        return $this;
    }

    public function removeAdvert(Advert $advert): void
    {
        $this->adverts->removeElement($advert);
    }

    public function getAdverts(): Collection
    {
        return $this->adverts;
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
