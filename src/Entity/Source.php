<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'source')]
#[ORM\Entity(repositoryClass: SourceRepository::class)]
#[UniqueEntity('code')]
class Source extends BaseEntity
{
    public const SOURCE_SREALITY = 'sreality';
    public const SOURCE_BAZOS = 'bazos';
    public const SOURCE_BEZREALITKY = 'bezrealitky';
    public const SOURCE_CESKEREALITY = 'ceskereality';
    public const SOURCE_ULOVDOMOV = 'ulovdomov';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    private int $id;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255)]
    #[Groups(['read'])]
    private string $name;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, unique: true)]
    #[Groups(['read'])]
    private string $code;

    /**
     * @var ArrayCollection<int, Advert>
     */
    #[ORM\OneToMany(targetEntity: Advert::class, mappedBy: 'source')]
    private Collection $adverts;

    public function __construct()
    {
        parent::__construct();

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

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
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
}
