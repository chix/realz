<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException as ApiPlatformValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\City;
use App\Entity\CityDistrict;
use App\Entity\Dto\PushNotificationTokenInput;
use App\Entity\PropertyDisposition;
use App\Entity\PushNotificationToken;
use App\Repository\AdvertTypeRepository;
use App\Repository\CityRepository;
use App\Repository\PushNotificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @implements ProcessorInterface<PushNotificationTokenInput, PushNotificationToken>
 */
final class PushNotificationTokenProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CityRepository $cityRepository,
        private PushNotificationTokenRepository $tokenRepository,
        private AdvertTypeRepository $advertTypeRepository,
        private ValidatorInterface $validator
    ) {
    }

    /**
     * @param PushNotificationTokenInput $data
     *
     * @throws ApiPlatformValidationException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PushNotificationToken
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

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

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
        if (null === $rawFilters) {
            return $filters;
        }

        foreach ($rawFilters as $rawCityFilters) {
            /** @var City|null $city */
            $city = null;
            ksort($rawCityFilters);
            /** @var array<string,mixed> $cityFilters */
            $cityFilters = [];
            foreach ($rawCityFilters as $type => $filter) {
                switch ($type) {
                    case 'advertType':
                        $advertType = $this->advertTypeRepository->findOneByCode($filter);
                        if (null === $advertType) {
                            throw $this->createValidationException('filters', "Advert type {$filter} not found.");
                        }
                        $cityFilters[$type] = $advertType->getCode();
                        break;
                    case 'cityCode':
                        $city = $this->cityRepository->findOneByCode($filter);
                        if (null === $city) {
                            throw $this->createValidationException('filters', "City with code {$filter} not found.");
                        }
                        $cityFilters[$type] = $city->getCode();
                        break;
                    case 'cityDistrict':
                        if (empty($filter) || null === $city) {
                            continue 2;
                        }
                        $cityDistrictCodes = $city->getCityDistrictCodes();
                        $cityDistrictCodes[] = CityDistrict::CODE_UNASSIGNED;
                        $cityFilters[$type] = [];
                        foreach ($filter as $disctrictCode) {
                            if (!in_array($disctrictCode, $cityDistrictCodes)) {
                                throw $this->createValidationException('filters', "City district {$disctrictCode} not found.");
                            }
                            $cityFilters[$type][] = $disctrictCode;
                        }
                        break;
                    case 'disposition':
                        if (!empty($filter)) {
                            $cityFilters[$type] = [];
                            foreach ($filter as $dispositionCode) {
                                if (!in_array($dispositionCode, PropertyDisposition::getCodes())) {
                                    throw $this->createValidationException('filters', "Disposition {$dispositionCode} not found.");
                                }
                                $cityFilters[$type][] = $dispositionCode;
                            }
                        }
                        break;
                    case 'price':
                        if (!isset($filter['gte']) && !isset($filter['lte'])) {
                            throw $this->createValidationException('filters', 'Either gte or lte have to be set on the price filter.');
                        }
                        $cityFilters[$type] = [];
                        if (isset($filter['gte'])) {
                            $cityFilters[$type]['gte'] = intval($filter['gte']);
                        }
                        if (isset($filter['lte'])) {
                            $cityFilters[$type]['lte'] = intval($filter['lte']);
                        }
                        break;
                    default:
                        throw $this->createValidationException('filters', "Unsupported filter type {$type}.");
                }
            }
            if (!empty($cityFilters)) {
                $filters[] = $cityFilters;
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
}
