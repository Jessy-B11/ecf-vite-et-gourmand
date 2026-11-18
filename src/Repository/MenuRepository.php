<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * @return Menu[]
     */
    public function findByFiltres(
        ?int $prixMaxCentimes = null,
        ?int $themeId = null,
        ?int $regimeId = null,
        ?int $prixMinCentimes = null,
        ?int $nombrePersonnes = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($prixMaxCentimes !== null) {
            $queryBuilder
                ->andWhere('m.prixMinimumCentimes <= :prixMax')
                ->setParameter('prixMax', $prixMaxCentimes);
        }
        if ($prixMinCentimes !== null) {
            $queryBuilder
                ->andWhere('m.prixMinimumCentimes >= :prixMin')
                ->setParameter('prixMin', $prixMinCentimes);
        }

        if ($themeId !== null) {
            $queryBuilder
                ->andWhere('m.theme = :themeId')
                ->setParameter('themeId', $themeId);
        }

        if ($regimeId !== null) {
            $queryBuilder
                ->andWhere('m.regime = :regimeId')
                ->setParameter('regimeId', $regimeId);
        }

                if ($nombrePersonnes !== null) {
            $queryBuilder
                ->andWhere('m.minimumPersonnes <= :nombrePersonnes')
                ->setParameter('nombrePersonnes', $nombrePersonnes);
        }
        

        return $queryBuilder
            ->orderBy('m.prixMinimumCentimes', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Menu[]
     */
    public function findByPrixMaximum(int $prixMaxCentimes): array
    {
        return $this->findByFiltres($prixMaxCentimes);
    }
}