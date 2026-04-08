<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\AttendanceRepository;

#[ORM\Entity(repositoryClass: AttendanceRepository::class)]
#[ORM\Table(name: 'attendance')]
class Attendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
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

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $date = null;

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $check_in = null;

    public function getCheck_in(): ?\DateTimeInterface
    {
        return $this->check_in;
    }

    public function getCheckIn(): ?\DateTimeInterface
    {
        return $this->getCheck_in();
    }

    public function setCheck_in(?\DateTimeInterface $check_in): self
    {
        $this->check_in = $check_in;
        return $this;
    }

    public function setCheckIn(?\DateTimeInterface $check_in): self
    {
        return $this->setCheck_in($check_in);
    }

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $check_out = null;

    public function getCheck_out(): ?\DateTimeInterface
    {
        return $this->check_out;
    }

    public function getCheckOut(): ?\DateTimeInterface
    {
        return $this->getCheck_out();
    }

    public function setCheck_out(?\DateTimeInterface $check_out): self
    {
        $this->check_out = $check_out;
        return $this;
    }

    public function setCheckOut(?\DateTimeInterface $check_out): self
    {
        return $this->setCheck_out($check_out);
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->getCreated_at();
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        return $this->setCreated_at($created_at);
    }

}
