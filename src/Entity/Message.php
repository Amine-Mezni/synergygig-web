<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ChatRoom $chatRoom;

    #[ORM\Column(type: 'longtext')]
    private string $content = '';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $attachments = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $reactionEmojis = null; // {"😂": [1,2,3], "❤️": [4,5]}

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isPinned = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isDeleted = false;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getChatRoom(): ChatRoom { return $this->chatRoom; }
    public function setChatRoom(ChatRoom $chatRoom): self { $this->chatRoom = $chatRoom; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): self { $this->content = $content; return $this; }
    public function getAttachments(): ?array { return $this->attachments; }
    public function setAttachments(?array $attachments): self { $this->attachments = $attachments; return $this; }
    public function getReactionEmojis(): ?array { return $this->reactionEmojis; }
    public function setReactionEmojis(?array $reactionEmojis): self { $this->reactionEmojis = $reactionEmojis; return $this; }
    public function isPinned(): bool { return $this->isPinned; }
    public function setIsPinned(bool $isPinned): self { $this->isPinned = $isPinned; return $this; }
    public function isDeleted(): bool { return $this->isDeleted; }
    public function setIsDeleted(bool $isDeleted): self { $this->isDeleted = $isDeleted; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
