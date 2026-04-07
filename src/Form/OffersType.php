<?php

namespace App\Form;

use App\Entity\Offers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class OffersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'Titre',
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

            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Draft' => 'DRAFT',
                    'Published' => 'PUBLISHED',
                    'In progress' => 'IN_PROGRESS',
                    'Completed' => 'COMPLETED',
                    'Cancelled' => 'CANCELLED',
                ],
            ])

            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => false,
            ])

            ->add('imageFile', FileType::class, [
                'label' => 'Image de l’offre',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '4M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Image invalide.',
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