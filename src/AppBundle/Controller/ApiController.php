<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PropertyDisposition;
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
        $entity = new PushNotificationToken();
        $form = $this->createForm(PushNotificationTokenType::class, $entity);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $existingEntity = $em->getRepository('AppBundle:PushNotificationToken')
                ->findOneByToken($entity->getToken());
            if ($existingEntity !== null) {
                $entity = $existingEntity;
            }

            // sanitize filters
            $rawFilter = $request->request->get('filter', []);
            $filters = [];
            foreach ($rawFilter as $type => $parameters) {
                switch ($type) {
                    case 'price':
                        if (isset($parameters['gte']) || isset($parameters['lte'])) {
                            $filters[$type] = [];
                            if (isset($parameters['gte'])) {
                                $filters[$type]['gte'] = intval($parameters['gte']);
                            }
                            if (isset($parameters['lte'])) {
                                $filters[$type]['lte'] = intval($parameters['lte']);
                            }
                        }
                        break;
                    case 'disposition':
                        if (!empty($parameters)) {
                            $filters[$type] = [];
                            foreach ($parameters as $parameter) {
                                if (in_array($parameter, PropertyDisposition::getCodes())) {
                                    $filters[$type][] = $parameter;
                                }
                            }
                        }
                        break;
                }
            }

            $entity->setActive(1);
            $entity->setErrorCount(0);
            $entity->setEnabled($request->request->get('enabled', false));
            $entity->setFilters($filters);
            $em->persist($entity);
            $em->flush();

            return $entity;
        }

        return $form;
    }

}
