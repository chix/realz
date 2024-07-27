<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\CityDistrict;
use App\Entity\Property;
use App\Entity\PropertyConstruction;
use App\Entity\PropertyDisposition;
use App\Entity\PropertySubtype;
use App\Entity\PropertyType;
use App\Repository\AdvertTypeRepository;
use App\Repository\PropertyConstructionRepository;
use App\Repository\PropertyDispositionRepository;
use App\Repository\PropertySubtypeRepository;
use App\Repository\PropertyTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class CrawlerBase
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected AdvertTypeRepository $advertTypeRepository,
        protected PropertyConstructionRepository $propertyConstructionRepository,
        protected PropertyDispositionRepository $propertyDispositionRepository,
        protected PropertyTypeRepository $propertyTypeRepository,
        protected PropertySubtypeRepository $propertySubtypeRepository,
        protected string $sourceUrl
    ) {
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
        /** @var PropertyType $flat */
        $flat = $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_FLAT);
        /** @var PropertyType $house */
        $house = $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_HOUSE);
        /** @var PropertyType $land */
        $land = $this->propertyTypeRepository->findOneByCode(PropertyType::TYPE_LAND);

        return [
            PropertyType::TYPE_FLAT => $flat,
            PropertyType::TYPE_HOUSE => $house,
            PropertyType::TYPE_LAND => $land,
        ];
    }

    /**
     * @return PropertySubtype[]
     */
    protected function getPropertySubtypeMap(): array
    {
        /** @var PropertySubtype $house */
        $house = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_HOUSE);
        /** @var PropertySubtype $cottage */
        $cottage = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_COTTAGE);
        /** @var PropertySubtype $garrage */
        $garrage = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_GARRAGE);
        /** @var PropertySubtype $farm */
        $farm = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_FARM);

        /** @var PropertySubtype $property */
        $property = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_PROPERTY);
        /** @var PropertySubtype $field */
        $field = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_FIELD);
        /** @var PropertySubtype $woods */
        $woods = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_WOODS);
        /** @var PropertySubtype $plantation */
        $plantation = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_PLANTATION);
        /** @var PropertySubtype $garden */
        $garden = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_GARDEN);

        /** @var PropertySubtype $other */
        $other = $this->propertySubtypeRepository->findOneByCode(PropertySubtype::SUBTYPE_OTHER);

        return [
            PropertySubtype::SUBTYPE_HOUSE => $house,
            PropertySubtype::SUBTYPE_COTTAGE => $cottage,
            PropertySubtype::SUBTYPE_GARRAGE => $garrage,
            PropertySubtype::SUBTYPE_FARM => $farm,
            PropertySubtype::SUBTYPE_PROPERTY => $property,
            PropertySubtype::SUBTYPE_FIELD => $field,
            PropertySubtype::SUBTYPE_WOODS => $woods,
            PropertySubtype::SUBTYPE_PLANTATION => $plantation,
            PropertySubtype::SUBTYPE_GARDEN => $garden,
            PropertySubtype::SUBTYPE_OTHER => $other,
        ];
    }

    /**
     * @return AdvertType[]
     */
    protected function getAdvertTypeMap(): array
    {
        /** @var AdvertType $sale */
        $sale = $this->advertTypeRepository->findOneByCode(AdvertType::TYPE_SALE);
        /** @var AdvertType $rent */
        $rent = $this->advertTypeRepository->findOneByCode(AdvertType::TYPE_RENT);

        return [
            AdvertType::TYPE_SALE => $sale,
            AdvertType::TYPE_RENT => $rent,
        ];
    }

    protected function assignCityDistrict(Advert $advert, string $cityDistrictString = ''): ?CityDistrict
    {
        $property = $advert->getProperty();
        if (null === $property) {
            return null;
        }
        $location = $property->getLocation();
        if (null === $location) {
            return null;
        }
        $city = $location->getCity();
        if (null === $city) {
            return null;
        }
        $cityDistricts = array_reverse($city->getCityDistricts()->toArray());
        if (empty($cityDistricts)) {
            return null;
        }
        $cityDistrict = $this->findMatchingCityDistrict(
            $cityDistricts,
            $cityDistrictString.' '.$advert->getTitle().' '.$location->getStreet(),
            $advert->getDescription()
        );
        if (null !== $cityDistrict) {
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
                if (false !== mb_stristr($title, $query)) {
                    return $cityDistrict;
                }
            }
        }

        // search description
        if (null !== $description) {
            foreach ($cityDistricts as $cityDistrict) {
                $queries = $cityDistrict->getQueries();
                if (empty($queries)) {
                    continue;
                }
                foreach ($queries as $query) {
                    if (false !== mb_stristr($description, $query)) {
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
                if (false !== mb_stristr($fulltext, $keyword)) {
                    $property->setConstruction($this->propertyConstructionRepository->findOneByCode($constructionCode));
                    break 2;
                }
            }
        }

        foreach ($dispositionKeywordsMap as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (false !== mb_stristr($fulltext, $keyword)) {
                    $property->setDisposition($dispositionMap[$code]);
                    break 2;
                }
            }
        }
        if (!$property->getDisposition()) {
            $property->setDisposition($dispositionMap[PropertyDisposition::DISPOSITION_other]);
        }

        if (false !== mb_stristr($fulltext, 'balkón')) {
            $property->setBalcony(true);
        }

        if (false !== mb_stristr($fulltext, 'teras')) {
            $property->setTerrace(true);
        }

        if (false !== mb_stristr($fulltext, 'lodži')) {
            $property->setLoggia(true);
        }

        if (false !== mb_stristr($fulltext, 'výtah')) {
            $property->setElevator(true);
        }

        if (false !== mb_stristr($fulltext, 'parkov')) {
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
