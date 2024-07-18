<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PropertyDispositionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'property_disposition')]
#[ORM\Entity(repositoryClass: PropertyDispositionRepository::class)]
#[UniqueEntity('code')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['read']],
    paginationEnabled: false,
)]
class PropertyDisposition extends BaseEntity
{
    public const DISPOSITION_1 = '1';
    public const DISPOSITION_1_kk = '1+kk';
    public const DISPOSITION_1_1 = '1+1';
    public const DISPOSITION_2_kk = '2+kk';
    public const DISPOSITION_2_1 = '2+1';
    public const DISPOSITION_3_kk = '3+kk';
    public const DISPOSITION_3_1 = '3+1';
    public const DISPOSITION_4_kk = '4+kk';
    public const DISPOSITION_4_1 = '4+1';
    public const DISPOSITION_5_kk = '5+kk';
    public const DISPOSITION_5_1 = '5+1';
    public const DISPOSITION_6 = '6+';
    public const DISPOSITION_other = 'other';

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
     * @var ArrayCollection<int, Property>
     */
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'disposition')]
    private Collection $properties;

    public function __construct()
    {
        parent::__construct();

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

    /**
     * @return ArrayCollection<int, Property>
     */
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
