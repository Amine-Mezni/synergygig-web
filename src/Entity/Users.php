<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\User_skills;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $full_name;

    #[ORM\Column(type: "string", length: 100, unique: true)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string")]
    private string $role;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\OneToMany(mappedBy: "created_by", targetEntity: Offers::class)]
    private Collection $offerss;

    #[ORM\OneToMany(mappedBy: "applicant_id", targetEntity: Applications::class)]
    private Collection $applicationss;

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: User_skills::class)]
    private Collection $user_skillss;

    public function __construct()
    {
        $this->offerss = new ArrayCollection();
        $this->applicationss = new ArrayCollection();
        $this->user_skillss = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $value): self
    {
        $this->id = $value;
        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->full_name;
    }

    public function getFull_name(): ?string
    {
        return $this->full_name;
    }

    public function setFullName(string $value): self
    {
        $this->full_name = $value;
        return $this;
    }

    public function setFull_name(string $value): self
    {
        $this->full_name = $value;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $value): self
    {
        $this->email = $value;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $value): self
    {
        $this->password = $value;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $value): self
    {
        $this->role = $value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function setCreated_at(\DateTimeInterface $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roleMap = [
            'ADMIN' => 'ROLE_ADMIN',
            'HR' => 'ROLE_HR',
            'EMPLOYEE' => 'ROLE_EMPLOYEE',
            'GIG_WORKER' => 'ROLE_GIG_WORKER',
        ];

        $symfonyRole = $roleMap[$this->role] ?? 'ROLE_USER';

        return [$symfonyRole];
    }

    public function eraseCredentials(): void
    {
        // rien à effacer pour l’instant
    }

    public function getOfferss(): Collection
    {
        return $this->offerss;
    }

    public function addOffers(Offers $offers): self
    {
        if (!$this->offerss->contains($offers)) {
            $this->offerss[] = $offers;
            $offers->setCreatedBy($this);
        }

        return $this;
    }

    public function removeOffers(Offers $offers): self
    {
        if ($this->offerss->removeElement($offers)) {
            if ($offers->getCreatedBy() === $this) {
                $offers->setCreatedBy(null);
            }
        }

        return $this;
    }

    public function getApplicationss(): Collection
    {
        return $this->applicationss;
    }

    public function addApplications(Applications $applications): self
    {
        if (!$this->applicationss->contains($applications)) {
            $this->applicationss[] = $applications;
            $applications->setApplicant_id($this);
        }

        return $this;
    }

    public function removeApplications(Applications $applications): self
    {
        if ($this->applicationss->removeElement($applications)) {
            if ($applications->getApplicant_id() === $this) {
                $applications->setApplicant_id(null);
            }
        }

        return $this;
    }

    public function getUser_skillss(): Collection
    {
        return $this->user_skillss;
    }

    public function addUser_skills(User_skills $user_skills): self
    {
        if (!$this->user_skillss->contains($user_skills)) {
            $this->user_skillss[] = $user_skills;
            $user_skills->setUser_id($this);
        }

        return $this;
    }

    public function removeUser_skills(User_skills $user_skills): self
    {
        if ($this->user_skillss->removeElement($user_skills)) {
            if ($user_skills->getUser_id() === $this) {
                $user_skills->setUser_id(null);
            }
        }

        return $this;
    }
}