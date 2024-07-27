<?php

namespace App\Entity\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class PushNotificationTokenInput
{
    #[Assert\NotBlank()]
    #[Assert\Type('string')]
    #[Groups(['write'])]
    private string $token;

    #[Assert\NotNull()]
    #[Assert\Type('bool')]
    #[Groups(['write'])]
    private bool $enabled;

    /**
     * @var array<mixed>|null
     */
    #[Assert\Type('array')]
    #[Groups(['write'])]
    #[ApiProperty(
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'cityCode' => [
                        'type' => 'string',
                        'example' => '582786',
                    ],
                    'districtCode' => [
                        'type' => 'string',
                        'example' => 'CZ0643',
                    ],
                    'advertType' => [
                        'type' => 'string',
                        'enum' => ['sale', 'rent'],
                    ],
                    'propertyType' => [
                        'type' => 'string',
                        'enum' => ['flat', 'house', 'land'],
                    ],
                    'propertySubtype' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'enum' => ['house', 'cottage', 'garrage', 'farm', 'property', 'field', 'woods', 'plantation', 'garden', 'other'],
                        ],
                        'example' => ['house', 'cottage', 'garrage', 'other'],
                    ],
                    'price' => [
                        'type' => 'object',
                        'properties' => [
                            'lte' => [
                                'type' => 'number',
                                'example' => 5000000,
                            ],
                            'gte' => [
                                'type' => 'number',
                                'example' => 0,
                            ],
                        ],
                    ],
                    'disposition' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                        'example' => ['1+kk', '1+1', 'other'],
                    ],
                    'cityDistrict' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ],
                        'example' => ['550973', '550990', 'unassigned'],
                    ],
                ],
            ],
        ],
    )]
    private ?array $filters;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array<mixed>|null
     */
    public function getFilters(): ?array
    {
        return $this->filters;
    }

    /**
     * @param array<mixed>|null $filters
     */
    public function setFilters(?array $filters): void
    {
        $this->filters = $filters;
    }
}
