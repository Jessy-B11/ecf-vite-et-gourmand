<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public const ELEMENTS_PAR_PAGE = 20;
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }


    /**
     * @return Commande[]
     */
        /**
     * @return Paginator<Commande>
     */
    public function findDernieresPourGestion(
        ?string $statut = null,
        ?string $client = null,
        int $page = 1
    ): Paginator {
        $page = max(1, $page);
        $limite = self::ELEMENTS_PAR_PAGE;

        $qb = $this->createQueryBuilder('c')
            ->addSelect('u', 'm')
            ->join('c.utilisateur', 'u')
            ->join('c.menu', 'm')
            ->orderBy('c.dateCreation', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setFirstResult(($page - 1) * $limite)
            ->setMaxResults($limite);

        if ($statut !== null && $statut !== '') {
            $qb
                ->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($client !== null && trim($client) !== '') {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        'LOWER(u.nom) LIKE LOWER(:client)',
                        'LOWER(u.prenom) LIKE LOWER(:client)',
                        'LOWER(u.email) LIKE LOWER(:client)'
                    )
                )
                ->setParameter(
                    'client',
                    '%' . trim($client) . '%'
                );
        }

        return new Paginator($qb->getQuery(), false);
    }

//    public function findOneBySomeField($value): ?Commande
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
