<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'utilisateur_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(
        name: 'menu_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Menu $menu = null;

    #[ORM\Column(name: 'nombre_personnes')]
    private ?int $nombrePersonnes = null;

    #[ORM\Column(
        name: 'date_creation',
        type: Types::DATETIMETZ_IMMUTABLE
    )]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(
        name: 'date_prestation',
        type: Types::DATE_IMMUTABLE
    )]
    private ?\DateTimeImmutable $datePrestation = null;

    #[ORM\Column(
        name: 'heure_livraison',
        type: Types::TIME_IMMUTABLE
    )]
    private ?\DateTimeImmutable $heureLivraison = null;

    #[ORM\Column(name: 'adresse_livraison', length: 255)]
    private ?string $adresseLivraison = null;

    #[ORM\Column(name: 'code_postal_livraison', length: 20)]
    private ?string $codePostalLivraison = null;

    #[ORM\Column(name: 'ville_livraison', length: 100)]
    private ?string $villeLivraison = null;

    #[ORM\Column(name: 'telephone_contact', length: 20)]
    private ?string $telephoneContact = null;

    #[ORM\Column(name: 'montant_menu_centimes')]
    private ?int $montantMenuCentimes = null;

    #[ORM\Column(name: 'montant_remise_centimes')]
    private int $montantRemiseCentimes = 0;

    #[ORM\Column(name: 'frais_livraison_centimes')]
    private ?int $fraisLivraisonCentimes = null;

    #[ORM\Column(name: 'montant_total_centimes')]
    private ?int $montantTotalCentimes = null;

    #[ORM\Column(length: 40)]
    private string $statut = 'en_attente';

    #[ORM\Column(name: 'materiel_prete')]
    private bool $materielPrete = false;

    #[ORM\Column(
        name: 'date_retour_materiel',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true
    )]
    private ?\DateTimeImmutable $dateRetourMateriel = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getNombrePersonnes(): ?int
    {
        return $this->nombrePersonnes;
    }

    public function setNombrePersonnes(int $nombrePersonnes): static
    {
        $this->nombrePersonnes = $nombrePersonnes;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDatePrestation(): ?\DateTimeImmutable
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(\DateTimeImmutable $datePrestation): static
    {
        $this->datePrestation = $datePrestation;

        return $this;
    }

    public function getHeureLivraison(): ?\DateTimeImmutable
    {
        return $this->heureLivraison;
    }

    public function setHeureLivraison(\DateTimeImmutable $heureLivraison): static
    {
        $this->heureLivraison = $heureLivraison;

        return $this;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(string $adresseLivraison): static
    {
        $this->adresseLivraison = $adresseLivraison;

        return $this;
    }

    public function getCodePostalLivraison(): ?string
    {
        return $this->codePostalLivraison;
    }

    public function setCodePostalLivraison(string $codePostalLivraison): static
    {
        $this->codePostalLivraison = $codePostalLivraison;

        return $this;
    }

    public function getVilleLivraison(): ?string
    {
        return $this->villeLivraison;
    }

    public function setVilleLivraison(string $villeLivraison): static
    {
        $this->villeLivraison = $villeLivraison;

        return $this;
    }

    public function getTelephoneContact(): ?string
    {
        return $this->telephoneContact;
    }

    public function setTelephoneContact(string $telephoneContact): static
    {
        $this->telephoneContact = $telephoneContact;

        return $this;
    }

    public function getMontantMenuCentimes(): ?int
    {
        return $this->montantMenuCentimes;
    }

    public function setMontantMenuCentimes(int $montantMenuCentimes): static
    {
        $this->montantMenuCentimes = $montantMenuCentimes;

        return $this;
    }

    public function getMontantRemiseCentimes(): int
    {
    return $this->montantRemiseCentimes;
    }

    public function setMontantRemiseCentimes(int $montantRemiseCentimes): static
    {
        $this->montantRemiseCentimes = $montantRemiseCentimes;

        return $this;
    }

    public function getFraisLivraisonCentimes(): ?int
    {
        return $this->fraisLivraisonCentimes;
    }

    public function setFraisLivraisonCentimes(int $fraisLivraisonCentimes): static
    {
        $this->fraisLivraisonCentimes = $fraisLivraisonCentimes;

        return $this;
    }

    public function getMontantTotalCentimes(): ?int
    {
        return $this->montantTotalCentimes;
    }

    public function setMontantTotalCentimes(int $montantTotalCentimes): static
    {
        $this->montantTotalCentimes = $montantTotalCentimes;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isMaterielPrete(): bool
    {
        return $this->materielPrete;
    }

    public function setMaterielPrete(bool $materielPrete): static
    {
        $this->materielPrete = $materielPrete;

        return $this;
    }

    public function getDateRetourMateriel(): ?\DateTimeImmutable
    {
        return $this->dateRetourMateriel;
    }

    public function setDateRetourMateriel(?\DateTimeImmutable $dateRetourMateriel): static
    {
        $this->dateRetourMateriel = $dateRetourMateriel;

        return $this;
    }
}