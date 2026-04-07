<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'training_courses')]
class TrainingCourse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100)]
    private string $category = 'TECHNICAL'; // TECHNICAL, SOFT_SKILLS, COMPLIANCE, ONBOARDING, LEADERSHIP

    #[ORM\Column(length: 100)]
    private string $difficulty = 'BEGINNER'; // BEGINNER, INTERMEDIATE, ADVANCED

    #[ORM\Column(type: 'float')]
    private float $durationHours = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instructorName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $megaLink = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(type: 'integer')]
    private int $maxParticipants = 50;

    #[ORM\Column(length: 50)]
    private string $status = 'ACTIVE'; // DRAFT, ACTIVE, ARCHIVED

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\OneToMany(mappedBy: 'course', targetEntity: TrainingEnrollment::class, orphanRemoval: true)]
    private Collection $enrollments;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getDifficulty(): string { return $this->difficulty; }
    public function setDifficulty(string $difficulty): self { $this->difficulty = $difficulty; return $this; }
    public function getDurationHours(): float { return $this->durationHours; }
    public function setDurationHours(float $durationHours): self { $this->durationHours = $durationHours; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getEnrollments(): Collection { return $this->enrollments; }
}
