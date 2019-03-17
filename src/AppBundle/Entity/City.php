<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="city")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CityRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class City extends BaseEntity
{
    use Traits\Locatable;

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
     * @var District|null
     *
     * @ORM\ManyToOne(targetEntity="District", inversedBy="cities")
     *
     * @Serializer\Expose
     */
    private $district;

    /**
     * @var ArrayCollection<CityDistrict>
     *
     * @ORM\OneToMany(targetEntity="CityDistrict", mappedBy="city")
     */
    private $cityDistricts;

    /**
     * @var ArrayCollection<Location>
     *
     * @ORM\OneToMany(targetEntity="Location", mappedBy="city")
     */
    private $locations;

    public function __construct()
    {
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
