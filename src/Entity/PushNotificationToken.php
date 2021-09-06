<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Entity\Dto\PushNotificationTokenInput;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="push_notification_token", indexes={@ORM\Index(name="token_idx", columns={"token"})})
 * @ORM\Entity(repositoryClass="App\Repository\PushNotificationTokenRepository")
 *
 * @ApiResource(
 *     collectionOperations={
 *         "create_or_update"={
 *             "method"="POST",
 *             "input"=PushNotificationTokenInput::class,
 *             "read"=false,
 *         }
 *     },
 *     itemOperations={"get"},
 *     normalizationContext={"groups"={"read"}},
 *     denormalizationContext={"groups"={"write"}},
 * )
 */
class PushNotificationToken extends BaseEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Groups({"read", "write"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     */
    private $token;

    /**
     * For system activation/deactivation
     *
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean", options={"default" : 1})
     */
    private $active;

    /**
     * @var int
     *
     * @ORM\Column(name="error_count", type="integer", options={"default" : 0})
     */
    private $errorCount;

    /**
     * @var array<mixed>|null $lastResponse
     *
     * @ORM\Column(name="last_response", type="json_array", nullable=true)
     */
    private $lastResponse;

    /**
     * For user activation/deactivation
     *
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default" : 1})
     */
    private $enabled;

    /**
     * @var array<mixed>|null $filters
     *
     * @ORM\Column(name="filters", type="json_array", nullable=true)
     */
    private $filters;

    /**
     * @var ArrayCollection<int, Advert>
     * @ORM\ManyToMany(targetEntity="Advert")
     * @ORM\JoinTable(name="push_notification_tokens_adverts",
     *      joinColumns={@ORM\JoinColumn(name="push_notification_token_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="advert_id", referencedColumnName="id")}
     *  )
     */
    private $adverts;

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

    public function setEnabled(bool $enabled):  self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }
}
