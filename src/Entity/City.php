<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'city')]
#[ORM\Entity(repositoryClass: CityRepository::class)]
#[UniqueEntity('code')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['read']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial'])]
class City extends BaseEntity
{
    use Traits\Locatable;

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

    #[ORM\ManyToOne(targetEntity: District::class, inversedBy: 'cities')]
    #[Groups(['read'])]
    private ?District $district = null;

    /**
     * @var ArrayCollection<int, CityDistrict>
     */
    #[ORM\OneToMany(targetEntity: CityDistrict::class, mappedBy: 'city')]
    private Collection $cityDistricts;

    /**
     * @var ArrayCollection<int, Location>
     */
    #[ORM\OneToMany(targetEntity: Location::class, mappedBy: 'city')]
    private Collection $locations;

    public function __construct()
    {
        parent::__construct();

        $this->locations = new ArrayCollection();
        $this->cityDistricts = new ArrayCollection();
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

    public function setDistrict(?District $district): self
    {
        $this->district = $district;

        return $this;
    }

    public function getDistrict(): ?District
    {
        return $this->district;
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

    public function addCityDistrict(CityDistrict $cityDistrict): self
    {
        $this->cityDistricts[] = $cityDistrict;

        return $this;
    }

    public function removeCityDistrict(CityDistrict $cityDistrict): void
    {
        $this->cityDistricts->removeElement($cityDistrict);
    }

    /**
     * @return ArrayCollection<int, CityDistrict>
     */
    public function getCityDistricts(): Collection
    {
        return $this->cityDistricts;
    }

    /**
     * @return string[]
     */
    public function getCityDistrictCodes(): array
    {
        return array_map(
            function ($cityDistrict) {
                return $cityDistrict->getCode();
            },
            $this->getCityDistricts()->toArray()
        );
    }
}
