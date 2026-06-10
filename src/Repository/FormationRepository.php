<?php

namespace App\Repository;

use App\Entity\EtatAvancement;
use App\Entity\Formation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * Persiste et sauvegarde une formation en base de données.
     *
     * @param Formation $entity
     */
    public function add(Formation $entity): void
    {
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Supprime une formation de la base de données.
     *
     * @param Formation $entity
     */
    public function remove(Formation $entity): void
    {
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne toutes les formations triées sur un champ
     * @param string $champ
     * @param string $ordre
     * @param string $table si $champ dans une autre table
     * @return Formation[]
     */
    public function findAllOrderBy($champ, $ordre, $table=""): array{
        if($table==""){
            return $this->createQueryBuilder('f')
                    ->orderBy('f.'.$champ, $ordre)
                    ->getQuery()
                    ->getResult();
        }else{
            return $this->createQueryBuilder('f')
                    ->join('f.'.$table, 't')
                    ->orderBy('t.'.$champ, $ordre)
                    ->getQuery()
                    ->getResult();
        }
    }

    /**
     * Enregistrements dont un champ contient une valeur
     * ou tous les enregistrements si la valeur est vide
     * @param string $champ
     * @param string $valeur
     * @param string $table si $champ dans une autre table
     * @return Formation[]
     */
    public function findByContainValue($champ, $valeur, $table=""): array{
        if($valeur==""){
            return $this->findAll();
        }
        if($table==""){
            return $this->createQueryBuilder('f')
                    ->where('f.'.$champ.' LIKE :valeur')
                    ->orderBy('f.publishedAt', 'DESC')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->getQuery()
                    ->getResult();
        }else{
            return $this->createQueryBuilder('f')
                    ->join('f.'.$table, 't')
                    ->where('t.'.$champ.' LIKE :valeur')
                    ->orderBy('f.publishedAt', 'DESC')
                    ->setParameter('valeur', '%'.$valeur.'%')
                    ->getQuery()
                    ->getResult();
        }
    }
    
    /**
     * Retourne les formations dont l'état de suivi de l'utilisateur correspond à la
     * valeur donnée ("non_inscrit" ou une valeur de EtatAvancement).
     * @param User $user
     * @param string $etat
     * @return Formation[]
     */
    public function findAllByEtatForUser(User $user, string $etat): array{
        $queryBuilder = $this->createQueryBuilder('f')
                ->leftJoin('f.inscriptions', 'i', 'WITH', 'i.user = :user')
                ->setParameter('user', $user)
                ->orderBy('f.publishedAt', 'DESC');

        if ($etat === 'non_inscrit') {
            $queryBuilder->andWhere('i.id IS NULL');
        } else {
            $queryBuilder->andWhere('i.etat = :etat')
                    ->setParameter('etat', EtatAvancement::from($etat));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Retourne les n formations les plus récentes
     * @param int $nb
     * @return Formation[]
     */
    public function findAllLasted($nb) : array {
        return $this->createQueryBuilder('f')
                ->orderBy('f.publishedAt', 'DESC')
                ->setMaxResults($nb)
                ->getQuery()
                ->getResult();
    }
    
    /**
     * Retourne la liste des formations d'une playlist
     * @param int $idPlaylist
     * @return Formation[]
     */
    public function findAllForOnePlaylist($idPlaylist): array{
        return $this->createQueryBuilder('f')
                ->join('f.playlist', 'p')
                ->where('p.id=:id')
                ->setParameter('id', $idPlaylist)
                ->orderBy('f.publishedAt', 'ASC')
                ->getQuery()
                ->getResult();
    }
    
}
