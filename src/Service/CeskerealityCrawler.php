<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\Location;
use App\Entity\Property;
use App\Entity\PropertyConstruction;
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
use simplehtmldom\HtmlDocument;
use simplehtmldom\HtmlNode;
use simplehtmldom\HtmlWeb;

final class CeskerealityCrawler extends CrawlerBase implements CrawlerInterface
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
        $ceskerealitySource = $this->sourceRepository->findOneByCode(Source::SOURCE_CESKEREALITY);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        $brno = $this->cityRepository->findOneByName('Brno');
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
            $listDomNodes = (array)$listDom->find('#div_nemovitost_obal .div_nemovitost');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: ' . $listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) { /** @var HtmlNode $node */
                $detailUrlNode = $node->find('h2 a', 0);
                if ($detailUrlNode === false) {
                    continue;
                }
                $detailUrl = trim($detailUrlNode->getAttribute('href'));
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if ($existingAdvert !== null) {
                    $currentPrice = null;

                    $priceNode = $node->find('.nemovitost_info .cena', 0);
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
                    $document = new HtmlDocument();
                    $detailDom = $document->load(iconv('windows-1250', 'utf-8', (string)$this->curlGetContent($detailUrl)));
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl . ' ' . $e->getMessage());
                    continue;
                }
                if (empty($detailDom)) {
                    $this->logger->debug('Could not load detail URL: ' . $detailUrl);
                    continue;
                }
                $mainNode = $detailDom->find('#hlavni_obsah_nemovitost', 0);
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
                $streetNode = $mainNode->find('div.title h2', 0);
                if ($streetNode) {
                    $street = trim($streetNode->innertext);
                }
                $mapIframeNode = $mainNode->find('iframe[data-block-name=map-canvas]', 0);
                if ($mapIframeNode) {
                    $iframeUrlQuery = [];
                    $iframeSrc = $mapIframeNode->src;
                    $iframeUrlParts = parse_url($iframeSrc);
                    if ($iframeUrlParts !== false && isset($iframeUrlParts['query'])) {
                        parse_str($iframeUrlParts['query'], $iframeUrlQuery);
                    }
                    if (isset($iframeUrlQuery['q'])) {
                        $gps = explode(',', $iframeUrlQuery['q']);
                        $latitude = floatval($gps[0]);
                        $longitude = floatval($gps[1]);
                    }
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

                $title = trim(strip_tags((string)$mainNode->find('div.title h1', 0)->innertext));
                $description = null;
                $possibleDescriptionNodes = $mainNode->find('div.row h3');
                foreach ($possibleDescriptionNodes as $descriptionNode) {
                    if (trim($descriptionNode->innertext) !== 'Popis nemovitosti') {
                        continue;
                    }
                    $description = $this->normalizeHtmlString($descriptionNode->parent->innertext);
                }
                $this->loadPropertyFromFulltext($property, $title . ' ' . $description);

                $itemNodes = (array)$mainNode->find('div.row div.info-table div.item');
                foreach ($itemNodes as $itemNode) {
                    $itemHeading = '';
                    $itemValue = '';
                    $itemHeadingNode = $itemNode->find('div.name', 0);
                    $itemValueNode = $itemNode->find('div.value', 0);
                    if ($itemHeadingNode) {
                        $itemHeading = str_replace(':', '', trim(strip_tags($itemHeadingNode->innertext)));
                    }
                    if ($itemValueNode) {
                        $itemValue = trim(strip_tags($itemValueNode->innertext));
                    }
                    switch (mb_strtolower($itemHeading)) {
                        case 'typ konstrukce':
                        case 'konstrukce':
                            if (in_array(mb_strtolower($itemValue), ['panelová'])) {
                                $property->setConstruction($constructionPanel);
                            } elseif (in_array(mb_strtolower($itemValue), ['zděná'])) {
                                $property->setConstruction($constructionBrick);
                            }
                            break;
                        case 'užitná plocha':
                        case 'plocha užitná':
                        case 'plocha obytná':
                        case 'obytná plocha':
                            $area = str_replace([' ', 'm2'], ['', ''], $itemValue);
                            $property->setArea(intval($area));
                            break;
                        case 'vlastnictví':
                            $property->setOwnership($itemValue);
                            break;
                        case 'patro':
                            $property->setFloor(intval($itemValue));
                            break;
                    }
                }

                $images = [];
                $imageHrefNodes = (array)$mainNode->find('div#media-window div.media-slide a.cbox');
                foreach ($imageHrefNodes as $imageHrefNode) {
                    $imageSrc = $imageHrefNode->getAttribute('href');
                    $tmp = new \stdClass();
                    $tmp->image = $imageSrc;
                    $tmp->thumbnail = $imageSrc;
                    $images[] = $tmp;
                }
                $property->setImages($images);

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($ceskerealitySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($detailUrl);
                $advert->setProperty($property);
                $advert->setTitle($title);
                $advert->setDescription($description);
                $priceNode = $mainNode->find('span[itemprop=price]', 0);
                if ($priceNode) {
                    $price = intval(trim($priceNode->content));
                    $advert->setPrice($price);
                    $advert->setCurrency('CZK');
                }
                if ($existingAdvert) {
                    $advert->setPreviousPrice($existingAdvert->getPrice());
                }

                $this->assignCityDistrict($advert);

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
            PropertyType::TYPE_FLAT => 'byty',
            PropertyType::TYPE_HOUSE => 'rodinne-domy',
            PropertyType::TYPE_LAND => 'pozemky',
        ];
        $parameters = [
            'ad_type' => $advertTypeParamMap[$advertType],
            'property_type' => $propertyTypeParamMap[$propertyType],
            'city' => 'obec-brno',
            'order' => 'nejnovejsi',
        ];
        $url = $this->getSourceUrl().vsprintf('/%s/%s/%s/%s', array_values($parameters));
        if ($page > 1) {
            $url .= '?strana='.$page;
        }
        return $url;
    }
}
