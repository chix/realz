<?php

namespace AppBundle\Service;

use AppBundle\Entity\Advert;
use AppBundle\Entity\City;
use AppBundle\Entity\Location;
use AppBundle\Entity\Property;
use AppBundle\Entity\PropertyConstruction;
use AppBundle\Entity\PropertyDisposition;
use AppBundle\Entity\PropertyType;
use AppBundle\Entity\Source;

class SrealityCrawler extends CrawlerBase implements CrawlerInterface
{

    /** @var array */
    protected $regionMap = [];

    /** @var boolean */
    protected $fullCrawl = false;

    /**
     * @inheritDoc
     */
    public function getNewAdverts()
    {
        $advertRepository = $this->entityManager->getRepository(Advert::class);
        $cityRepository = $this->entityManager->getRepository(City::class);
        $locationRepository = $this->entityManager->getRepository(Location::class);
        $propertyRepository = $this->entityManager->getRepository(Property::class);
        $propertyConstructionRepository = $this->entityManager->getRepository(PropertyConstruction::class);
        $propertyDispositionRepository = $this->entityManager->getRepository(PropertyDisposition::class);
        $propertyTypeRepository = $this->entityManager->getRepository(PropertyType::class);
        $sourceRepository = $this->entityManager->getRepository(Source::class);

        $srealitySource = $sourceRepository->findOneByCode(Source::SOURCE_SREALITY);
        $typeMap = [
            1 => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_FLAT),
            2 => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_HOUSE),
            3 => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_LAND),
        ];
        $brno = $cityRepository->findOneByName('Brno');
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
            $dispositionMap[$key] = $propertyDispositionRepository->findOneByCode($code);
        }
        $constructionBrick = $propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_BRICK);
        $constructionPanel = $propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_PANEL);

        $page = 1;
        $limit = 60;
        $listUrl = $this->constructListUrl($page, $limit);
        $json = json_decode(file_get_contents($listUrl), true);
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
            $listUrl = $this->constructListUrl($page, $limit);
            $list = json_decode(file_get_contents($listUrl), true);
            if (empty($list)) {
                $this->logger->debug('Could not load list URL: ' . $listUrl);
                continue;
            }

            foreach ($list['_embedded']['estates'] as $ad) {
                if ($ad['region_tip']) {
                    continue;
                }
                $detailUrl = $this->constructDetailUrl($ad['hash_id']);
                $existingAdvert = $advertRepository->findOneBySourceUrl($detailUrl);
                if ($existingAdvert !== null) {
                    continue;
                }

                $adDetail = json_decode(file_get_contents($detailUrl), true);
                if (empty($adDetail)) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl, [$ad['hash_id']]);
                    continue;
                }

                // TODO implement findProperty()
                $property = $propertyRepository->findProperty();
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
                    $location = $locationRepository->findLocation($brno, $street, $latitude, $longitude);
                    if ($location === null) {
                        $location = new Location();
                        $location->setCity($brno);
                        $location->setStreet($street);
                        $location->setLatitude($latitude);
                        $location->setLongitude($longitude);
                    }

                    $property = new Property();
                    $property->setType($typeMap[$adDetail['seo']['category_main_cb']]);
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
                                } else if (in_array(mb_strtolower($item['value']), ['cihlová'])) {
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
                            case 'stav objektu':
                                // TODO
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
                $advert->setSource($srealitySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($this->constructExternalUrl(
                    $ad['hash_id'],
                    'byt',
                    $property->getDisposition()->getCode(),
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

    /**
     * @param int $page
     * @param int $limit
     * @return string url
     */
    protected function constructListUrl($page, $limit)
    {
        $parameters = [
            'category_main_cb' => 1,
            'category_type_cb' => 1,
            'locality_region_id' => 14,
            'locality_district_id' => 72,
            'per_page' => $limit,
            'page' => $page,
        ];

        $url = $this->getSourceUrl().'?'.http_build_query($parameters);
        return $url;
    }

    /**
     * @param string $id
     * @return string url
     */
    protected function constructDetailUrl($id)
    {

        $url = $this->getSourceUrl().'/'.$id;

        return $url;
    }

    /**
     * 
     * @param string $id
     * @param string $type
     * @param string $subtype
     * @param string $locality
     * @return string url
     */
    protected function constructExternalUrl($id, $type, $subtype, $locality)
    {
        $urlParts = parse_url($this->getSourceUrl());
        $url = vsprintf('%s://%s/detail/prodej/%s/%s/%s/%s', [
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
