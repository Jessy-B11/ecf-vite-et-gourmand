<?php

namespace App\Repository;

use App\Entity\HoraireJour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<HoraireJour> */
class HoraireJourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HoraireJour::class);
    }

    /** @return list<HoraireJour> */
    public function findSemaineAvecCreneaux(): array
    {
        // Charger tous les créneaux ici évite une requête supplémentaire par jour.
        return $this->createQueryBuilder('jour')
            ->addSelect('creneau')
            ->leftJoin('jour.creneaux', 'creneau')
            ->orderBy('jour.jourSemaine', 'ASC')
            ->addOrderBy('creneau.heureOuverture', 'ASC')
            ->addOrderBy('creneau.heureFermeture', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
