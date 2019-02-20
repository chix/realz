<?php

declare(strict_types=1);

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="property_disposition")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PropertyDispositionRepository")
 *
 * @Serializer\ExclusionPolicy("all")
 *
 * @UniqueEntity({"code"})
 */
class PropertyDisposition extends BaseEntity
{
    const DISPOSITION_1 = '1';
    const DISPOSITION_1_kk = '1+kk';
    const DISPOSITION_1_1 = '1+1';
    const DISPOSITION_2_kk = '2+kk';
    const DISPOSITION_2_1 = '2+1';
    const DISPOSITION_3_kk = '3+kk';
    const DISPOSITION_3_1 = '3+1';
    const DISPOSITION_4_kk = '4+kk';
    const DISPOSITION_4_1 = '4+1';
    const DISPOSITION_5_kk = '5+kk';
    const DISPOSITION_5_1 = '5+1';
    const DISPOSITION_6 = '6+';
    const DISPOSITION_other = 'other';

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
     * @var Property[]
     *
     * @ORM\OneToMany(targetEntity="Property", mappedBy="disposition")
     */
    private $properties;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    /**
     * @return string[]
     */
    public static function getCodes(): array
    {
        return [
            self::DISPOSITION_1,
            self::DISPOSITION_1_1,
            self::DISPOSITION_1_kk,
            self::DISPOSITION_2_1,
            self::DISPOSITION_2_kk,
            self::DISPOSITION_3_1,
            self::DISPOSITION_3_kk,
            self::DISPOSITION_4_1,
            self::DISPOSITION_4_kk,
            self::DISPOSITION_5_1,
            self::DISPOSITION_5_kk,
            self::DISPOSITION_6,
            self::DISPOSITION_other,
        ];
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

    public function addProperty(Property $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function removeProperty(Property $property): void
    {
        $this->properties->removeElement($property);
    }

    public function getProperties(): Collection
    {
        return $this->properties;
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
