<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;
use App\Entity\Applications;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Offers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: "Minimum 3 caractères.",
        maxMessage: "Maximum 150 caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "Minimum 10 caractères.",
        maxMessage: "Maximum 2000 caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: "Le type est obligatoire.")]
    #[Assert\Choice(
        choices: ['INTERNAL', 'GIG'],
        message: "Type invalide."
    )]
    private ?string $type = null;

    #[ORM\Column(type: "string")]
    #[Assert\NotBlank(message: "Le statut est obligatoire.")]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "offerss")]
    #[ORM\JoinColumn(name: "created_by", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Users $created_by = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image_url = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotNull(message: "Le montant est obligatoire.")]
    #[Assert\PositiveOrZero(message: "Le montant doit être positif.")]
    private ?float $amount = null;

    #[ORM\OneToMany(mappedBy: "offer_id", targetEntity: Applications::class)]
    private Collection $applicationss;

    public function __construct()
    {
        $this->applicationss = new ArrayCollection();
    }

    // getters setters inchangés
}