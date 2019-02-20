<?php

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Entity\PushNotificationToken;
use AppBundle\Form\PushNotificationTokenType;
use AppBundle\Entity\CityDistrict;
use AppBundle\Entity\PropertyDisposition;
use AppBundle\Repository\AdvertRepository;
use AppBundle\Repository\CityRepository;
use AppBundle\Repository\PushNotificationTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class ApiController extends AbstractController
{

    /**
     * @Annotations\Get("/adverts")
     */
    public function getAdvertsAction(AdvertRepository $advertRepository): array
    {
        return $advertRepository->getLatestAdverts();
    }

    /**
     * @Annotations\Get("/adverts/{id}", requirements={"id"="[0-9]+"})
     */
    public function getAdvertAction(AdvertRepository $advertRepository, string $id)
    {
        $advert = $advertRepository->find($id);
        if ($advert === null) {
            return $this->createNotFoundException();
        }
        return $advert;
    }

    /**
     * @Annotations\Post("/push-notification-token")
     */
    public function postPushNotificationTokenAction(
        Request $request,
        EntityManagerInterface $entityManager,
        PushNotificationTokenRepository $tokenRepository,
        CityRepository $cityRepository
    ) {
        $entity = new PushNotificationToken();
        $form = $this->createForm(PushNotificationTokenType::class, $entity);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $existingEntity = $tokenRepository->findOneByToken($entity->getToken());
            if ($existingEntity !== null) {
                $entity = $existingEntity;
            }

            $entity->setActive(1);
            $entity->setErrorCount(0);
            $entity->setEnabled($form->getData()->getEnabled());
            $entity->setFilters($this->sanitizeFilters($request->get('filters'), $cityRepository));
            $entityManager->persist($entity);
            $entityManager->flush();

            return $entity;
        }

        return $form;
    }

    private function sanitizeFilters(array $rawFilters, CityRepository $cityRepository): array
    {
        $filters = [];

        foreach ($rawFilters as $cityCode => $rawCityFilters) {
            $city = $cityRepository->findOneByCode((string)$cityCode);
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
