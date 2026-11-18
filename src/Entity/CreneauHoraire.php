<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'creneau_horaire')]
class CreneauHoraire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'creneaux')]
    #[ORM\JoinColumn(name: 'horaire_jour_id', referencedColumnName: 'id', nullable: false)]
    private ?HoraireJour $horaireJour = null;

    #[ORM\Column(name: 'heure_ouverture', type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureOuverture = null;

    #[ORM\Column(name: 'heure_fermeture', type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $heureFermeture = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHoraireJour(): ?HoraireJour
    {
        return $this->horaireJour;
    }

    public function setHoraireJour(HoraireJour $horaireJour): static
    {
        $this->horaireJour = $horaireJour;

        return $this;
    }

    public function getHeureOuverture(): ?\DateTimeImmutable
    {
        return $this->heureOuverture;
    }

    public function setHeureOuverture(\DateTimeImmutable $heureOuverture): static
    {
        $this->heureOuverture = $heureOuverture;

        return $this;
    }

    public function getHeureFermeture(): ?\DateTimeImmutable
    {
        return $this->heureFermeture;
    }

    public function setHeureFermeture(\DateTimeImmutable $heureFermeture): static
    {
        $this->heureFermeture = $heureFermeture;

        return $this;
    }
}
