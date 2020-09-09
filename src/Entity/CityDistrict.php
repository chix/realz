<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="city_district")
 * @ORM\Entity(repositoryClass="App\Repository\CityDistrictRepository")
 *
 * @UniqueEntity({"code"})
 *
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"read"}},
 * )
 * @ApiFilter(SearchFilter::class, properties={"name": "partial", "city.code": "exact"})
 */
class CityDistrict extends BaseEntity
{
    const CODE_UNASSIGNED = 'unassigned';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     *
     * @Groups({"read"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=true)
     *
     * @Groups({"read"})
     */
    private $code;

    /**
     * @var City|null
     *
     * @ORM\ManyToOne(targetEntity="City", inversedBy="cityDistricts")
     */
    private $city;

    /**
     * @var ArrayCollection<int, Location>
     *
     * @ORM\OneToMany(targetEntity="Location", mappedBy="cityDistrict")
     */
    private $locations;

    /**
     * @var array<mixed>|null $queries
     *
     * @ORM\Column(name="queries", type="json_array", nullable=true)
     */
    private $queries;

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
