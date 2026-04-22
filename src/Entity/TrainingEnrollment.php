<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'training_enrollments')]
#[ORM\UniqueConstraint(columns: ['course_id', 'user_id'])]
class TrainingEnrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private TrainingCourse $course;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 50)]
    private string $status = 'ENROLLED'; // ENROLLED, IN_PROGRESS, COMPLETED, DROPPED

    #[ORM\Column(type: 'integer')]
    private int $progress = 0; // 0-100

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $enrolledAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function __construct()
    {
        $this->enrolledAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getCourse(): TrainingCourse { return $this->course; }
    public function setCourse(TrainingCourse $course): self { $this->course = $course; return $this; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getProgress(): int { return $this->progress; }
    public function setProgress(int $progress): self { $this->progress = $progress; return $this; }
    public function getScore(): ?float { return $this->score; }
    public function setScore(?float $score): self { $this->score = $score; return $this; }
}
