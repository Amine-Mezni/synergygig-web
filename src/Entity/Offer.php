<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'offers')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $offerType = 'FREELANCE'; // FULL_TIME, PART_TIME, FREELANCE, INTERNSHIP, CONTRACT

    #[ORM\Column(length: 50)]
    private string $status = 'OPEN'; // DRAFT, OPEN, CLOSED, CANCELLED

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $requiredSkills = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount = '0';

    #[ORM\Column(length: 10)]
    private string $currency = 'USD';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\OneToMany(mappedBy: 'offer', targetEntity: JobApplication::class, orphanRemoval: true)]
    private Collection $applications;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getOfferType(): string { return $this->offerType; }
    public function setOfferType(string $offerType): self { $this->offerType = $offerType; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }
    public function getOwner(): User { return $this->owner; }
    public function setOwner(User $owner): self { $this->owner = $owner; return $this; }
    public function getApplications(): Collection { return $this->applications; }
}
