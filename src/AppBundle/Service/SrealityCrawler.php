<?php

declare(strict_types=1);

namespace AppBundle\Service;

use AppBundle\Entity\Advert;
use AppBundle\Entity\AdvertType;
use AppBundle\Entity\Location;
use AppBundle\Entity\Property;
use AppBundle\Entity\PropertyConstruction;
use AppBundle\Entity\PropertyDisposition;
use AppBundle\Entity\PropertyType;
use AppBundle\Entity\Source;
use AppBundle\Repository\AdvertRepository;
use AppBundle\Repository\AdvertTypeRepository;
use AppBundle\Repository\CityRepository;
use AppBundle\Repository\LocationRepository;
use AppBundle\Repository\PropertyRepository;
use AppBundle\Repository\PropertyConstructionRepository;
use AppBundle\Repository\PropertyDispositionRepository;
use AppBundle\Repository\PropertyTypeRepository;
use AppBundle\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class SrealityCrawler extends CrawlerBase implements CrawlerInterface
{
    /** @var AdvertRepository */
    protected $advertRepository;

    /** @var AdvertTypeRepository */
    protected $advertTypeRepository;

    /** @var CityRepository */
    protected $cityRepository;

    /** @var LocationRepository */
    protected $locationRepository;

    /** @var PropertyRepository */
    protected $propertyRepository;

    /** @var PropertyTypeRepository */
    protected $propertyTypeRepository;

    /** @var SourceRepository */
    protected $sourceRepository;

    /** @var bool */
    protected $fullCrawl = false;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        AdvertTypeRepository $advertTypeRepository,
        PropertyConstructionRepository $propertyConstructionRepository,
        PropertyDispositionRepository $propertyDispositionRepository,
        string $sourceUrl,
        AdvertRepository $advertRepository,
        CityRepository $cityRepository,
        LocationRepository $locationRepository,
        PropertyRepository $propertyRepository,
        PropertyTypeRepository $propertyTypeRepository,
        SourceRepository $sourceRepository
    ) {
        $this->advertRepository = $advertRepository;
        $this->cityRepository = $cityRepository;
        $this->locationRepository = $locationRepository;
        $this->propertyRepository = $propertyRepository;
        $this->sourceRepository = $sourceRepository;

        parent::__construct(
            $entityManager,
            $logger,
            $advertTypeRepository,
            $propertyConstructionRepository,
            $propertyDispositionRepository,
            $propertyTypeRepository,
            $sourceUrl
        );
    }

    /**
     * @inheritDoc
     */
    public function getNewAdverts(string $advertType, string $propertyType): array
    {
        $srealitySource = $this->sourceRepository->findOneByCode(Source::SOURCE_SREALITY);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        $brno = $this->cityRepository->findOneByName('Brno');
        $dispositionMap = [
            1 => PropertyDisposition::DISPOSITION_1,
            2 => PropertyDisposition::DISPOSITION_1_kk,
            3 => PropertyDisposition::DISPOSITION_1_1,
            4 => PropertyDisposition::DISPOSITION_2_kk,
            5 => PropertyDisposition::DISPOSITION_2_1,
            6 => PropertyDisposition::DISPOSITION_3_kk,
            7 => PropertyDisposition::DISPOSITION_3_1,
            8 => PropertyDisposition::DISPOSITION_4_kk,
            9 => PropertyDisposition::DISPOSITION_4_1,
            10 => PropertyDisposition::DISPOSITION_5_kk,
            11 => PropertyDisposition::DISPOSITION_5_1,
            12 => PropertyDisposition::DISPOSITION_6,
            16 => PropertyDisposition::DISPOSITION_other,
        ];
        foreach ($dispositionMap as $key => $code) {
            $dispositionMap[$key] = $this->propertyDispositionRepository->findOneByCode($code);
        }
        $constructionBrick = $this->propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_BRICK);
        $constructionPanel = $this->propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_PANEL);

        $page = 1;
        $limit = 60;
        $listUrl = $this->constructListUrl($page, $limit, $advertType, $propertyType);
        $json = json_decode((string)file_get_contents($listUrl), true);
        if (empty($json)) {
            $this->logger->debug('Could not load list URL: ' . $listUrl);
            exit;
        }
        if ($this->fullCrawl) {
            $pages = range($page, (int)ceil($json['result_size'] / $limit));
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            $listUrl = $this->constructListUrl($page, $limit, $advertType, $propertyType);
            $list = json_decode((string)file_get_contents($listUrl), true);
            if (empty($list)) {
                $this->logger->debug('Could not load list URL: ' . $listUrl);
                continue;
            }

            foreach ($list['_embedded']['estates'] as $ad) {
                if ($ad['region_tip']) {
                    continue;
                }
                $detailUrl = $this->constructDetailUrl($ad['hash_id']);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl);
                if ($existingAdvert !== null) {
                    continue;
                }

                $adDetail = json_decode((string)file_get_contents($detailUrl), true);
                if (empty($adDetail)) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl, [$ad['hash_id']]);
                    continue;
                }

                $property = $this->propertyRepository->findProperty();
                if ($property !== null) {
                } else {
                    $street = $latitude = $longitude = null;
                    if (!empty($adDetail['locality'])) {
                        $street = $adDetail['locality']['value'];
                    }
                    if (!empty($adDetail['map']) && isset($adDetail['map']['lat']) && isset($adDetail['map']['lon'])) {
                        $latitude = $adDetail['map']['lat'];
                        $longitude = $adDetail['map']['lon'];
                    }
                    $location = $this->locationRepository->findLocation($brno, $street, $latitude, $longitude);
                    if ($location === null) {
                        $location = new Location();
                        $location->setCity($brno);
                        $location->setStreet($street);
                        $location->setLatitude($latitude);
                        $location->setLongitude($longitude);
                    }

                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                    if (isset($dispositionMap[$adDetail['seo']['category_sub_cb']])) {
                        $property->setDisposition($dispositionMap[$adDetail['seo']['category_sub_cb']]);
                    } else {
                        $property->setDisposition($dispositionMap[16]);
                    }
                    $property->setLocation($location);
                    foreach ($adDetail['items'] as $item) {
                        switch (mb_strtolower($item['name'])) {
                            case 'stavba':
                                if (in_array(mb_strtolower($item['value']), ['panelová'])) {
                                    $property->setConstruction($constructionPanel);
                                } elseif (in_array(mb_strtolower($item['value']), ['cihlová'])) {
                                    $property->setConstruction($constructionBrick);
                                }
                                break;
                            case 'užitná plocha':
                                $property->setArea(intval($item['value']));
                                break;
                            case 'plocha podlahová':
                                if ($property->getArea() === null) {
                                    $property->setArea(intval($item['value']));
                                }
                                break;
                            case 'vlastnictví':
                                $property->setOwnership($item['value']);
                                break;
                            case 'podlaží':
                                $dotPosition = mb_stripos($item['value'], '.');
                                if ($dotPosition !== false) {
                                    $property->setFloor(intval(mb_substr($item['value'], 0, $dotPosition)));
                                }
                                break;
                            case 'balkón':
                                $property->setBalcony((boolean)$item['value']);
                                break;
                            case 'terasa':
                                $property->setTerrace((boolean)$item['value']);
                                break;
                            case 'lodžie':
                                $property->setLoggia((boolean)$item['value']);
                                break;
                            case 'výtah':
                                $property->setElevator((boolean)$item['value']);
                                break;
                            case 'parkování':
                                $property->setParking((boolean)$item['value']);
                                break;
                        }
                    }
                    $images = [];
                    if (!empty($adDetail['_embedded']['images'])) {
                        foreach ($adDetail['_embedded']['images'] as $image) {
                            if (empty($image['_links']) || empty($image['_links']['view']) || empty($image['_links']['gallery'])) {
                                continue;
                            }
                            $tmp = new \stdClass();
                            $tmp->image = $image['_links']['view']['href'];
                            $tmp->thumbnail = $image['_links']['gallery']['href'];
                            $images[] = $tmp;
                        }
                    }
                    $property->setImages($images);
                }

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($srealitySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($this->constructExternalUrl(
                    $ad['hash_id'],
                    'byt',
                    ($property->getDisposition() !== null) ? $property->getDisposition()->getCode() : $dispositionMap[16],
                    $adDetail['seo']['locality']
                ));
                $advert->setProperty($property);
                if (!empty($adDetail['name'])) {
                    $advert->setTitle($adDetail['name']['value']);
                }
                if (!empty($adDetail['text'])) {
                    $advert->setDescription($adDetail['text']['value']);
                }
                if (!empty($adDetail['price_czk'])) {
                    $advert->setPrice($adDetail['price_czk']['value_raw']);
                    $advert->setCurrency('CZK');
                }

                $this->assignCityDistrict($advert);

                $adverts[$ad['hash_id']] = $advert;
            }
        }

        return $adverts;
    }

    protected function constructListUrl(int $page, int $limit, string $advertType, string $propertyType): string
    {
        $advertTypeParamMap = [
            AdvertType::TYPE_SALE => 1,
            AdvertType::TYPE_RENT => 2,
        ];
        $propertyTypeParamMap = [
            PropertyType::TYPE_FLAT => 1,
            PropertyType::TYPE_HOUSE => 2,
            PropertyType::TYPE_LAND => 3,
        ];
        $parameters = [
            'category_main_cb' => $propertyTypeParamMap[$propertyType],
            'category_type_cb' => $advertTypeParamMap[$advertType],
            'locality_region_id' => 14,
            'locality_district_id' => 72,
            'per_page' => $limit,
            'page' => $page,
        ];

        $url = $this->getSourceUrl().'?'.http_build_query($parameters);
        return $url;
    }

    protected function constructDetailUrl(int $id): string
    {
        $url = $this->getSourceUrl().'/'.$id;

        return $url;
    }

    protected function constructExternalUrl(int $id, string $type, string $subtype, string $locality): string
    {
        $urlParts = parse_url($this->getSourceUrl());
        if ($urlParts === false || !isset($urlParts['scheme']) || !isset($urlParts['host'])) {
            return $this->getSourceUrl();
        }
        $url = vsprintf('%s://%s/detail/prodej/%s/%s/%s/%d', [
            $urlParts['scheme'],
            $urlParts['host'],
            $type,
            $subtype,
            $locality,
            $id,
        ]);

        return $url;
    }
}
