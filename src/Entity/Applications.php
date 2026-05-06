<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Users;
use App\Entity\Offers;
use App\Entity\Contracts;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Applications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Offers::class, inversedBy: "applicationss")]
    #[ORM\JoinColumn(name: 'offer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Offers $offer_id = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "applicationss")]
    #[ORM\JoinColumn(name: 'applicant_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Users $applicant_id = null;

    #[ORM\Column(type: "string", length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $applied_at = null;

    #[ORM\OneToMany(mappedBy: "application_id", targetEntity: Contracts::class)]
    private Collection $contractss;

    public function __construct()
    {
        $this->contractss = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOffer_id(): ?Offers
    {
        return $this->offer_id;
    }

    public function setOffer_id(?Offers $value): self
    {
        $this->offer_id = $value;
        return $this;
    }

    public function getApplicant_id(): ?Users
    {
        return $this->applicant_id;
    }

    public function setApplicant_id(?Users $value): self
    {
        $this->applicant_id = $value;
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

    public function getApplied_at(): ?\DateTimeInterface
    {
        return $this->applied_at;
    }

  

    public function removeContracts(Contracts $contracts): self
    {
        $this->contractss->removeElement($contracts);

        return $this;
    }
}