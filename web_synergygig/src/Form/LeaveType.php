<?php

namespace App\Form;

use App\Entity\Leave;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LeaveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $u) {
                    return $u->getFirstName() . ' ' . $u->getLastName();
                },
                'placeholder' => 'Select employee',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Sick Leave' => 'SICK',
                    'Vacation' => 'VACATION',
                    'Personal Leave' => 'PERSONAL',
                    'Maternity' => 'MATERNITY',
                    'Paternity' => 'PATERNITY',
                    'Unpaid' => 'UNPAID',
                ],
                'constraints' => [new Assert\NotBlank()],
                'placeholder' => 'Select type',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Start Date',
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('end_date', DateType::class, [
                'widget' => 'single_text',
                'label' => 'End Date',
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('reason', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Reason for leave...'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'PENDING',
                    'Approved' => 'APPROVED',
                    'Rejected' => 'REJECTED',
                ],
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Leave::class]);
    }
}
