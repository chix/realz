<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'region')]
#[ORM\Entity(repositoryClass: RegionRepository::class)]
#[UniqueEntity('code')]
class Region extends BaseEntity
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

    /**
     * @var ArrayCollection<int, District>
     */
    #[ORM\OneToMany(targetEntity: District::class, mappedBy: 'region')]
    private Collection $districts;

    public function __construct()
    {
        parent::__construct();

        $this->districts = new ArrayCollection();
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

    public function addDistrict(District $district): self
    {
        $this->districts[] = $district;

        return $this;
    }

    public function removeDistrict(District $district): void
    {
        $this->districts->removeElement($district);
    }

    /**
     * @return ArrayCollection<int, District>
     */
    public function getDistricts(): Collection
    {
        return $this->districts;
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
