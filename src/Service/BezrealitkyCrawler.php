<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\Location;
use App\Entity\Property;
use App\Entity\PropertyConstruction;
use App\Entity\PropertyDisposition;
use App\Entity\PropertyType;
use App\Entity\Source;
use App\Repository\AdvertRepository;
use App\Repository\AdvertTypeRepository;
use App\Repository\CityRepository;
use App\Repository\LocationRepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyConstructionRepository;
use App\Repository\PropertyDispositionRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use simplehtmldom\HtmlWeb;

final class BezrealitkyCrawler extends CrawlerBase implements CrawlerInterface
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
        PropertyTypeRepository $propertyTypeRepository,
        string $sourceUrl,
        AdvertRepository $advertRepository,
        CityRepository $cityRepository,
        LocationRepository $locationRepository,
        PropertyRepository $propertyRepository,
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
    public function getNewAdverts(string $advertType, string $propertyType, ?int $cityCode = null): array
    {
        $bezrealitkySource = $this->sourceRepository->findOneByCode(Source::SOURCE_BEZREALITKY);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        $brno = $this->cityRepository->findOneByName('Brno');
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
            $dispositionMap[$key] = $this->propertyDispositionRepository->findOneByCode($code);
        }
        $constructionBrick = $this->propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_BRICK);
        $constructionPanel = $this->propertyConstructionRepository->findOneByCode(PropertyConstruction::CONSTRUCTION_PANEL);

        $page = 1;
        if ($this->fullCrawl) {
            $pages = range($page, 10);
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            $listUrl = $this->constructListUrl($page, $advertType, $propertyType);
            try {
                $document = new HtmlWeb();
                $listDom = $document->load($listUrl);
            } catch (\Exception $e) {
                $this->logger->debug('Could not load list URL: ' . $listUrl . ' ' .$e->getMessage());
                continue;
            }
            if (empty($listDom)) {
                $this->logger->debug('Could not load list URL: ' . $listUrl);
                continue;
            }
            $listDomNodes = (array)$listDom->find('article.product');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: ' . $listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) {
                $detailPath = trim($node->find('div.product__body--left .product__title a', 0)->href);
                $detailUrl = $this->constructDetailUrl($detailPath);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if ($existingAdvert !== null) {
                    $currentPrice = null;

                    $priceNode = $node->find('div.product__body--left .product__value', 0);
                    if ($priceNode) {
                        $priceRaw = trim(strip_tags($priceNode->innertext));
                        $currentPrice = intval(preg_replace('/\D/', '', $priceRaw));
                    }

                    $existingPrice = $existingAdvert->getPrice();
                    if ((int)$currentPrice === (int)$existingPrice) {
                        continue;
                    }
                }

                try {
                    $document = new HtmlWeb();
                    $detailDom = $document->load($detailUrl);
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl . ' ' . $e->getMessage());
                    continue;
                }
                if (empty($detailDom)) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl);
                    continue;
                }
                $mainNode = $detailDom->find('main[role=main] article[role=article] > .main__container', 0);
                if ($mainNode === null) {
                    $this->logger->debug('No main node on URL: ' . $detailUrl);
                    continue;
                }

                $property = $existingAdvert
                    ? $existingAdvert->getProperty()
                    : $this->propertyRepository->findProperty();
                if ($property === null) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $street = $latitude = $longitude = null;
                $titleNode = $mainNode->find('[data-element="detail-title"]', 0);
                if ($titleNode) {
                    $streetNode = $titleNode->find('h2', 0);
                    if ($streetNode) {
                        $street = $streetNode->innertext;
                    }
                }
                $mapNode = $mainNode->find('div#map', 0);
                if ($mapNode) {
                    $latitude = floatval($mapNode->getAttribute('data-lat'));
                    $longitude = floatval($mapNode->getAttribute('data-lng'));
                }
                $location = $this->locationRepository->findLocation($brno, $street, $latitude, $longitude);
                if ($location === null) {
                    $location = new Location();
                    $location->setCity($brno);
                    $location->setStreet($street);
                    $location->setLatitude($latitude);
                    $location->setLongitude($longitude);
                }
                $property->setLocation($location);

                $cityDistrict = '';
                $itemNodes = (array)$mainNode->find('#detail-parameters div.row.param');
                foreach ($itemNodes as $itemNode) {
                    $itemHeading = '';
                    $itemValue = '';
                    $itemHeadingNode = $itemNode->find('.param-title', 0);
                    $itemValueNode = $itemNode->find('.param-value', 0);
                    if ($itemHeadingNode) {
                        $itemHeading = trim($itemHeadingNode->innertext);
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
                            } elseif (in_array(mb_strtolower($itemValue), ['cihla'])) {
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
                $imageNodes = (array)$detailDom->find('main[role=main] article[role=article] .detail-gallery .detail-slick-item img');
                foreach ($imageNodes as $imageNode) {
                    $tmp = new \stdClass();
                    $tmp->image = trim($imageNode->src);
                    $tmp->thumbnail = $tmp->image;
                    $images[] = $tmp;
                }
                $property->setImages($images);

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($bezrealitkySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($detailUrl);
                $advert->setProperty($property);
                if ($titleNode) {
                    $advert->setTitle(trim((string)$titleNode->find('h1', 0)->innertext));

                    $priceNode = $titleNode->find('.detail-price', 0);
                    if ($priceNode) {
                        $priceRaw = trim($priceNode->innertext);
                        $price = intval(preg_replace('/\D/', '', $priceRaw));
                        $advert->setPrice($price);
                        $advert->setCurrency('CZK');
                    }
                }
                $descriptionNode = $mainNode->find('#description p', 0);
                if ($descriptionNode) {
                    $advert->setDescription($this->normalizeHtmlString($descriptionNode->innertext));
                }
                if ($existingAdvert) {
                    $advert->setPreviousPrice($existingAdvert->getPrice());
                }

                $this->assignCityDistrict($advert, $cityDistrict);

                $adverts[$detailUrl] = $advert;
            }
        }

        return $adverts;
    }

    protected function constructListUrl(int $page = 1, string $advertType, string $propertyType): string
    {
        $advertTypeParamMap = [
            AdvertType::TYPE_SALE => 'prodej',
            AdvertType::TYPE_RENT => 'pronajem',
        ];
        $propertyTypeParamMap = [
            PropertyType::TYPE_FLAT => 'byt',
            PropertyType::TYPE_HOUSE => 'dum',
            PropertyType::TYPE_LAND => 'pozemek',
        ];
        $parameters = [
            'ad_type' => 'nabidka-' . $advertTypeParamMap[$advertType],
            'property_type' => $propertyTypeParamMap[$propertyType],
            'region' => 'jihomoravsky-kraj',
            'disctrict' => 'okres-brno-mesto',
        ];
        $url = $this->getSourceUrl().vsprintf('/vypis/%s/%s/%s/%s', array_values($parameters));
        if ($page > 1) {
            $url .= '?page='.$page;
        }
        return $url;
    }

    protected function constructDetailUrl(string $path): string
    {
        $url = $this->getSourceUrl().$path;

        return $url;
    }
}
