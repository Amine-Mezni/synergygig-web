<?php

namespace App\Entity;

use App\Repository\CallSignalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallSignalRepository::class)]
#[ORM\Table(name: 'call_signals')]
class CallSignal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Call::class, inversedBy: 'signals')]
    #[ORM\JoinColumn(name: 'call_id', referencedColumnName: 'id', nullable: false)]
    private ?Call $call = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'from_user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $fromUser = null;

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $signalType = null;

    #[ORM\Column(type: 'text')]
    private ?string $payload = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getCall(): ?Call { return $this->call; }
    public function setCall(?Call $call): self { $this->call = $call; return $this; }

    public function getFromUser(): ?User { return $this->fromUser; }
    public function setFromUser(?User $fromUser): self { $this->fromUser = $fromUser; return $this; }

    public function getSignalType(): ?string { return $this->signalType; }
    public function setSignalType(?string $signalType): self { $this->signalType = $signalType; return $this; }

    public function getPayload(): ?string { return $this->payload; }
    public function setPayload(?string $payload): self { $this->payload = $payload; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
