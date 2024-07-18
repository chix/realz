<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DistrictRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'district')]
#[ORM\Entity(repositoryClass: DistrictRepository::class)]
#[UniqueEntity('code')]
class District extends BaseEntity
{
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

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'districts')]
    #[Groups(['read'])]
    private ?Region $region;

    /**
     * @var ArrayCollection<int, City>
     */
    #[ORM\OneToMany(targetEntity: City::class, mappedBy: 'district')]
    private Collection $cities;

    public function __construct()
    {
        parent::__construct();

        $this->cities = new ArrayCollection();
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

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function addCity(City $city): self
    {
        $this->cities[] = $city;

        return $this;
    }

    public function removeCity(City $city): void
    {
        $this->cities->removeElement($city);
    }

    /**
     * @return ArrayCollection<int, City>
     */
    public function getCities(): Collection
    {
        return $this->cities;
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
