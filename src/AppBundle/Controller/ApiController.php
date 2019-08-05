<?php

declare(strict_types=1);

namespace AppBundle\Controller;

use AppBundle\Form\PushNotificationTokenData;
use AppBundle\Form\PushNotificationTokenType;
use AppBundle\Repository\AdvertRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class ApiController extends AbstractController
{

    /**
     * @Annotations\Get("/adverts/{type}", requirements={"type"="sale|rent"}, defaults={"type"="sale"})
     */
    public function getAdvertsAction(AdvertRepository $advertRepository, $type): array
    {
        return $advertRepository->getLatestAdverts((string)$type);
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
        PushNotificationTokenData $dto
    ) {
        $form = $this->createForm(PushNotificationTokenType::class, $dto);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $dto->createOrUpdateEntity($request->get('filters', []));
            $entityManager->persist($entity);
            $entityManager->flush();

            return $entity;
        }

        return $form;
    }
}
