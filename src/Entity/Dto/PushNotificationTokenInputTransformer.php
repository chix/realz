<?php

namespace App\Entity\Dto;

use ApiPlatform\Core\Bridge\Symfony\Validator\Exception\ValidationException as ApiPlatformValidationException;
use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Entity\CityDistrict;
use App\Entity\PropertyDisposition;
use App\Entity\PushNotificationToken;
use App\Repository\AdvertTypeRepository;
use App\Repository\CityRepository;
use App\Repository\PushNotificationTokenRepository;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class PushNotificationTokenInputTransformer implements DataTransformerInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var CityRepository
     */
    private $cityRepository;

    /**
     * @var PushNotificationTokenRepository
     */
    private $tokenRepository;

    /**
     * @var AdvertTypeRepository
     */
    protected $advertTypeRepository;

    public function __construct(
        CityRepository $cityRepository,
        PushNotificationTokenRepository $tokenRepository,
        AdvertTypeRepository $advertTypeRepository,
        ValidatorInterface $validator
    ) {
        $this->cityRepository = $cityRepository;
        $this->tokenRepository = $tokenRepository;
        $this->advertTypeRepository = $advertTypeRepository;
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     *
     * @param PushNotificationTokenInput $data
     * @param array<mixed> $context
     *
     * @throws ApiPlatformValidationException
     */
    public function transform($data, string $to, array $context = []): PushNotificationToken
    {
        // basic dto annotation validation
        $this->validator->validate($data);

        $token = $this->createOrUpdateEntity($data);
        
        return $token;
    }

    /**
     * @throws ApiPlatformValidationException
     */
    private function createOrUpdateEntity(PushNotificationTokenInput $data): PushNotificationToken
    {
        $existingEntity = $this->tokenRepository->findOneByToken($data->getToken());
        $entity = $existingEntity ?: new PushNotificationToken();

        $entity->setActive(true);
        $entity->setErrorCount(0);
        $entity->setToken($data->getToken());
        $entity->setEnabled($data->getEnabled());
        $entity->setFilters($this->sanitizeFilters($data->getFilters()));

        return $entity;
    }

    /**
     * @param array<mixed>|null $rawFilters
     *
     * @return array<mixed>
     *
     * @throws ApiPlatformValidationException
     */
    private function sanitizeFilters(?array $rawFilters): array
    {
        $filters = [];
        if ($rawFilters === null) {
            return $filters;
        }

        foreach ($rawFilters as $cityCode => $rawCityFilters) {
            $city = $this->cityRepository->findOneByCode((string)$cityCode);
            if ($city === null) {
                throw $this->createValidationException(
                    'filters',
                    "City with code {$cityCode} not found."
                );
            }
            $cityDistrictCodes = $city->getCityDistrictCodes();
            $cityDistrictCodes[] = CityDistrict::CODE_UNASSIGNED;
            $cityFilters = [];
            foreach ($rawCityFilters as $type => $filter) {
                switch ($type) {
                    case 'advertType':
                        $advertType = $this->advertTypeRepository->findOneByCode($filter);
                        if ($advertType === null) {
                            throw $this->createValidationException(
                                'filters',
                                "Advert type {$filter} not found."
                            );
                        }
                        $cityFilters[$type] = $advertType->getCode();
                        break;
                    case 'price':
                        if (!isset($filter['gte']) && !isset($filter['lte'])) {
                            throw $this->createValidationException(
                                'filters',
                                "Either gte or lte have to be set on the price filter."
                            );
                        }
                        $cityFilters[$type] = [];
                        if (isset($filter['gte'])) {
                            $cityFilters[$type]['gte'] = intval($filter['gte']);
                        }
                        if (isset($filter['lte'])) {
                            $cityFilters[$type]['lte'] = intval($filter['lte']);
                        }
                        break;
                    case 'disposition':
                        if (!empty($filter)) {
                            $cityFilters[$type] = [];
                            foreach ($filter as $dispositionCode) {
                                if (!in_array($dispositionCode, PropertyDisposition::getCodes())) {
                                    throw $this->createValidationException(
                                        'filters',
                                        "Disposition {$dispositionCode} not found."
                                    );
                                }
                                $cityFilters[$type][] = $dispositionCode;
                            }
                        }
                        break;
                    case 'cityDistrict':
                        if (!empty($filter)) {
                            $cityFilters[$type] = [];
                            foreach ($filter as $disctrictCode) {
                                if (!in_array($disctrictCode, $cityDistrictCodes)) {
                                    throw $this->createValidationException(
                                        'filters',
                                        "City district {$disctrictCode} not found."
                                    );
                                }
                                $cityFilters[$type][] = $disctrictCode;
                            }
                        }
                        break;
                    default:
                        throw $this->createValidationException(
                            'filters',
                            "Unsupported filter type {$type}."
                        );
                }
            }
            if (!empty($cityFilters)) {
                $filters[$cityCode] = $cityFilters;
            }
        }
        
        return $filters;
    }

    private function createValidationException(string $property, string $message): ApiPlatformValidationException
    {
        $violationList = new ConstraintViolationList([new ConstraintViolation(
            $message,
            null,
            [],
            null,
            $property,
            null
        )]);
        return new ApiPlatformValidationException($violationList);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $data
     * @param array<mixed> $context
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return $to === PushNotificationToken::class;
    }
}
