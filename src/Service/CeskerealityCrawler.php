<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\City;
use App\Entity\Location;
use App\Entity\Property;
use App\Entity\PropertyConstruction;
use App\Entity\PropertyType;
use App\Entity\Source;
use App\Repository\AdvertRepository;
use App\Repository\AdvertTypeRepository;
use App\Repository\CityRepository;
use App\Repository\LocationRepository;
use App\Repository\PropertyConstructionRepository;
use App\Repository\PropertyDispositionRepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use simplehtmldom\HtmlDocument;
use simplehtmldom\HtmlNode;
use simplehtmldom\HtmlWeb;

final class CeskerealityCrawler extends CrawlerBase implements CrawlerInterface
{
    /** @var bool */
    protected $fullCrawl = false;

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
        protected AdvertTypeRepository $advertTypeRepository,
        protected PropertyConstructionRepository $propertyConstructionRepository,
        protected PropertyDispositionRepository $propertyDispositionRepository,
        protected PropertyTypeRepository $propertyTypeRepository,
        protected string $sourceUrl,
        private AdvertRepository $advertRepository,
        private CityRepository $cityRepository,
        private LocationRepository $locationRepository,
        private PropertyRepository $propertyRepository,
        private SourceRepository $sourceRepository
    ) {
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

    public function getNewAdverts(string $advertType, string $propertyType, ?int $cityCode = null): array
    {
        $ceskerealitySource = $this->sourceRepository->findOneByCode(Source::SOURCE_CESKEREALITY);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        /** @var City $brno */
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
            $listUrl = $this->constructListUrl($advertType, $propertyType, $page);
            try {
                $document = new HtmlWeb();
                /** @var HtmlDocument|null $listDom */
                $listDom = $document->load($listUrl);
            } catch (\Exception $e) {
                $this->logger->debug('Could not load list URL: '.$listUrl.' '.$e->getMessage());
                continue;
            }
            if (null === $listDom) {
                $this->logger->debug('Could not load list URL: '.$listUrl);
                continue;
            }
            $listDomNodes = (array) $listDom->find('article.i-estate');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: '.$listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) { /** @var HtmlNode $node */
                $detailUrlNode = $node->find('h2 a', 0);
                if (false === $detailUrlNode) {
                    continue;
                }
                $detailUrl = $this->constructDetailUrl(trim((string) $detailUrlNode->getAttribute('href')));
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if (null !== $existingAdvert) {
                    $currentPrice = null;

                    $priceNode = $node->find('.i-estate__footer-price-value', 0);
                    if ($priceNode) {
                        $priceRaw = trim(strip_tags($priceNode->innertext));
                        $currentPrice = intval(preg_replace('/\D/', '', $priceRaw));
                    }

                    $existingPrice = $existingAdvert->getPrice();
                    if ((int) $currentPrice === (int) $existingPrice) {
                        continue;
                    }
                }

                try {
                    $document = new HtmlDocument();
                    $detailDom = $document->load((string) $this->curlGetContent($detailUrl));
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load detail URL: '.$detailUrl.' '.$e->getMessage());
                    continue;
                }
                if (empty($detailDom)) {
                    $this->logger->debug('Could not load detail URL: '.$detailUrl);
                    continue;
                }
                $mainNode = $detailDom->find('main', 0);
                if (null === $mainNode) {
                    $this->logger->debug('No main node on URL: '.$detailUrl);
                    continue;
                }

                $property = $existingAdvert
                    ? $existingAdvert->getProperty()
                    : $this->propertyRepository->findProperty();
                if (null === $property) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $street = $latitude = $longitude = null;
                $streetNode = $mainNode->find('h1 span', 0);
                if ($streetNode) {
                    $street = trim($streetNode->innertext);
                }
                $distanceInputNode = $mainNode->find('input#driving_calculator_from', 0);
                if ($distanceInputNode) {
                    $latitude = $distanceInputNode->getAttribute('data-coord-lat') ? $distanceInputNode->getAttribute('data-coord-lat') : null;
                    $longitude = $distanceInputNode->getAttribute('data-coord-lat') ? $distanceInputNode->getAttribute('data-coord-lat') : null;
                }
                $location = $this->locationRepository->findLocation($brno, $street, $latitude, $longitude);
                if (null === $location) {
                    $location = new Location();
                    $location->setCity($brno);
                    $location->setStreet($street);
                    $location->setLatitude($latitude);
                    $location->setLongitude($longitude);
                }
                $property->setLocation($location);

                $title = trim(strip_tags((string) $mainNode->find('h1', 0)->innertext));
                $description = null;
                $descriptionNode = $mainNode->find('section.s-estate-content div.entry-content', 0);
                if ($descriptionNode) {
                    $description = $this->normalizeHtmlString($descriptionNode->innertext);
                }
                $this->loadPropertyFromFulltext($property, $title.' '.(string) $description);

                $itemNodes = (array) $mainNode->find('section.s-estate-info dl.g-info div.i-info');
                foreach ($itemNodes as $itemNode) {
                    $itemHeading = '';
                    $itemValue = '';
                    $itemHeadingNode = $itemNode->find('span.-info__title', 0);
                    $itemValueNode = $itemNode->find('span.i-info__value', 0);
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
                $imageHrefNodes = (array) $mainNode->find('section.s-estate-detail-intro div.gallery div.swiper-wrapper a');
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
                $priceNode = $mainNode->find('h2.s-estate-detail-intro__price', 0);
                if ($priceNode) {
                    $priceRaw = trim(strip_tags($priceNode->innertext));
                    $price = intval(preg_replace('/\D/', '', $priceRaw));
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

    protected function constructListUrl(string $advertType, string $propertyType, int $page = 1): string
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
        $url = $this->getSourceUrl().vsprintf('/%s/%s/%s/%s/', array_values($parameters));
        if ($page > 1) {
            $url .= '?strana='.$page;
        }

        return $url;
    }

    protected function constructDetailUrl(string $path): string
    {
        $url = $this->getSourceUrl().$path;

        return $url;
    }
}
