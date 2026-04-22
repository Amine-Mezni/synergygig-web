<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\CommunityGroupRepository;

#[ORM\Entity(repositoryClass: CommunityGroupRepository::class)]
#[ORM\Table(name: 'community_groups')]
class CommunityGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: Post::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupMember::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $groupMembers;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->groupMembers = new ArrayCollection();
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

    public function getPosts(): Collection { return $this->posts; }
    public function getGroupMembers(): Collection { return $this->groupMembers; }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $image_base64 = null;

    public function getImage_base64(): ?string
    {
        return $this->image_base64;
    }

    public function setImage_base64(?string $image_base64): self
    {
        $this->image_base64 = $image_base64;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'creator_id', referencedColumnName: 'id')]
    private ?User $creator = null;

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $privacy = null;

    public function getPrivacy(): ?string
    {
        return $this->privacy;
    }

    public function setPrivacy(?string $privacy): self
    {
        $this->privacy = $privacy;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $member_count = null;

    public function getMember_count(): ?int
    {
        return $this->member_count;
    }

    public function getMemberCount(): ?int
    {
        return $this->getMember_count();
    }

    public function setMember_count(?int $member_count): self
    {
        $this->member_count = $member_count;
        return $this;
    }

    public function setMemberCount(?int $member_count): self
    {
        return $this->setMember_count($member_count);
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
