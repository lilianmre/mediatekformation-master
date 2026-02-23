<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Formation;
use App\Entity\Playlist;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le titre est obligatoire.',
                    ]),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('videoId', TextType::class, [
                'label' => 'Identifiant vidéo',
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'identifiant vidéo est obligatoire.',
                    ]),
                    new Length([
                        'max' => 20,
                        'maxMessage' => 'L\'identifiant vidéo ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('publishedAt', DateType::class, [
                'label' => 'Date de publication',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'constraints' => [
                    new NotNull([
                        'message' => 'La date de publication est obligatoire.',
                    ]),
                    new LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de publication ne peut pas être postérieure à aujourd\'hui.',
                    ]),
                ],
            ])
            ->add('playlist', EntityType::class, [
                'class' => Playlist::class,
                'choice_label' => 'name',
                'label' => 'Playlist',
                'placeholder' => 'Choisissez une playlist',
                'constraints' => [
                    new NotNull([
                        'message' => 'La playlist est obligatoire.',
                    ]),
                ],
            ])
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'name',
                'label' => 'Catégories',
                'multiple' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
        ]);
    }
}
