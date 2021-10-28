<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\Location;
use App\Entity\Property;
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
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UlovdomovCrawler extends CrawlerBase implements CrawlerInterface
{
    /** @var HttpClientInterface */
    protected $restClient;

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
        HttpClientInterface $restClient,
        AdvertRepository $advertRepository,
        CityRepository $cityRepository,
        LocationRepository $locationRepository,
        PropertyRepository $propertyRepository,
        PropertyTypeRepository $propertyTypeRepository,
        SourceRepository $sourceRepository
    ) {
        $this->restClient = $restClient;
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
        if ($advertType !== AdvertType::TYPE_RENT || $propertyType !== PropertyType::TYPE_FLAT) {
            return [];
        }

        $ulovdomovSource = $this->sourceRepository->findOneByCode(Source::SOURCE_ULOVDOMOV);
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
            16 => PropertyDisposition::DISPOSITION_other,
        ];
        foreach ($dispositionMap as $key => $code) {
            $dispositionMap[$key] = $this->propertyDispositionRepository->findOneByCode($code);
        }

        $page = 1;
        $limit = 20;
        try {
            $response = $this->restClient->request('POST', $this->sourceUrl . '/find', [
                'json' => $this->constructListPayload($page, $limit),
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
            ]);
            $list = json_decode($response->getContent(true), true);
        } catch (\Exception $e) {
            $this->logger->debug('Could not load list URL: ' . $this->sourceUrl, $this->constructListPayload($page, $limit));
            exit;
        }
        if ($this->fullCrawl) {
            $pages = range($page, (int)ceil($list['count'] / $limit));
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            if ($page > 1) {
                try {
                    $response = $this->restClient->request('POST', $this->sourceUrl . '/find', [
                        'json' => $this->constructListPayload($page, $limit),
                        'headers' => [
                            'Accept: application/json',
                            'Content-Type: application/json',
                        ],
                    ]);
                    $list = json_decode($response->getContent(true), true);
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load list URL: ' . $this->sourceUrl, $this->constructListPayload($page, $limit));
                    continue;
                }
            }

            foreach ($list['offers'] as $ad) {
                $detailUrl = $this->constructDetailUrl($ad['id']);
                $existingAdvert = $this->advertRepository->findOneBySourceUrl($detailUrl, ['id' => 'DESC']);
                if ($existingAdvert) {
                    $currentPrice = null;
                    if (!empty($ad['price_rental'])) {
                        $currentPrice = $ad['price_rental'];
                    }
                    $existingPrice = $existingAdvert->getPrice();
                    if ($currentPrice === $existingPrice) {
                        continue;
                    }
                }

                $property = $existingAdvert
                    ? $existingAdvert->getProperty()
                    : $this->propertyRepository->findProperty();
                if ($property === null) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $street = $latitude = $longitude = null;
                if (!empty($ad['street'])) {
                    $street = $ad['street']['label'];
                }
                if (!empty($ad['lat']) && !empty($ad['lng'])) {
                    $latitude = $ad['lat'];
                    $longitude = $ad['lng'];
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

                if (isset($dispositionMap[$ad['disposition_id']])) {
                    $property->setDisposition($dispositionMap[$ad['disposition_id']]);
                } else {
                    $property->setDisposition($dispositionMap[16]);
                }

                if (!empty($ad['floor_level'])) {
                    $property->setFloor($ad['floor_level']);
                }
                if (!empty($ad['acreage'])) {
                    $property->setArea($ad['acreage']);
                }

                $images = [];
                if (!empty($ad['photos'])) {
                    foreach ($ad['photos'] as $image) {
                        $tmp = new \stdClass();
                        $tmp->image = $image['path'];
                        $tmp->thumbnail = $image['path'];
                        $images[] = $tmp;
                    }
                }
                $property->setImages($images);

                $advert = new Advert();
                $advert->setType($advertTypeMap[$advertType]);
                $advert->setSource($ulovdomovSource);
                $advert->setSourceUrl($detailUrl);
                $advert->setExternalUrl($ad['absolute_url']);
                $advert->setProperty($property);
                $titleParts = ['Pronájem'];
                if ($property->getDisposition() !== null) {
                    $titleParts[] = $property->getDisposition()->getName();
                }
                if (!empty($ad['village_part'])) {
                    $titleParts[] = $ad['village_part']['label'];
                }
                if (!empty($ad['street'])) {
                    $titleParts[] = $ad['street']['label'];
                }
                $advert->setTitle(implode(', ', $titleParts));
                if (!empty($ad['description'])) {
                    $advert->setDescription($ad['description']);
                }
                if (!empty($ad['price_rental'])) {
                    $advert->setPrice($ad['price_rental']);
                    $advert->setCurrency('CZK');
                }
                if ($existingAdvert) {
                    $advert->setPreviousPrice($existingAdvert->getPrice());
                }

                $this->assignCityDistrict($advert);

                $adverts[$ad['id']] = $advert;
            }
        }

        return $adverts;
    }

    /**
     * @return array<mixed>
     */
    protected function constructListPayload(int $page, int $limit): array
    {
        return [
            'offer_type_id' => '1',
            'limit' => $limit,
            'page' => $page,
            'query' => 'Brno',
            'sort_by' => 'date:desc',
            'bounds' => [
                'north_east' => [
                    'lat' => 49.29691939312055,
                    'lng' => 16.96426391601563,
                ],
                'south_west' => [
                    'lat' => 49.10803981507455,
                    'lng' => 16.191787719726566,
                ]
            ],
        ];
    }

    protected function constructDetailUrl(int $id): string
    {
        $url = $this->getSourceUrl() . '/offer/' . $id;

        return $url;
    }
}
