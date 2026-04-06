<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\User_skills;

#[ORM\Entity]
class Skills
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 100)]
    private string $name;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    #[ORM\OneToMany(mappedBy: "skill_id", targetEntity: User_skills::class)]
    private Collection $user_skillss;

        public function getUser_skillss(): Collection
        {
            return $this->user_skillss;
        }
    
        public function addUser_skills(User_skills $user_skills): self
        {
            if (!$this->user_skillss->contains($user_skills)) {
                $this->user_skillss[] = $user_skills;
                $user_skills->setSkill_id($this);
            }
    
            return $this;
        }
    
        public function removeUser_skills(User_skills $user_skills): self
        {
            if ($this->user_skillss->removeElement($user_skills)) {
                // set the owning side to null (unless already changed)
                if ($user_skills->getSkill_id() === $this) {
                    $user_skills->setSkill_id(null);
                }
            }
    
            return $this;
        }
}
