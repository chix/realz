<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\CityDistrict;
use App\Entity\Property;
use App\Entity\PropertyConstruction;
use App\Entity\PropertyDisposition;
use App\Entity\PropertyType;
use App\Repository\AdvertTypeRepository;
use App\Repository\PropertyConstructionRepository;
use App\Repository\PropertyDispositionRepository;
use App\Repository\PropertyTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class CrawlerBase
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var LoggerInterface */
    protected $logger;

    /** @var AdvertTypeRepository */
    protected $advertTypeRepository;

    /** @var PropertyConstructionRepository */
    protected $propertyConstructionRepository;

    /** @var PropertyDispositionRepository */
    protected $propertyDispositionRepository;

    /** @var PropertyTypeRepository */
    protected $propertyTypeRepository;

    /** @var string */
    protected $sourceUrl;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        AdvertTypeRepository $advertTypeRepository,
        PropertyConstructionRepository $propertyConstructionRepository,
        PropertyDispositionRepository $propertyDispositionRepository,
        PropertyTypeRepository $propertyTypeRepository,
        string $sourceUrl
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->advertTypeRepository = $advertTypeRepository;
        $this->propertyConstructionRepository = $propertyConstructionRepository;
        $this->propertyDispositionRepository = $propertyDispositionRepository;
        $this->propertyTypeRepository = $propertyTypeRepository;
        $this->sourceUrl = $sourceUrl;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getIdentifier(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return PropertyType[]
     */
    protected function getPropertyTypeMap(): array
    {
        return [
            PropertyType::TYPE_FLAT => $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_FLAT),
            PropertyType::TYPE_HOUSE => $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_HOUSE),
            PropertyType::TYPE_LAND => $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_LAND),
        ];
    }

    /**
     * @return AdvertType[]
     */
    protected function getAdvertTypeMap(): array
    {
        return [
            AdvertType::TYPE_SALE => $this->advertTypeRepository->findOneByCode(AdvertType::TYPE_SALE),
            AdvertType::TYPE_RENT => $this->advertTypeRepository->findOneByCode(AdvertType::TYPE_RENT),
        ];
    }

    protected function assignCityDistrict(Advert $advert, string $cityDistrictString = ''): ?CityDistrict
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
     */
    private function findMatchingCityDistrict(array $cityDistricts, string $title, ?string $description = null): ?CityDistrict
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

    protected function loadPropertyFromFulltext(Property $property, string $fulltext): Property
    {
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
            $dispositionMap[$code] = $this->propertyDispositionRepository->findOneByCode($code);
        }

        $constructionMap = [
            PropertyConstruction::CONSTRUCTION_BRICK => ['cihla', 'cihlo', 'zděn'],
            PropertyConstruction::CONSTRUCTION_PANEL => ['panel'],
        ];
        foreach ($constructionMap as $constructionCode => $keywords) {
            foreach ($keywords as $keyword) {
                if (mb_stristr($fulltext, $keyword) !== false) {
                    $property->setConstruction($this->propertyConstructionRepository->findOneByCode($constructionCode));
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
        if (empty($floorMatches)) {
            preg_match("/patro[ ]*(\d+)/i", $fulltext, $floorMatches);
        }
        if (count($floorMatches) > 1) {
            $property->setFloor(intval($floorMatches[1]));
        } elseif (stristr($fulltext, 'suterén') || stristr($fulltext, 'přízem')) {
            $property->setFloor(0);
        }

        return $property;
    }

    protected function curlGetContent(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $content = curl_exec($ch);
        curl_close($ch);
        return is_string($content) ? $content : null;
    }

    protected function normalizeHtmlString(string $html): string
    {
        $withoutBRs = str_ireplace(['<br>', '<br />', '<br/>'], "\r\n", $html);
        $withoutTags = strip_tags($withoutBRs);
        return trim($withoutTags);
    }
}
