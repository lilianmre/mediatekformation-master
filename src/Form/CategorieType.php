<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\ORM\EntityManagerInterface;

class CategorieType extends AbstractType
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entityManager = $this->entityManager;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la catégorie',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de la catégorie est obligatoire.',
                    ]),
                    new Length([
                        'max' => 50,
                        'maxMessage' => 'Le nom ne doit pas dépasser {{ limit }} caractères.',
                    ]),
                    new Callback(function ($value, ExecutionContextInterface $context) use ($entityManager) {
                        if (empty($value)) {
                            return;
                        }

                        $existing = $entityManager->getRepository(Categorie::class)
                            ->findOneBy(['name' => $value]);

                        if ($existing !== null) {
                            $context->buildViolation('Une catégorie avec ce nom existe déjà.')
                                ->addViolation();
                        }
                    }),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
}
