<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Skills;

#[ORM\Entity]
class User_skills
{

    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "user_skillss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: Skills::class, inversedBy: "user_skillss")]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Skills $skill_id;

    #[ORM\Column(type: "string")]
    private string $level;

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getSkill_id()
    {
        return $this->skill_id;
    }

    public function setSkill_id($value)
    {
        $this->skill_id = $value;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($value)
    {
        $this->level = $value;
    }
}
