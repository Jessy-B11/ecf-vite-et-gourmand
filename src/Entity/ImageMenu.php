<?php

namespace App\Entity;

use App\Repository\ImageMenuRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImageMenuRepository::class)]
#[ORM\Table(name: 'image_menu')]
#[ORM\UniqueConstraint(
    name: 'image_menu_menu_id_position_key',
    columns: ['menu_id', 'position']
)]
class ImageMenu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $chemin = null;

    #[ORM\Column(name: 'texte_alternatif', length: 255)]
    private ?string $textAlternatif = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(
        name: 'menu_id',
        referencedColumnName: 'id',
        nullable: false
    )]
    private ?Menu $menu = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChemin(): ?string
    {
        return $this->chemin;
    }

    public function setChemin(string $chemin): static
    {
        $this->chemin = $chemin;

        return $this;
    }

    public function getTextAlternatif(): ?string
    {
        return $this->textAlternatif;
    }

    public function setTextAlternatif(string $textAlternatif): static
    {
        $this->textAlternatif = $textAlternatif;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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
}