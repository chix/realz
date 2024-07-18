<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\PropertyConditionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'property_condition')]
#[ORM\Entity(repositoryClass: PropertyConditionRepository::class)]
#[UniqueEntity('code')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['read']],
    paginationEnabled: false,
)]
class PropertyCondition extends BaseEntity
{
    public const CONDITION_DEVELOPMENT = 'development';
    public const CONDITION_NEW = 'new';
    public const CONDITION_GOOD = 'good';
    public const CONDITION_POOR = 'poor';
    public const CONDITION_RENOVATED = 'renovated';
    public const CONDITION_UNDER_CONSTRUCTION = 'under_construction';
    public const CONDITION_DEMOLITION = 'demolition';

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
    #[ORM\OneToMany(targetEntity: Property::class, mappedBy: 'condition')]
    private Collection $properties;

    public function __construct()
    {
        parent::__construct();

        $this->properties = new ArrayCollection();
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
