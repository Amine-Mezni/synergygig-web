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

// 🔥 VALIDATION
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\File;

class OffersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('title', null, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'minMessage' => 'Minimum 3 caractères.',
                        'max' => 150,
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire.']),
                    new Length([
                        'min' => 10,
                        'minMessage' => 'Minimum 10 caractères.',
                    ]),
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Internal' => 'INTERNAL',
                    'Gig' => 'GIG',
                ],
                'placeholder' => 'Choisir un type',
                'constraints' => [
                    new NotBlank(['message' => 'Le type est obligatoire.']),
                ],
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
                'constraints' => [
                    new NotBlank(['message' => 'Le statut est obligatoire.']),
                ],
            ])

            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => false,
                'constraints' => [
                    new NotBlank(['message' => 'Le montant est obligatoire.']),
                    new PositiveOrZero(['message' => 'Le montant doit être positif.']),
                ],
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