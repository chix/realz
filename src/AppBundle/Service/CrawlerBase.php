<?php

namespace AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

abstract class CrawlerBase
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /** @var string */
    protected $sourceUrl;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Set sourceUrl
     *
     * @param string $sourceUrl
     *
     * @return string
     */
    public function setSourceUrl($sourceUrl)
    {
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    /**
     * Get sourceUrl
     *
     * @return string
     */
    public function getSourceUrl()
    {
        return $this->sourceUrl;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

}
