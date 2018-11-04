<?php

namespace AppBundle\Form;

use AppBundle\Entity\CityDistrict;
use AppBundle\Entity\PropertyDisposition;
use AppBundle\Entity\PushNotificationToken;
use AppBundle\Repository\CityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PushNotificationTokenType extends AbstractType
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('token')
            ->add('enabled')
            ->add('filters', null, [
                'required' => false,
            ])
        ;

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            /** @var PushNotificationToken $pushNotificationToken  */
            $pushNotificationToken = $event->getData();
            $rawFilters = $pushNotificationToken->getFilters();
            $sanitizedFilters = $this->sanitizeFilters($rawFilters, $options['city_repository']);
            $pushNotificationToken->setFilters($sanitizedFilters);
            $event->setData($pushNotificationToken);
        });
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PushNotificationToken::class,
            'csrf_protection' => false,
        ]);
        $resolver->setRequired('city_repository');
    }

    /**
     * @param array $rawFilters
     * @param CityRepository $cityRepository
     * @return array
     */
    private function sanitizeFilters($rawFilters, CityRepository $cityRepository)
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
