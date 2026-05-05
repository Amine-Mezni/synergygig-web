<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\GroupMemberRepository;

#[ORM\Entity(repositoryClass: GroupMemberRepository::class)]
#[ORM\Table(name: 'group_members')]
class GroupMember
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

    #[ORM\ManyToOne(targetEntity: CommunityGroup::class, inversedBy: 'groupMembers')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    private ?CommunityGroup $group = null;

    public function getGroup(): ?CommunityGroup
    {
        return $this->group;
    }

    public function setGroup(?CommunityGroup $group): self
    {
        $this->group = $group;
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
    private ?string $role = null;

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $joined_at = null;

    public function getJoined_at(): ?\DateTimeInterface
    {
        return $this->joined_at;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->getJoined_at();
    }

    public function setJoined_at(\DateTimeInterface $joined_at): self
    {
        $this->joined_at = $joined_at;
        return $this;
    }

    public function setJoinedAt(\DateTimeInterface $joined_at): self
    {
        return $this->setJoined_at($joined_at);
    }

}
