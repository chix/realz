<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="city_district")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CityDistrictRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class CityDistrict
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
     * @var City|null
     *
     * @ORM\ManyToOne(targetEntity="City", inversedBy="cityDistricts")
     */
    private $city;

    /**
     * @var Location[]
     *
     * @ORM\OneToMany(targetEntity="Location", mappedBy="cityDistrict")
     */
    private $locations;

    /**
     * @var string[]|null $queries
     *
     * @ORM\Column(name="queries", type="json_array", nullable=true)
     */
    private $queries;

    public function __construct()
    {
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

    public function setQueries(?array $queries): self
    {
        $this->queries = $queries;

        return $this;
    }

    public function getQueries(): ?array
    {
        return $this->queries;
    }
}
