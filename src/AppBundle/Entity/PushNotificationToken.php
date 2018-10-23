<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Advert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * PushNotificationToken
 *
 * @ORM\Table(name="push_notification_token", indexes={@ORM\Index(name="token_idx", columns={"token"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PushNotificationTokenRepository")
 * 
 * @Serializer\ExclusionPolicy("all")
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
     * @Serializer\Expose
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     * 
     * @Assert\NotBlank()
     * 
     * @Serializer\Expose
     */
    private $token;

    /**
     * For system activation/deactivation
     *
     * @var boolean
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
     * @var string[] $lastResponse
     *
     * @ORM\Column(name="last_response", type="json_array", nullable=true)
     */
    private $lastResponse;

    /**
     * For user activation/deactivation
     * 
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", options={"default" : 1})
     */
    private $enabled;

    /**
     * @var string[] $filters
     *
     * @ORM\Column(name="filters", type="json_array", nullable=true)
     */
    private $filters;

    /**
     * @var Advert[]
     * @ORM\ManyToMany(targetEntity="Advert")
     * @ORM\JoinTable(name="push_notification_tokens_adverts",
     *      joinColumns={@ORM\JoinColumn(name="push_notification_token_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="advert_id", referencedColumnName="id")}
     *  )
     */
    private $adverts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->adverts = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return PushNotificationToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return PushNotificationToken
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set errorCount
     *
     * @param integer $errorCount
     *
     * @return PushNotificationToken
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;

        return $this;
    }

    /**
     * Get errorCount
     *
     * @return integer
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * Set lastResponse
     *
     * @param array $lastResponse
     *
     * @return PushNotificationToken
     */
    public function setLastResponse($lastResponse)
    {
        $this->lastResponse = $lastResponse;

        return $this;
    }

    /**
     * Get lastResponse
     *
     * @return array
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Add advert
     *
     * @param Advert $advert
     *
     * @return PushNotificationToken
     */
    public function addAdvert(Advert $advert)
    {
        $this->adverts[] = $advert;

        return $this;
    }

    /**
     * Remove advert
     *
     * @param Advert $advert
     */
    public function removeAdvert(Advert $advert)
    {
        $this->adverts->removeElement($advert);
    }

    /**
     * Get adverts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdverts()
    {
        return $this->adverts;
    }

    /**
     * Set filters
     *
     * @param array $filters
     *
     * @return PushNotificationToken
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * Get filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return PushNotificationToken
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
