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
        ?int $regimeId = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($prixMaxCentimes !== null) {
            $queryBuilder
                ->andWhere('m.prixMinimumCentimes <= :prixMax')
                ->setParameter('prixMax', $prixMaxCentimes);
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