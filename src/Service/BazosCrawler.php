<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\Location;
use App\Entity\Property;
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

final class BazosCrawler extends CrawlerBase implements CrawlerInterface
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
        $bazosSource = $this->sourceRepository->findOneByCode(Source::SOURCE_BAZOS);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        $brno = $this->cityRepository->findOneByName('Brno');

        $page = 1;
        $limit = 20;
        if ($this->fullCrawl) {
            $pages = range($page, 8);
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            $listUrl = $this->constructListUrl($page, $limit, $advertType, $propertyType);
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
            $listDomNodes = (array)$listDom->find('.inzeraty.inzeratyflex');
            if (empty($listDomNodes)) {
                $this->logger->debug('Empty nodes on URL: ' . $listUrl);
                continue;
            }

            foreach ($listDomNodes as $node) {
                $titleNode = $node->find('.inzeratynadpis h2.nadpis a', 0);
                if (!$titleNode) {
                    $this->logger->debug('No title node');
                    continue;
                }
                $title = trim($titleNode->innertext);
                foreach (['koupím', 'hledám', 'sháním', 'poptávám'] as $ignoredWord) {
                    if (mb_stristr($title, $ignoredWord) !== false) {
                        continue 2;
                    }
                }
                $priceNode = $node->find('.inzeratycena', 0);
                if (!$priceNode) {
                    $this->logger->debug('No price node');
                    continue;
                }
                $detailPath = trim($titleNode->href);
                $detailUrl = $this->constructDetailUrl($detailPath);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if ($existingAdvert !== null) {
                    $priceRaw = trim(strip_tags($priceNode->innertext));
                    $currentPrice = intval(preg_replace('/\D/', '', $priceRaw));

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
                $mainNode = $detailDom->find('div.maincontent', 0);

                $property = $existingAdvert
                    ? $existingAdvert->getProperty()
                    : $this->propertyRepository->findProperty();
                if ($property === null) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $description = $streetNode = $street = $latitude = $longitude = null;
                $descriptionNode = $mainNode->find('div.popisdetail', 0);
                if ($descriptionNode) {
                    $description = $this->normalizeHtmlString($descriptionNode->innertext);
                }
                $itemsNodes = (array)$mainNode->find('td.listadvlevo', 0)->find('table', 0)->find('tr');
                foreach ($itemsNodes as $itemNode) {
                    $itemHeadingNode = $itemNode->find('td', 0);
                    if (!$itemHeadingNode) {
                        continue;
                    }
                    $itemHeading = str_replace(':', '', trim(strip_tags($itemHeadingNode->innertext)));
                    switch (mb_strtolower($itemHeading)) {
                        case 'lokalita':
                            $streetNode = $itemNode->find('td', 2);
                            break;
                    }
                }
                
                if ($streetNode) {
                    $streetHrefNode = $streetNode->find('a', 0);
                    if ($streetHrefNode) {
                        $street = trim($streetHrefNode->innertext);
                        $mapUrlPathParts = [];
                        $mapHref = $streetHrefNode->href;
                        $mapUrlParts = parse_url($mapHref);
                        if ($mapUrlParts !== false && isset($mapUrlParts['path'])) {
                            $mapUrlPathParts = explode('/', $mapUrlParts['path']);
                        }
                        if (isset($mapUrlPathParts[3])) {
                            $gps = explode(',', $mapUrlPathParts[3]);
                            $latitude = floatval($gps[0]);
                            $longitude = floatval($gps[1]);
                        }
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

                $images = [];
                $thumbnailNodes = (array)$mainNode->find('div.fliobal div.flinavigace img');
                $imageNodes = (array)$mainNode->find('div.fliobal img.carousel-cell-image');
                for ($i = 0; $i < min([count($thumbnailNodes), count($imageNodes)]); $i++) {
                    $imageNode = $imageNodes[$i];
                    $thumbnailNode = $thumbnailNodes[$i];
                    $image = trim($imageNode->{'data-flickity-lazyload'});
                    $thumbnail = trim($thumbnailNode->src);
                    if (empty($image) || empty($thumbnail)) {
                        continue;
                    }
                    $tmp = new \stdClass();
                    $tmp->image = $image;
                    $tmp->thumbnail = $thumbnail;
                    $images[] = $tmp;
                }
                $property->setImages($images);
                if (empty($images)) {
                    continue;
                }

                $this->loadPropertyFromFulltext($property, $title . ' ' . $description);

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($bazosSource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($detailUrl);
                $advert->setProperty($property);
                $advert->setTitle($title);
                if ($description) {
                    $advert->setDescription($description);
                }
                if ($priceNode) {
                    $priceRaw = trim(strip_tags($priceNode->innertext));
                    $price = intval(preg_replace('/\D/', '', $priceRaw));
                    if ($price > 0) {
                        $advert->setPrice($price);
                    }
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

    protected function constructListUrl(int $page = 1, int $limit = 20, string $advertType, string $propertyType): string
    {
        $advertTypeParamMap = [
            AdvertType::TYPE_SALE => 'prodam',
            AdvertType::TYPE_RENT => 'pronajmu',
        ];
        $propertyTypeParamMap = [
            PropertyType::TYPE_FLAT => 'byt',
            PropertyType::TYPE_HOUSE => 'dum',
            PropertyType::TYPE_LAND => 'pozemek',
        ];
        $parameters = [
            'ad_type' => $advertTypeParamMap[$advertType],
            'property_type' => $propertyTypeParamMap[$propertyType],
            'zipCode' => '60200',
            'diameter' => '10',
        ];
        $url = $this->getSourceUrl().vsprintf('/%s/%s/?hlokalita=%s&humkreis=%s', array_values($parameters));
        if ($page > 1) {
            $url = str_replace('/?', sprintf('/%d/?', ($page - 1) * $limit), $url);
        }
        return $url;
    }

    protected function constructDetailUrl(string $path): string
    {
        $url = $this->getSourceUrl().$path;

        return $url;
    }
}
