<?php

namespace App\Entity;

use App\Repository\InscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant l'inscription d'un utilisateur à une formation et son état d'avancement.
 */
#[ORM\Entity(repositoryClass: InscriptionRepository::class)]
#[ORM\Table(name: 'inscription')]
#[ORM\UniqueConstraint(name: 'uniq_inscription_user_formation', columns: ['user_id', 'formation_id'])]
class Inscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Formation::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Formation $formation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(length: 20, enumType: EtatAvancement::class)]
    private EtatAvancement $etat = EtatAvancement::NON_COMMENCEE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateValidation = null;

    /**
     * Initialise la date d'inscription à la date courante.
     */
    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return static
     */
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Formation|null
     */
    public function getFormation(): ?Formation
    {
        return $this->formation;
    }

    /**
     * @param Formation|null $formation
     * @return static
     */
    public function setFormation(?Formation $formation): static
    {
        $this->formation = $formation;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateInscription(): ?\DateTimeInterface
    {
        return $this->dateInscription;
    }

    /**
     * @param \DateTimeInterface $dateInscription
     * @return static
     */
    public function setDateInscription(\DateTimeInterface $dateInscription): static
    {
        $this->dateInscription = $dateInscription;

        return $this;
    }

    /**
     * @return EtatAvancement
     */
    public function getEtat(): EtatAvancement
    {
        return $this->etat;
    }

    /**
     * Met à jour l'état d'avancement et recalcule la date de validation
     * (renseignée si l'état devient "terminée", sinon remise à null).
     *
     * @param EtatAvancement $etat
     * @return static
     */
    public function setEtat(EtatAvancement $etat): static
    {
        $this->etat = $etat;
        $this->dateValidation = $etat === EtatAvancement::TERMINEE ? new \DateTime() : null;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->dateValidation;
    }

    /**
     * @param \DateTimeInterface|null $dateValidation
     * @return static
     */
    public function setDateValidation(?\DateTimeInterface $dateValidation): static
    {
        $this->dateValidation = $dateValidation;

        return $this;
    }
}
