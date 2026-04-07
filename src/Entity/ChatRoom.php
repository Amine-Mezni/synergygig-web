<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'chat_rooms')]
class ChatRoom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type = 'DIRECT'; // DIRECT, GROUP, TEAM, ANNOUNCEMENT

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isArchived = false;

    #[ORM\OneToMany(mappedBy: 'chatRoom', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'chatRooms')]
    #[ORM\JoinTable(name: 'chat_room_members')]
    private Collection $members;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getCreatedBy(): User { return $this->createdBy; }
    public function setCreatedBy(User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function isArchived(): bool { return $this->isArchived; }
    public function setIsArchived(bool $isArchived): self { $this->isArchived = $isArchived; return $this; }
    public function getMessages(): Collection { return $this->messages; }
    public function addMessage(Message $message): self { if (!$this->messages->contains($message)) { $this->messages->add($message); $message->setChatRoom($this); } return $this; }
    public function removeMessage(Message $message): self { if ($this->messages->removeElement($message)) { if ($message->getChatRoom() === $this) { $message->setChatRoom(null); } } return $this; }
    public function getMembers(): Collection { return $this->members; }
    public function addMember(User $member): self { if (!$this->members->contains($member)) { $this->members->add($member); } return $this; }
}
