<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email = '';

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string')]
    private string $password = '';

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'longtext', nullable: true)]
    private ?string $faceIdEncoding = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $emailVerifiedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: ChatRoom::class)]
    private Collection $chatRoomsCreated;

    #[ORM\ManyToMany(targetEntity: ChatRoom::class, mappedBy: 'members')]
    private Collection $chatRooms;

    #[ORM\OneToMany(mappedBy: 'initiator', targetEntity: Call::class)]
    private Collection $callsInitiated;

    #[ORM\OneToMany(mappedBy: 'recipient', targetEntity: Call::class)]
    private Collection $callsReceived;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->chatRoomsCreated = new ArrayCollection();
        $this->chatRooms = new ArrayCollection();
        $this->callsInitiated = new ArrayCollection();
        $this->callsReceived = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }
    public function getFullName(): string { return ($this->firstName ?? '') . ' ' . ($this->lastName ?? ''); }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function getFaceIdEncoding(): ?string { return $this->faceIdEncoding; }
    public function setFaceIdEncoding(?string $faceIdEncoding): self { $this->faceIdEncoding = $faceIdEncoding; return $this; }
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getRoles(): array { $roles = $this->roles; $roles[] = 'ROLE_USER'; return array_unique($roles); }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }
    public function getSalt(): ?string { return null; }
    public function eraseCredentials(): void {}
    public function getUserIdentifier(): string { return $this->email; }
    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function getEmailVerifiedAt(): ?\DateTimeInterface { return $this->emailVerifiedAt; }
    public function setEmailVerifiedAt(?\DateTimeInterface $emailVerifiedAt): self { $this->emailVerifiedAt = $emailVerifiedAt; return $this; }
}
