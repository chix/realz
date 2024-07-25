<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Entity\Dto\PushNotificationTokenInput;
use App\Repository\PushNotificationTokenRepository;
use App\State\PushNotificationTokenProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'push_notification_token')]
#[ORM\Index(name: 'token_idx', columns: ['token'])]
#[ORM\Entity(repositoryClass: PushNotificationTokenRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new Post(
            input: PushNotificationTokenInput::class,
            processor: PushNotificationTokenProcessor::class,
            read: false,
        ),
    ],
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
)]
class PushNotificationToken extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    #[Groups(['read'])]
    private int $id;

    #[ORM\Column(name: 'token', type: Types::STRING, length: 255)]
    private string $token;

    /**
     * For system activation/deactivation.
     */
    #[ORM\Column(name: 'active', type: Types::BOOLEAN, options: ['deafult' => true])]
    private bool $active = true;

    #[ORM\Column(name: 'error_count', type: Types::INTEGER, options: ['deafult' => 0])]
    private int $errorCount = 0;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(name: 'last_response', type: Types::JSON, nullable: true)]
    private ?array $lastResponse = null;

    /**
     * For user activation/deactivation.
     */
    #[ORM\Column(name: 'enabled', type: Types::BOOLEAN, options: ['deafult' => true])]
    private bool $enabled = true;

    /**
     * @var array<mixed>|null
     */
    #[ORM\Column(name: 'filters', type: Types::JSON, nullable: true)]
    private ?array $filters = null;

    /**
     * @var ArrayCollection<int, Advert>
     */
    #[ORM\ManyToMany(targetEntity: Advert::class)]
    #[ORM\JoinTable(name: 'push_notification_tokens_adverts', joinColumns: [])]
    #[ORM\JoinColumn(name: 'push_notification_token_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'advert_id', referencedColumnName: 'id')]
    private Collection $adverts;

    public function __construct()
    {
        parent::__construct();

        $this->adverts = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setErrorCount(int $errorCount): self
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getErrorCount(): int
    {
        return $this->errorCount;
    }

    /**
     * @param array<mixed> $lastResponse
     */
    public function setLastResponse(?array $lastResponse): self
    {
        $this->lastResponse = $lastResponse;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }

    public function addAdvert(Advert $advert): self
    {
        if (!$this->adverts->contains($advert)) {
            $this->adverts[] = $advert;
        }

        return $this;
    }

    public function removeAdvert(Advert $advert): void
    {
        $this->adverts->removeElement($advert);
    }

    /**
     * @return ArrayCollection<int, Advert>
     */
    public function getAdverts(): Collection
    {
        return $this->adverts;
    }

    /**
     * @param array<mixed> $filters
     */
    public function setFilters(?array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
}
