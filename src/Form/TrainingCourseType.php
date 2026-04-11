<?php

namespace App\Form;

use App\Entity\TrainingCourse;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TrainingCourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 200)],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Course title'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Course description...'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Technical' => 'TECHNICAL',
                    'Soft Skills' => 'SOFT_SKILLS',
                    'Compliance' => 'COMPLIANCE',
                    'Onboarding' => 'ONBOARDING',
                    'Leadership' => 'LEADERSHIP',
                ],
                'required' => false,
                'placeholder' => 'Select category',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('difficulty', ChoiceType::class, [
                'choices' => [
                    'Beginner' => 'BEGINNER',
                    'Intermediate' => 'INTERMEDIATE',
                    'Advanced' => 'ADVANCED',
                ],
                'required' => false,
                'placeholder' => 'Select difficulty',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('duration_hours', NumberType::class, [
                'required' => false,
                'label' => 'Duration (hours)',
                'attr' => ['class' => 'form-control', 'placeholder' => '0'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('instructor_name', TextType::class, [
                'required' => false,
                'label' => 'Instructor',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Instructor name'],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('max_participants', IntegerType::class, [
                'required' => false,
                'label' => 'Max Participants',
                'attr' => ['class' => 'form-control', 'placeholder' => '30'],
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
                    'Active' => 'ACTIVE',
                    'Archived' => 'ARCHIVED',
                ],
                'placeholder' => 'Select status',
                'attr' => ['class' => 'form-control form-select'],
                'label_attr' => ['class' => 'form-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TrainingCourse::class]);
    }
}
