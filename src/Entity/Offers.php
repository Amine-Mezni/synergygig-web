<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;
use App\Entity\Applications;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Offers
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 150)]
    private ?string $title = null;

    #[ORM\Column(type: "text")]
    private ?string $description = null;

    #[ORM\Column(type: "string")]
    private ?string $type = null;

    #[ORM\Column(type: "string")]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "offerss")]
    #[ORM\JoinColumn(name: "created_by", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private ?Users $created_by = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $image_url = null;

    #[ORM\Column(type: "float")]
    private ?float $amount = null;

    #[ORM\OneToMany(mappedBy: "offer_id", targetEntity: Applications::class)]
    private Collection $applicationss;

    public function __construct()
    {
        $this->applicationss = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $value): self
    {
        $this->title = $value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $value): self
    {
        $this->type = $value;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $value): self
    {
        $this->status = $value;
        return $this;
    }

    public function getCreatedBy(): ?Users
    {
        return $this->created_by;
    }

    public function setCreatedBy(?Users $value): self
    {
        $this->created_by = $value;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $value): self
    {
        $this->created_at = $value;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $value): self
    {
        $this->image_url = $value;
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $value): self
    {
        $this->amount = $value;
        return $this;
    }

    public function getApplicationss(): Collection
    {
        return $this->applicationss;
    }

    public function addApplications(Applications $applications): self
    {
        if (!$this->applicationss->contains($applications)) {
            $this->applicationss[] = $applications;
            $applications->setOffer_id($this);
        }

        return $this;
    }

    public function removeApplications(Applications $applications): self
    {
        if ($this->applicationss->removeElement($applications)) {
            if ($applications->getOffer_id() === $this) {
                $applications->setOffer_id(null);
            }
        }

        return $this;
    }
}