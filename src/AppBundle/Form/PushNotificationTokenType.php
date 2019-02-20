<?php

declare(strict_types=1);

namespace AppBundle\Form;

use AppBundle\Entity\PushNotificationToken;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PushNotificationTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('token')
            ->add('enabled')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PushNotificationToken::class,
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
