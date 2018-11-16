<?php

namespace AppBundle\Service;

use AppBundle\Entity\Advert;
use AppBundle\Entity\CityDistrict;
use AppBundle\Entity\Property;
use AppBundle\Entity\PropertyConstruction;
use AppBundle\Entity\PropertyDisposition;
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
        $cityDistricts = array_reverse($city->getCityDistricts()->toArray());
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
                if (mb_stristr($title, $query) !== false) {
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
                    if (mb_stristr($description, $query) !== false) {
                        return $cityDistrict;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param Property $property
     * @param string $fulltext
     */
    protected function loadPropertyFromFulltext(Property $property, $fulltext)
    {
        $propertyConstructionRepository = $this->entityManager->getRepository(PropertyConstruction::class);
        $propertyDispositionRepository = $this->entityManager->getRepository(PropertyDisposition::class);
        $dispositionKeywordsMap = [
            PropertyDisposition::DISPOSITION_1 => ['garson'],
            PropertyDisposition::DISPOSITION_1_kk => ['1kk', '1+kk'],
            PropertyDisposition::DISPOSITION_1_1 => ['1+1'],
            PropertyDisposition::DISPOSITION_2_kk => ['2kk', '2+kk'],
            PropertyDisposition::DISPOSITION_2_1 => ['2+1'],
            PropertyDisposition::DISPOSITION_3_kk => ['3kk', '3+kk'],
            PropertyDisposition::DISPOSITION_3_1 => ['3+1'],
            PropertyDisposition::DISPOSITION_4_kk => ['4kk', '4+kk'],
            PropertyDisposition::DISPOSITION_4_1 => ['4+1'],
            PropertyDisposition::DISPOSITION_5_kk => ['5kk', '5+kk'],
            PropertyDisposition::DISPOSITION_5_1 => ['5+1'],
            PropertyDisposition::DISPOSITION_6 => ['6kk', '6+kk', '6+1', '7kk', '7+kk', '7+1'],
            PropertyDisposition::DISPOSITION_other => ['atypick'],
        ];
        $dispositionMap = [];
        foreach ($dispositionKeywordsMap as $code => $keywords) {
            $dispositionMap[$code] = $propertyDispositionRepository->findOneByCode($code);
        }

        $constructionMap = [
            PropertyConstruction::CONSTRUCTION_BRICK => ['cihla', 'cihlo'],
            PropertyConstruction::CONSTRUCTION_PANEL => ['panel'],
        ];
        foreach ($constructionMap as $constructionCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stristr($fulltext, $keyword) !== false) {
                    $property->setConstruction($propertyConstructionRepository->findOneByCode($constructionCode));
                    break 2;
                }
            }
        }

        foreach ($dispositionKeywordsMap as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stristr($fulltext, $keyword) !== false) {
                    $property->setDisposition($dispositionMap[$code]);
                    break 2;
                }
            }
        }
        if (!$property->getDisposition()) {
            $property->setDisposition($dispositionMap[PropertyDisposition::DISPOSITION_other]);
        }

        if (mb_stristr($fulltext, 'balkón') !== false) {
            $property->setBalcony(true);
        }

        if (mb_stristr($fulltext, 'teras') !== false) {
            $property->setTerrace(true);
        }

        if (mb_stristr($fulltext, 'lodži') !== false) {
            $property->setLoggia(true);
        }

        if (mb_stristr($fulltext, 'výtah') !== false) {
            $property->setElevator(true);
        }

        if (mb_stristr($fulltext, 'parkov') !== false) {
            $property->setParking(true);
        }

        $areaMatches = [];
        preg_match("/(\d+)[\. ]*m²/i", $fulltext, $areaMatches);
        if (empty($areaMatches)) {
            preg_match("/(\d+)[\. ]*m2/i", $fulltext, $areaMatches);
        }
        if (empty($areaMatches)) {
            preg_match("/(\d+)[\. ]*m/i", $fulltext, $areaMatches);
        }
        if (count($areaMatches) > 1) {
            $property->setArea(intval($areaMatches[1]));
        }

        $floorMatches = [];
        preg_match("/(\d+)[\. ]*NP/i", $fulltext, $floorMatches);
        if (empty($floorMatches)) {
            preg_match("/(\d+)[\. ]*nadzem/i", $fulltext, $floorMatches);
        }
        if (empty($floorMatches)) {
            preg_match("/(\d+)[\. ]*podl/i", $fulltext, $floorMatches);
        }
        if (count($floorMatches) > 1) {
            $property->setFloor(intval($floorMatches[1]));
        } elseif (stristr($fulltext, 'suterén')) {
            $property->getFloor(0);
        }

        return $property;
    }

    /**
     * @param string $url
     * @return string|false
     */
    protected function curlGetContent($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * @param string $html
     * @return string
     */
    protected function normalizeHtmlString($html)
    {
        $withoutBRs = str_ireplace(['<br>', '<br />', '<br/>'], "\r\n", $html);
        $withoutTags = strip_tags($withoutBRs);
        return trim($withoutTags);
    }
}
