<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Advert;
use App\Entity\AdvertType;
use App\Entity\City;
use App\Entity\Location;
use App\Entity\Property;
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
use App\Repository\PropertySubtypeRepository;
use App\Repository\PropertyTypeRepository;
use App\Repository\SourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UlovdomovCrawler extends CrawlerBase implements CrawlerInterface
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
        protected PropertySubtypeRepository $propertySubtypeRepository,
        protected string $sourceUrl,
        private HttpClientInterface $restClient,
        private AdvertRepository $advertRepository,
        private CityRepository $cityRepository,
        private LocationRepository $locationRepository,
        private PropertyRepository $propertyRepository,
        private SourceRepository $sourceRepository
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
            $propertySubtypeRepository,
            $sourceUrl
        );
    }

    public function getNewAdverts(string $advertType, string $propertyType, ?string $propertySubtype, ?string $locationCode = null): array
    {
        if (AdvertType::TYPE_RENT !== $advertType || PropertyType::TYPE_FLAT !== $propertyType) {
            return [];
        }

        $ulovdomovSource = $this->sourceRepository->findOneByCode(Source::SOURCE_ULOVDOMOV);
        $advertTypeMap = $this->getAdvertTypeMap();
        $propertyTypeMap = $this->getPropertyTypeMap();
        /** @var City $brno */
        $brno = $this->cityRepository->findOneByName('Brno');
        $dispositionMap = [
            1 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1),
            2 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_kk),
            3 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_1_1),
            4 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_kk),
            5 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_2_1),
            6 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_kk),
            7 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_3_1),
            8 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_kk),
            9 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_4_1),
            16 => $this->propertyDispositionRepository->findOneByCode(PropertyDisposition::DISPOSITION_other),
        ];

        $page = 1;
        $limit = 20;
        try {
            $response = $this->restClient->request('POST', $this->sourceUrl.'/find', [
                'json' => $this->constructListPayload($page, $limit),
                'headers' => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ],
            ]);
            $list = json_decode($response->getContent(true), true);
        } catch (\Exception $e) {
            $this->logger->debug('Could not load list URL: '.$this->sourceUrl, $this->constructListPayload($page, $limit));
            exit;
        }
        if ($this->fullCrawl) {
            $pages = range($page, (int) ceil($list['count'] / $limit));
        } else {
            $pages = [$page];
        }

        $adverts = [];
        foreach ($pages as $page) {
            if ($page > 1) {
                try {
                    $response = $this->restClient->request('POST', $this->sourceUrl.'/find', [
                        'json' => $this->constructListPayload($page, $limit),
                        'headers' => [
                            'Accept: application/json',
                            'Content-Type: application/json',
                        ],
                    ]);
                    $list = json_decode($response->getContent(true), true);
                } catch (\Exception $e) {
                    $this->logger->debug('Could not load list URL: '.$this->sourceUrl, $this->constructListPayload($page, $limit));
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
                if (null === $property) {
                    $property = new Property();
                    $property->setType($propertyTypeMap[$propertyType]);
                }

                $street = $latitude = $longitude = null;
                if (!empty($ad['street'])) {
                    $street = $ad['street']['label'];
                }
                if (!empty($ad['lat']) && !empty($ad['lng'])) {
                    $latitude = (string) $ad['lat'];
                    $longitude = (string) $ad['lng'];
                }
                $location = $this->locationRepository->findLocation($brno, null, $street, $latitude, $longitude);
                if (null === $location) {
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
                if (null !== $property->getDisposition()) {
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
                ],
            ],
        ];
    }

    protected function constructDetailUrl(int $id): string
    {
        $url = $this->getSourceUrl().'/offer/'.$id;

        return $url;
    }
}
