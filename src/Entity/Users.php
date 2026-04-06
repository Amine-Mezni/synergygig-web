<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\User_skills;

#[ORM\Entity]
class Users
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $full_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $password;

    #[ORM\Column(type: "string")]
    private string $role;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getFull_name()
    {
        return $this->full_name;
    }

    public function setFull_name($value)
    {
        $this->full_name = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    #[ORM\OneToMany(mappedBy: "created_by", targetEntity: Offers::class)]
    private Collection $offerss;

        public function getOfferss(): Collection
        {
            return $this->offerss;
        }
    
        public function addOffers(Offers $offers): self
        {
            if (!$this->offerss->contains($offers)) {
                $this->offerss[] = $offers;
                $offers->setCreated_by($this);
            }
    
            return $this;
        }
    
        public function removeOffers(Offers $offers): self
        {
            if ($this->offerss->removeElement($offers)) {
                // set the owning side to null (unless already changed)
                if ($offers->getCreated_by() === $this) {
                    $offers->setCreated_by(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "applicant_id", targetEntity: Applications::class)]
    private Collection $applicationss;

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
                // set the owning side to null (unless already changed)
                if ($applications->getApplicant_id() === $this) {
                    $applications->setApplicant_id(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "user_id", targetEntity: User_skills::class)]
    private Collection $user_skillss;

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
                // set the owning side to null (unless already changed)
                if ($user_skills->getUser_id() === $this) {
                    $user_skills->setUser_id(null);
                }
            }
    
            return $this;
        }
}
