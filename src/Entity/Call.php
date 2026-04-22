<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'calls')]
class Call
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $initiator;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $recipient;

    #[ORM\Column(length: 50)]
    private string $type = 'AUDIO'; // AUDIO, VIDEO, SCREEN_SHARE

    #[ORM\Column(length: 50)]
    private string $status = 'INITIATING'; // INITIATING, RINGING, CONNECTED, ENDED, MISSED

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null; // seconds

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $recordingUrl = null;

    #[ORM\Column(type: 'longtext', nullable: true)]
    private ?string $transcript = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getInitiator(): User { return $this->initiator; }
    public function setInitiator(User $initiator): self { $this->initiator = $initiator; return $this; }
    public function getRecipient(): User { return $this->recipient; }
    public function setRecipient(User $recipient): self { $this->recipient = $recipient; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): self { $this->type = $type; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(?int $duration): self { $this->duration = $duration; return $this; }
    public function getRecordingUrl(): ?string { return $this->recordingUrl; }
    public function setRecordingUrl(?string $recordingUrl): self { $this->recordingUrl = $recordingUrl; return $this; }
    public function getTranscript(): ?string { return $this->transcript; }
    public function setTranscript(?string $transcript): self { $this->transcript = $transcript; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getEndedAt(): ?\DateTimeInterface { return $this->endedAt; }
    public function setEndedAt(?\DateTimeInterface $endedAt): self { $this->endedAt = $endedAt; return $this; }
}
