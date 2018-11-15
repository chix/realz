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
use Sunra\PhpSimple\HtmlDomParser;

class BezrealitkyCrawler extends CrawlerBase implements CrawlerInterface
{

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

        $bezrealitkySource = $sourceRepository->findOneByCode(Source::SOURCE_BEZREALITKY);
        $typeMap = [
            'byt' => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_FLAT),
            'dum' => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_HOUSE),
            'pozemek' => $propertyTypeRepository->findOneByCode(PropertyType::TYPE_LAND),
        ];
        $flatType = 'byt';
        $brno = $cityRepository->findOneByName('Brno');
        $dispositionMap = [
            'Garsoniéra' => PropertyDisposition::DISPOSITION_1,
            '1+kk' => PropertyDisposition::DISPOSITION_1_kk,
            '1+1' => PropertyDisposition::DISPOSITION_1_1,
            '2+kk' => PropertyDisposition::DISPOSITION_2_kk,
            '2+1' => PropertyDisposition::DISPOSITION_2_1,
            '3+kk' => PropertyDisposition::DISPOSITION_3_kk,
            '3+1' => PropertyDisposition::DISPOSITION_3_1,
            '4+kk' => PropertyDisposition::DISPOSITION_4_kk,
            '4+1' => PropertyDisposition::DISPOSITION_4_1,
            '5+kk' => PropertyDisposition::DISPOSITION_5_kk,
            '5+1' => PropertyDisposition::DISPOSITION_5_1,
            '6+kk' => PropertyDisposition::DISPOSITION_6,
            '6+1' => PropertyDisposition::DISPOSITION_6,
            '7+kk' => PropertyDisposition::DISPOSITION_6,
            '7+1' => PropertyDisposition::DISPOSITION_6,
            'Ostatní' => PropertyDisposition::DISPOSITION_other,
        ];
        foreach ($dispositionMap as $key => $code) {
            $dispositionMap[$key] = $propertyDispositionRepository->findOneByCode($code);
        }
        $constructionBrick = $propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_BRICK);
        $constructionPanel = $propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_PANEL);

        $page = 1;
        if ($this->fullCrawl) {
            $pages = range($page, 10);
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            $listUrl = $this->constructListUrl($page, $flatType);
            try {
                $listDom = HtmlDomParser::file_get_html($listUrl);
            } catch (\Exception $e) {
                $this->logger->debug('Could not load list URL: ' . $listUrl . ' ' .$e->getMessage());
                continue;
            }
            if (!$listDom) {
                $this->logger->debug('Could not load list URL: ' . $listUrl);
                continue;
            }
            $listDomNodes = (array)$listDom->find('article.product');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: ' . $listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) {
                $detailPath = $url = trim($node->find('div.product__body p.product__ctas a.btn-primary', 0)->href);
                $detailUrl = $this->constructDetailUrl($detailPath);
                $existingAdvert = $advertRepository->findOneBySourceUrl($detailUrl);
                if ($existingAdvert !== null) {
                    continue;
                }

                try {
                    $detailDom = HtmlDomParser::file_get_html($detailUrl);
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl . ' ' . $e->getMessage());
                    continue;
                }
                if (!$detailDom) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl);
                    continue;
                }
                $mainNode = $detailDom->find('main[role=main] article[role=article]', 0);
                if ($mainNode === null) {
                    $this->logger->debug('No main node on URL: ' . $detailUrl);
                    continue;
                }

                // TODO implement findProperty()
                $property = $propertyRepository->findProperty();
                if ($property !== null) {

                } else {
                    $street = $latitude = $longitude = null;
                    $cityDistrict = '';
                    
                    $streetNode = $mainNode->find('div.heading p.heading__perex', 0);
                    if ($streetNode) {
                        $street = trim($streetNode->innertext);
                    }
                    $mapIframeNode = $mainNode->find('div#map iframe', 0);
                    if ($mapIframeNode) {
                        $iframeSrc = $mapIframeNode->src;
                        $iframeUrl = parse_url($iframeSrc);
                        $iframeUrlQuery = [];
                        parse_str($iframeUrl['query'], $iframeUrlQuery);
                        if (isset($iframeUrlQuery['q'])) {
                            $gps = explode(',', $iframeUrlQuery['q']);
                            $latitude = floatval($gps[0]);
                            $longitude = floatval($gps[1]);
                        }
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
                    $property->setType($typeMap[$flatType]);
                    $property->setLocation($location);
                    $itemNodes = (array)$mainNode->find('div.main__container div.b-desc table.table tbody tr');
                    foreach ($itemNodes as $itemNode) {
                        $itemHeading = '';
                        $itemValue = '';
                        $itemHeadingNode = $itemNode->find('th', 0);
                        $itemValueNode = $itemNode->find('td', 0);
                        if ($itemHeadingNode) {
                            $itemHeading = str_replace(':', '', trim($itemHeadingNode->innertext));
                        }
                        if ($itemValueNode) {
                            $itemValue = trim($itemValueNode->innertext);
                        }
                        switch (mb_strtolower($itemHeading)) {
                            case 'dispozice':
                                if (isset($dispositionMap[$itemValue])) {
                                    $property->setDisposition($dispositionMap[$itemValue]);
                                } else {
                                    $property->setDisposition($dispositionMap['Ostatní']);
                                }
                                break;
                            case 'typ budovy':
                                if (in_array(mb_strtolower($itemValue), ['panel'])) {
                                    $property->setConstruction($constructionPanel);
                                } else if (in_array(mb_strtolower($itemValue), ['cihla'])) {
                                    $property->setConstruction($constructionBrick);
                                }
                                break;
                            case 'plocha':
                                $area = str_replace([' ', 'm²'], ['', ''], $itemValue);
                                $property->setArea(intval($area));
                                break;
                            case 'typ vlastnictví':
                                $property->setOwnership($itemValue);
                                break;
                            case 'podlaží':
                                $property->setFloor(intval($itemValue));
                                break;
                            case 'balkón':
                                $property->setBalcony((mb_strtolower($itemValue) === 'ano'));
                                break;
                            case 'terasa':
                                $property->setTerrace((mb_strtolower($itemValue) === 'ano'));
                                break;
                            case 'městská část':
                                $cityDistrict = trim($itemValue);
                                break;
                        }
                    }
                    $images = [];
                    $imageHrefNodes = (array)$mainNode->find('div.main__container div.carousel div.carousel__list a');
                    foreach ($imageHrefNodes as $imageHrefNode) {
                        if (stristr($imageHrefNode->class, 'gallery-ad-img')) {
                            continue;
                        }
                        $imageNode = $imageHrefNode->find('img', 0);
                        if (!$imageNode) {
                            continue;
                        }
                        $tmp = new \stdClass();
                        $tmp->image = trim($imageNode->src);
                        $tmp->thumbnail = $tmp->image;
                        $images[] = $tmp;
                    }
                    $property->setImages($images);
                }

                $advert = new Advert();
                $advert->setSource($bezrealitkySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($detailUrl);
                $advert->setProperty($property);
                $advert->setTitle(trim($mainNode->find('div.heading h1.heading__title', 0)->innertext));
                $descriptionNode = $mainNode->find('div.main__container div.b-desc p.b-desc__info', 0);
                if ($descriptionNode) {
                    $advert->setDescription(strip_tags(trim($descriptionNode->innertext)));
                }
                $priceNode = $mainNode->find('div.heading p.heading__side', 0);
                if ($priceNode) {
                    $priceRaw = trim($priceNode->innertext);
                    $price = intval(str_replace(['.', ' ', 'Kč'], ['', '', ''], $priceRaw));
                    $advert->setPrice($price);
                    $advert->setCurrency('CZK');
                }

                $this->assignCityDistrict($advert, $cityDistrict);

                $adverts[$detailUrl] = $advert;
            }
        }

        return $adverts;
    }

    /**
     * @param int $page
     * @param string $propertyType
     * @return string url
     */
    protected function constructListUrl($page = 1, $propertyType = 'byt')
    {
        $parameters = [
            'ad_type' => 'nabidka-prodej',
            'property_type' => $propertyType,
            'region' => 'jihomoravsky-kraj',
            'disctrict' => 'okres-brno-mesto',
        ];
        $url = $this->getSourceUrl().vsprintf('/vypis/%s/%s/%s/%s', array_values($parameters));
        if ($page > 1) {
            $url .= '?page='.$page;
        }
        return $url;
    }

    /**
     * @param string $path
     * @return string url
     */
    protected function constructDetailUrl($path)
    {

        $url = $this->getSourceUrl().$path;

        return $url;
    }
}
