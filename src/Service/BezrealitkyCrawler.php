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
            'GARSONIERA' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1),
            'DISP_1_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_kk),
            'DISP_1_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_1),
            'DISP_2_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_kk),
            'DISP_2_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_1),
            'DISP_3_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_kk),
            'DISP_3_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_1),
            'DISP_4_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_kk),
            'DISP_4_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_1),
            'DISP_5_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_5_kk),
            'DISP_5_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_5_1),
            'DISP_6_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            'DISP_6_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            'DISP_7_KK' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            'DISP_7_1' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_6),
            'OSTATNI' => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_other),
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
            $listDomNodes = (array) $listDom->find('article.propertyCard');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: '.$listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) {
                $detailUrl = trim($node->find('h2 a', 0)->href);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if (null !== $existingAdvert) {
                    $currentPrice = null;

                    $priceNode = $node->find('div.propertyPrice span', 0);
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
                $mainNode = $detailDom->find('main', 0);
                if (null === $mainNode) {
                    $this->logger->debug('No main node on URL: '.$detailUrl);
                    continue;
                }
                $scriptNode = $detailDom->find('script#__NEXT_DATA__', 0);
                if (null === $scriptNode) {
                    $this->logger->debug('No script node on URL: '.$detailUrl);
                    continue;
                }
                $scriptData = json_decode($scriptNode->innertext, true);
                if (!$scriptData) {
                    $this->logger->debug('Could not parse script node data on URL: '.$detailUrl);
                    continue;
                }

                $property = $existingAdvert
                    ? $existingAdvert->getProperty()
                    : $this->propertyRepository->findProperty();
                if (null === $property) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $street = $scriptData['props']['pageProps']['origAdvert']['address'] ?? null;
                $latitude = $scriptData['props']['pageProps']['origAdvert']['gps']['lat'] ?? null;
                $longitude = $scriptData['props']['pageProps']['origAdvert']['gps']['lng'] ?? null;
                $location = $this->locationRepository->findLocation($brno, $street, $latitude ? strval($latitude) : null, $longitude ? strval($longitude) : null);
                if (null === $location) {
                    $location = new Location();
                    $location->setCity($brno);
                    $location->setStreet($street);
                    $location->setLatitude($latitude ? strval($latitude) : null);
                    $location->setLongitude($longitude ? strval($longitude) : null);
                }
                $property->setLocation($location);

                $cityDistrict = $scriptData['props']['pageProps']['origAdvert']['regionTree'][3]['name'] ?? '';
                $disposition = $scriptData['props']['pageProps']['origAdvert']['disposition'] ?? 'OSTATNI';
                if (isset($dispositionMap[$disposition])) {
                    $property->setDisposition($dispositionMap[$disposition]);
                }
                $property->setArea($scriptData['props']['pageProps']['origAdvert']['surface'] ?? null);
                $construction = $scriptData['props']['pageProps']['origAdvert']['construction'] ?? null;
                if ('PANEL' === $construction) {
                    $property->setConstruction($constructionPanel);
                } elseif ('BRICK' === $construction) {
                    $property->setConstruction($constructionBrick);
                }
                $property->setOwnership($scriptData['props']['pageProps']['origAdvert']['ownership'] ?? null);
                $property->setFloor($scriptData['props']['pageProps']['origAdvert']['etage'] ?? null);
                $property->setBalcony($scriptData['props']['pageProps']['origAdvert']['balcony'] ?? false);
                $property->setTerrace($scriptData['props']['pageProps']['origAdvert']['terrace'] ?? false);
                $property->setLoggia($scriptData['props']['pageProps']['origAdvert']['loggia'] ?? false);
                $property->setParking($scriptData['props']['pageProps']['origAdvert']['parging'] ?? false);
                $property->setElevator($scriptData['props']['pageProps']['origAdvert']['lift'] ?? false);

                $images = [];
                foreach ($scriptData['props']['pageProps']['origAdvert']['publicImages'] ?? [] as $image) {
                    if ($image['url'] ?? null) {
                        $tmp = new \stdClass();
                        $tmp->image = $image['url'];
                        $tmp->thumbnail = $tmp->image;
                        $images[] = $tmp;
                    }
                }
                $property->setImages($images);

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($bezrealitkySource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($detailUrl);
                $advert->setProperty($property);
                $advert->setTitle($scriptData['props']['pageProps']['origAdvert']['imageAltText'] ?? null);
                $advert->setPrice($scriptData['props']['pageProps']['origAdvert']['price'] ?? null);
                $advert->setCurrency('CZK');
                $advert->setDescription($scriptData['props']['pageProps']['origAdvert']['description'] ?? null);
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
            'disctrict' => 'okres-brno-mesto',
        ];
        $url = $this->getSourceUrl().vsprintf('/vypis/%s/%s/%s', array_values($parameters));
        if ($page > 1) {
            $url .= '?page='.$page;
        }

        return $url;
    }
}
