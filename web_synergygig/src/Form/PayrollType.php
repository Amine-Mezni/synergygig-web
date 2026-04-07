<?php

namespace App\Form;

use App\Entity\Payroll;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PayrollType extends AbstractType
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
            ->add('month', IntegerType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Range(min: 1, max: 12)],
                'attr' => ['class' => 'form-control', 'min' => 1, 'max' => 12, 'placeholder' => '1-12'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('year', IntegerType::class, [
                'constraints' => [new Assert\NotBlank()],
                'attr' => ['class' => 'form-control', 'placeholder' => date('Y')],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('base_salary', TextType::class, [
                'required' => false,
                'label' => 'Base Salary',
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('bonus', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('deductions', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('net_salary', TextType::class, [
                'required' => false,
                'label' => 'Net Salary',
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('total_hours_worked', NumberType::class, [
                'required' => false,
                'label' => 'Total Hours Worked',
                'attr' => ['class' => 'form-control', 'placeholder' => '0'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'PENDING',
                    'Paid' => 'PAID',
                    'Cancelled' => 'CANCELLED',
                ],
                'placeholder' => 'Select status',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Payroll::class]);
    }
}
