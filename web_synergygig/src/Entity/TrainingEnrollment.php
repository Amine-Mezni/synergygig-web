<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\TrainingEnrollmentRepository;

#[ORM\Entity(repositoryClass: TrainingEnrollmentRepository::class)]
#[ORM\Table(name: 'training_enrollments')]
class TrainingEnrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'enrollment', targetEntity: TrainingCertificate::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $certificates;

    public function __construct()
    {
        $this->certificates = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getCertificates(): Collection { return $this->certificates; }

    #[ORM\ManyToOne(targetEntity: TrainingCourse::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(name: 'course_id', referencedColumnName: 'id')]
    private ?TrainingCourse $course = null;

    public function getCourse(): ?TrainingCourse
    {
        return $this->course;
    }

    public function setCourse(?TrainingCourse $course): self
    {
        $this->course = $course;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $progress = null;

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): self
    {
        $this->progress = $progress;
        return $this;
    }

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $score = null;

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $enrolled_at = null;

    public function getEnrolled_at(): ?\DateTimeInterface
    {
        return $this->enrolled_at;
    }

    public function getEnrolledAt(): ?\DateTimeInterface
    {
        return $this->getEnrolled_at();
    }

    public function setEnrolled_at(\DateTimeInterface $enrolled_at): self
    {
        $this->enrolled_at = $enrolled_at;
        return $this;
    }

    public function setEnrolledAt(\DateTimeInterface $enrolled_at): self
    {
        return $this->setEnrolled_at($enrolled_at);
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completed_at = null;

    public function getCompleted_at(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->getCompleted_at();
    }

    public function setCompleted_at(?\DateTimeInterface $completed_at): self
    {
        $this->completed_at = $completed_at;
        return $this;
    }

    public function setCompletedAt(?\DateTimeInterface $completed_at): self
    {
        return $this->setCompleted_at($completed_at);
    }

}
