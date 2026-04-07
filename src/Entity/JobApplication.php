<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'job_applications')]
class JobApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $applicant;

    #[ORM\ManyToOne(targetEntity: Offer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(nullable: false)]
    private Offer $offer;

    #[ORM\Column(length: 50)]
    private string $status = 'PENDING'; // PENDING, ACCEPTED, REJECTED, WITHDRAWN

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $coverMessage = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $respondedAt = null;

    #[ORM\OneToMany(mappedBy: 'jobApplication', targetEntity: Contract::class)]
    private Collection $contracts;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getApplicant(): User { return $this->applicant; }
    public function setApplicant(User $applicant): self { $this->applicant = $applicant; return $this; }
    public function getOffer(): Offer { return $this->offer; }
    public function setOffer(Offer $offer): self { $this->offer = $offer; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCoverMessage(): ?string { return $this->coverMessage; }
    public function setCoverMessage(?string $coverMessage): self { $this->coverMessage = $coverMessage; return $this; }
    public function getContracts(): Collection { return $this->contracts; }
}
