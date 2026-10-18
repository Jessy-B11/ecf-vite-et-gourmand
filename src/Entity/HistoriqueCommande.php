<?php

namespace App\Entity;

use App\Repository\HistoriqueCommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueCommandeRepository::class)]
#[ORM\Table(name: 'historique_commande')]
class HistoriqueCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'commande_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Commande $commande = null;

    #[ORM\Column(length: 40)]
    private ?string $statut = null;

    #[ORM\Column(
        name: 'date_changement',
        type: Types::DATETIMETZ_IMMUTABLE
    )]
    private \DateTimeImmutable $dateChangement;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'auteur_id',
        referencedColumnName: 'id',
        nullable: true
    )]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(name: 'mode_contact', length: 20, nullable: true)]
    private ?string $modeContact = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    public function __construct()
    {
        $this->dateChangement = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): static
    {
        $this->commande = $commande;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateChangement(): \DateTimeImmutable
    {
        return $this->dateChangement;
    }

    public function setDateChangement(
        \DateTimeImmutable $dateChangement
    ): static {
        $this->dateChangement = $dateChangement;

        return $this;
    }

    public function getAuteur(): ?Utilisateur
    {
        return $this->auteur;
    }

    public function setAuteur(?Utilisateur $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getModeContact(): ?string
    {
        return $this->modeContact;
    }

    public function setModeContact(?string $modeContact): static
    {
        $this->modeContact = $modeContact;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }
}