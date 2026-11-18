<?php

namespace App\Entity;

use App\Repository\HoraireJourRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HoraireJourRepository::class)]
#[ORM\Table(name: 'horaire_jour')]
#[ORM\UniqueConstraint(name: 'horaire_jour_jour_semaine_key', fields: ['jourSemaine'])]
class HoraireJour
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'jour_semaine')]
    private ?int $jourSemaine = null;

    #[ORM\Column]
    private bool $ferme = false;

    /** @var Collection<int, CreneauHoraire> */
    #[ORM\OneToMany(targetEntity: CreneauHoraire::class, mappedBy: 'horaireJour')]
    private Collection $creneaux;

    public function __construct()
    {
        $this->creneaux = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJourSemaine(): ?int
    {
        return $this->jourSemaine;
    }

    public function setJourSemaine(int $jourSemaine): static
    {
        $this->jourSemaine = $jourSemaine;

        return $this;
    }

    public function isFerme(): bool
    {
        return $this->ferme;
    }

    public function setFerme(bool $ferme): static
    {
        $this->ferme = $ferme;

        return $this;
    }

    /** @return Collection<int, CreneauHoraire> */
    public function getCreneaux(): Collection
    {
        return $this->creneaux;
    }
}
