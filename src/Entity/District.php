<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="district")
 * @ORM\Entity(repositoryClass="App\Repository\DistrictRepository")
 *
 * @UniqueEntity({"code"})
 */
class District extends BaseEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"read"})
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
     * @var Region|null
     *
     * @ORM\ManyToOne(targetEntity="Region", inversedBy="districts")
     *
     * @Groups({"read"})
     */
    private $region;

    /**
     * @var ArrayCollection<int, City>
     *
     * @ORM\OneToMany(targetEntity="City", mappedBy="district")
     */
    private $cities;

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
