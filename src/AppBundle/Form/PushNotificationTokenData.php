<?php

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\CityDistrict;
use AppBundle\Entity\PropertyDisposition;
use AppBundle\Entity\PushNotificationToken;
use AppBundle\Repository\CityRepository;
use AppBundle\Repository\PushNotificationTokenRepository;
use Symfony\Component\Validator\Constraints as Assert;

final class PushNotificationTokenData
{

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    public $token;

    /**
     * @var bool
     *
     * @Assert\NotNull()
     */
    public $enabled;

    /**
     * @var CityRepository
     */
    private $cityRepository;

    /**
     * @var PushNotificationTokenRepository
     */
    private $tokenRepository;

    public function __construct(CityRepository $cityRepository, PushNotificationTokenRepository $tokenRepository)
    {
        $this->cityRepository = $cityRepository;
        $this->tokenRepository = $tokenRepository;
    }

    public function createOrUpdateEntity(array $filters): PushNotificationToken
    {
        $existingEntity = $this->tokenRepository->findOneByToken($this->token);
        $entity = $existingEntity ?: new PushNotificationToken();

        $entity->setActive(true);
        $entity->setErrorCount(0);
        $entity->setToken($this->token);
        $entity->setEnabled($this->enabled);
        $entity->setFilters($this->sanitizeFilters($filters));

        return $entity;
    }

    private function sanitizeFilters(array $rawFilters): array
    {
        $filters = [];

        foreach ($rawFilters as $cityCode => $rawCityFilters) {
            $city = $this->cityRepository->findOneByCode((string)$cityCode);
            if ($city === null) {
                continue;
            }
            $cityDistrictCodes = $city->getCityDistrictCodes();
            $cityDistrictCodes[] = CityDistrict::CODE_UNASSIGNED;
            $cityFilters = [];
            foreach ($rawCityFilters as $type => $parameters) {
                switch ($type) {
                    case 'price':
                        if (isset($parameters['gte']) || isset($parameters['lte'])) {
                            $cityFilters[$type] = [];
                            if (isset($parameters['gte'])) {
                                $cityFilters[$type]['gte'] = intval($parameters['gte']);
                            }
                            if (isset($parameters['lte'])) {
                                $cityFilters[$type]['lte'] = intval($parameters['lte']);
                            }
                        }
                        break;
                    case 'disposition':
                        if (!empty($parameters)) {
                            $cityFilters[$type] = [];
                            foreach ($parameters as $parameter) {
                                if (in_array($parameter, PropertyDisposition::getCodes())) {
                                    $cityFilters[$type][] = $parameter;
                                }
                            }
                        }
                        break;
                    case 'cityDistrict':
                        if (!empty($parameters)) {
                            $cityFilters[$type] = [];
                            foreach ($parameters as $parameter) {
                                if (in_array($parameter, $cityDistrictCodes)) {
                                    $cityFilters[$type][] = $parameter;
                                }
                            }
                        }
                        break;
                }
            }
            if (!empty($cityFilters)) {
                $filters[$cityCode] = $cityFilters;
            }
        }
        
        return $filters;
    }
}
