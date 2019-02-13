<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PushNotificationToken;
use AppBundle\Form\PushNotificationTokenType;
use FOS\RestBundle\Controller\Annotations;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{

    /**
     * @Annotations\Get("/adverts")
     */
    public function getAdvertsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $advertRepository = $em->getRepository('AppBundle:Advert');
        return $advertRepository->getLatestAdverts();
    }

    /**
     * @Annotations\Get("/adverts/{id}", requirements={"id"="[0-9]+"})
     */
    public function getAdvertAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $advertRepository = $em->getRepository('AppBundle:Advert');
        $advert = $advertRepository->find($id);
        if ($advert === null) {
            return $this->createNotFoundException();
        }
        return $advert;
    }

    /**
     * @Annotations\Post("/push-notification-token")
     */
    public function postPushNotificationTokenAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = new PushNotificationToken();
        $form = $this->createForm(PushNotificationTokenType::class, $entity, [
            'city_repository' => $em->getRepository('AppBundle:City'),
        ]);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $existingEntity = $em->getRepository('AppBundle:PushNotificationToken')
                ->findOneByToken($entity->getToken());
            if ($existingEntity !== null) {
                $entity = $existingEntity;
            }

            $entity->setActive(1);
            $entity->setErrorCount(0);
            $entity->setEnabled($form->getData()->getEnabled());
            $entity->setFilters($form->getData()->getFilters());
            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return $form;
    }
}
