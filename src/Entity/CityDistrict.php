<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CityDistrictRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'city_district')]
#[ORM\Entity(repositoryClass: CityDistrictRepository::class)]
#[UniqueEntity('code')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'city.code' => 'exact'])]
class CityDistrict extends BaseEntity
{
    public const CODE_UNASSIGNED = 'unassigned';

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

    #[ORM\ManyToOne(targetEntity: City::class, inversedBy: 'cityDistricts')]
    private ?City $city = null;

    /**
     * @var ArrayCollection<int, Location>
     */
    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'cityDistrict')]
    private Collection $locations;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(name: 'queries', type: Types::JSON, nullable: true)]
    private ?array $queries = null;

    public function __construct()
    {
        parent::__construct();

        $this->locations = new ArrayCollection();
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

    public function addLocation(Location $location): self
    {
        $this->locations[] = $location;

        return $this;
    }

    public function removeLocation(Location $location): void
    {
        $this->locations->removeElement($location);
    }

    /**
     * @return ArrayCollection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    /**
     * @param array<mixed> $queries
     */
    public function setQueries(?array $queries): self
    {
        $this->queries = $queries;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getQueries(): ?array
    {
        return $this->queries;
    }
}
