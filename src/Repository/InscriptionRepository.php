<?php

namespace App\Repository;

use App\Entity\EtatAvancement;
use App\Entity\Formation;
use App\Entity\Inscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Persiste et sauvegarde une inscription en base de données.
     *
     * @param Inscription $entity
     */
    public function add(Inscription $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne l'inscription d'un utilisateur à une formation, ou null si elle n'existe pas.
     *
     * @param User $user
     * @param Formation $formation
     * @return Inscription|null
     */
    public function findOneByUserAndFormation(User $user, Formation $formation): ?Inscription
    {
        return $this->createQueryBuilder('i')
                ->where('i.user = :user')
                ->andWhere('i.formation = :formation')
                ->setParameter('user', $user)
                ->setParameter('formation', $formation)
                ->getQuery()
                ->getOneOrNullResult();
    }

    /**
     * Retourne toutes les inscriptions d'un utilisateur.
     *
     * @param User $user
     * @return Inscription[]
     */
    public function findAllByUser(User $user): array
    {
        return $this->createQueryBuilder('i')
                ->where('i.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getResult();
    }

    /**
     * Retourne l'historique des inscriptions d'un utilisateur, des plus récentes
     * aux plus anciennes (date d'inscription).
     *
     * @param User $user
     * @return Inscription[]
     */
    public function findAllByUserOrderByDateInscription(User $user): array
    {
        return $this->createQueryBuilder('i')
                ->where('i.user = :user')
                ->setParameter('user', $user)
                ->orderBy('i.dateInscription', 'DESC')
                ->getQuery()
                ->getResult();
    }

    /**
     * Retourne les formations terminées par un utilisateur, des plus récemment
     * terminées aux plus anciennes.
     *
     * @param User $user
     * @return Inscription[]
     */
    public function findTermineesByUserOrderByDateValidation(User $user): array
    {
        return $this->createQueryBuilder('i')
                ->where('i.user = :user')
                ->andWhere('i.etat = :etat')
                ->setParameter('user', $user)
                ->setParameter('etat', EtatAvancement::TERMINEE)
                ->orderBy('i.dateValidation', 'DESC')
                ->getQuery()
                ->getResult();
    }
}
