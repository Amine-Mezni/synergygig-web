<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'attendances')]
class Attendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $date;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $checkInTime = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $checkOutTime = null;

    #[ORM\Column(length: 50)]
    private string $status = 'PRESENT'; // PRESENT, ABSENT, LATE, HALF_DAY

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $date): self { $this->date = $date; return $this; }
    public function getCheckInTime(): ?\DateTimeInterface { return $this->checkInTime; }
    public function setCheckInTime(?\DateTimeInterface $checkInTime): self { $this->checkInTime = $checkInTime; return $this; }
    public function getCheckOutTime(): ?\DateTimeInterface { return $this->checkOutTime; }
    public function setCheckOutTime(?\DateTimeInterface $checkOutTime): self { $this->checkOutTime = $checkOutTime; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}
