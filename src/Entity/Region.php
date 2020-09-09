<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="region")
 * @ORM\Entity(repositoryClass="App\Repository\RegionRepository")
 *
 * @UniqueEntity({"code"})
 */
class Region extends BaseEntity
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
     * @var ArrayCollection<int, District>
     *
     * @ORM\OneToMany(targetEntity="District", mappedBy="region")
     */
    private $districts;

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
