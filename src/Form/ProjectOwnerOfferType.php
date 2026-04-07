<?php

namespace App\Form;

use App\Entity\Offers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProjectOwnerOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'Titre',
                'trim' => true
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Internal' => 'INTERNAL',
                    'Gig' => 'GIG',
                ],
                'placeholder' => 'Choisir un type',
            ])

            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => 'USD',
            ])

            ->add('imageFile', FileType::class, [
                'label' => 'Image de l’offre',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Image invalide (JPG, PNG ou WEBP).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offers::class,
        ]);
    }
}