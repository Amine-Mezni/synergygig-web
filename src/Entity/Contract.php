<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contracts')]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private JobApplication $jobApplication;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $buyer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $freelancer;

    #[ORM\Column(length: 50)]
    private string $status = 'ACTIVE'; // DRAFT, ACTIVE, COMPLETED, CANCELLED, EXPIRED

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $totalAmount = '0';

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $milestones = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $signedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->startDate = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getJobApplication(): JobApplication { return $this->jobApplication; }
    public function setJobApplication(JobApplication $jobApplication): self { $this->jobApplication = $jobApplication; return $this; }
    public function getBuyer(): User { return $this->buyer; }
    public function setBuyer(User $buyer): self { $this->buyer = $buyer; return $this; }
    public function getFreelancer(): User { return $this->freelancer; }
    public function setFreelancer(User $freelancer): self { $this->freelancer = $freelancer; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(string $totalAmount): self { $this->totalAmount = $totalAmount; return $this; }
    public function getStartDate(): \DateTimeInterface { return $this->startDate; }
    public function setStartDate(\DateTimeInterface $startDate): self { $this->startDate = $startDate; return $this; }
    public function getEndDate(): ?\DateTimeInterface { return $this->endDate; }
    public function setEndDate(?\DateTimeInterface $endDate): self { $this->endDate = $endDate; return $this; }
}
