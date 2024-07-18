<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\City;
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
use App\Repository\PropertyConstructionRepository;
use App\Repository\PropertyDispositionRepository;
use App\Repository\PropertyRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use simplehtmldom\HtmlDocument;
use simplehtmldom\HtmlWeb;

final class BezrealitkyCrawler extends CrawlerBase implements CrawlerInterface
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
        $bezrealitkySource = $this->sourceRepository->findOneByCode(Source::SOURCE_BEZREALITKY);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        /** @var City $brno */
        $brno = $this->cityRepository->findOneByName('Brno');
        $dispositionMap = [
            'Garsoniéra' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1),
            '1+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_kk),
            '1+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_1),
            '2+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_kk),
            '2+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_1),
            '3+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_kk),
            '3+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_1),
            '4+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_kk),
            '4+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_1),
            '5+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_5_kk),
            '5+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_5_1),
            '6+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            '6+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            '7+kk' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            '7+1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            'Ostatní' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_other),
        ];
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
            $listDomNodes = (array) $listDom->find('article.product');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: '.$listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) {
                $detailPath = trim($node->find('div.product__body--left .product__title a', 0)->href);
                $detailUrl = $this->constructDetailUrl($detailPath);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if (null !== $existingAdvert) {
                    $currentPrice = null;

                    $priceNode = $node->find('div.product__body--left .product__value', 0);
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
                    $document = new HtmlWeb();
                    /** @var HtmlDocument|null $detailDom */
                    $detailDom = $document->load($detailUrl);
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load detail URL: '.$detailUrl.' '.$e->getMessage());
                    continue;
                }
                if (empty($detailDom)) {
                    $this->logger->debug('Could not load detail URL: '.$detailUrl);
                    continue;
                }
                $mainNode = $detailDom->find('main[role=main] article[role=article] > .main__container', 0);
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
                $titleNode = $mainNode->find('[data-element="detail-title"]', 0);
                if ($titleNode) {
                    $streetNode = $titleNode->find('h2', 0);
                    if ($streetNode) {
                        $street = $streetNode->innertext;
                    }
                }
                $mapNode = $mainNode->find('div#map', 0);
                if ($mapNode) {
                    $latitude = $mapNode->getAttribute('data-lat');
                    $longitude = $mapNode->getAttribute('data-lng');
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

                $cityDistrict = '';
                $itemNodes = (array) $mainNode->find('#detail-parameters div.row.param');
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
                            $property->setBalcony('ano' === mb_strtolower($itemValue));
                            break;
                        case 'terasa':
                            $property->setTerrace('ano' === mb_strtolower($itemValue));
                            break;
                        case 'městská část':
                            $cityDistrict = trim($itemValue);
                            break;
                    }
                }
                $images = [];
                $imageNodes = (array) $detailDom->find('main[role=main] article[role=article] .detail-gallery .detail-slick-item img');
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
                    $advert->setTitle(trim((string) $titleNode->find('h1', 0)->innertext));

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

    protected function constructListUrl(string $advertType, string $propertyType, int $page = 1): string
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
            'ad_type' => 'nabidka-'.$advertTypeParamMap[$advertType],
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
