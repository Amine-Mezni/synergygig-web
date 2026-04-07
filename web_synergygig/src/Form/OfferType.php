<?php

namespace App\Form;

use App\Entity\Department;
use App\Entity\Offer;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 200)],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Job title or offer name'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Describe the offer...'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('offer_type', ChoiceType::class, [
                'choices' => [
                    'Full Time' => 'FULL_TIME',
                    'Part Time' => 'PART_TIME',
                    'Freelance' => 'FREELANCE',
                    'Internship' => 'INTERNSHIP',
                    'Contract' => 'CONTRACT',
                ],
                'constraints' => [new Assert\NotBlank(message: 'Please select a type.')],
                'label' => 'Type',
                'placeholder' => 'Select type',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('department', EntityType::class, [
                'class' => Department::class,
                'choice_label' => 'name',
                'required' => false,
                'placeholder' => 'Select department',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $u) {
                    return $u->getFirstName() . ' ' . $u->getLastName();
                },
                'required' => false,
                'placeholder' => 'Select owner',
                'label' => 'Posted By',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('required_skills', TextareaType::class, [
                'required' => false,
                'label' => 'Required Skills',
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'PHP, Symfony, MySQL...'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Remote / Paris / Tunis...'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('amount', NumberType::class, [
                'required' => false,
                'label' => 'Salary / Budget',
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('currency', ChoiceType::class, [
                'choices' => ['USD' => 'USD', 'EUR' => 'EUR', 'TND' => 'TND', 'GBP' => 'GBP'],
                'required' => false,
                'placeholder' => 'Currency',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Start Date',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('end_date', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'End Date',
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Draft' => 'DRAFT',
                    'Open' => 'OPEN',
                    'Closed' => 'CLOSED',
                    'Cancelled' => 'CANCELLED',
                ],
                'constraints' => [new Assert\NotBlank(message: 'Please select a status.')],
                'placeholder' => 'Select status',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Offer::class]);
    }
}
