<?php

namespace App\Entity\Dto;

use ApiPlatform\Core\Annotation\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class PushNotificationTokenInput
{
    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Type("string")
     *
     * @Groups({"write"})
     */
    private $token;

    /**
     * @var bool
     *
     * @Assert\NotNull()
     * @Assert\Type("bool")
     *
     * @Groups({"write"})
     */
    private $enabled;

    /**
     * @var array<mixed>|null $filters
     *
     * @Assert\Type("array")
     *
     * @Groups({"write"})
     *
     * @ApiProperty(
     *     attributes={
     *         "openapi_context": {
     *             "type": "object",
     *             "properties": {
     *                 "582786": {
     *                     "type": "object",
     *                     "description": "Object key is a city code",
     *                     "properties": {
     *                         "advertType": {
     *                             "type": "string",
     *                             "enum": {"sale", "rent"}
     *                         },
     *                         "price": {
     *                             "type": "object",
     *                             "properties": {
     *                                 "lte": {
     *                                     "type": "number",
     *                                     "example": 5000000
     *                                 },
     *                                 "gte": {
     *                                     "type": "number",
     *                                     "example": 0
     *                                 }
     *                             }
     *                         },
     *                         "disposition": {
     *                             "type": "array",
     *                             "items": {
     *                                 "type": "string"
     *                             },
     *                             "example": {"1+kk", "1+1", "other"}
     *                         },
     *                         "cityDistrict": {
     *                             "type": "array",
     *                             "items": {
     *                                 "type": "string"
     *                             },
     *                             "example": {"550973","550990", "unassigned"}
     *                         }
     *                     }
     *                 }
     *             }
     *         }
     *     }
     * )
     */
    private $filters;

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
