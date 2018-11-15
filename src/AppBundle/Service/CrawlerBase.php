<?php

namespace AppBundle\Service;

use AppBundle\Entity\Advert;
use AppBundle\Entity\CityDistrict;
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

    /**
     * @param Advert $advert
     * @param string $cityDistrictString
     * @return CityDistrict|null
     */
    protected function assignCityDistrict(Advert $advert, $cityDistrictString = '')
    {
        $property = $advert->getProperty();
        if ($property === null) {
            return null;
        }
        $location = $property->getLocation();
        if ($location === null) {
            return null;
        }
        $city = $location->getCity();
        if ($city === null) {
            return null;
        }
        $cityDistricts = $city->getCityDistricts();
        if (empty($cityDistricts)) {
            return null;
        }
        $cityDistrict = $this->findMatchingCityDistrict(
            $cityDistricts,
            $cityDistrictString . ' ' . $advert->getTitle() . ' ' . $location->getStreet(),
            $advert->getDescription()
        );
        if ($cityDistrict !== null) {
            $location->setCityDistrict($cityDistrict);
            $this->entityManager->persist($location);
        }
        return $cityDistrict;
    }

    /**
     * @param CityDistrict[] $cityDistricts
     * @param string $title
     * @param string|null $description
     * @return CityDistrict|null
     */
    private function findMatchingCityDistrict($cityDistricts, $title, $description = null)
    {
        // search title
        foreach ($cityDistricts as $cityDistrict) {
            $queries = $cityDistrict->getQueries();
            if (empty($queries)) {
                continue;
            }
            foreach ($queries as $query) {
                if (preg_match("/{$query}/i", $title) === 1) {
                    return $cityDistrict;
                }
            }
        }
        
        // search description
        if ($description !== null) {
            foreach ($cityDistricts as $cityDistrict) {
                $queries = $cityDistrict->getQueries();
                if (empty($queries)) {
                    continue;
                }
                foreach ($queries as $query) {
                    if (preg_match("/{$query}/i", $description) === 1) {
                        return $cityDistrict;
                    }
                }
            }
        }

        return null;
    }
}
