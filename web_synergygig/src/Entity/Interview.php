<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InterviewRepository;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
#[ORM\Table(name: 'interviews')]
class Interview
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

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'organizer_id', referencedColumnName: 'id')]
    private ?User $organizer = null;

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): self
    {
        $this->organizer = $organizer;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'candidate_id', referencedColumnName: 'id')]
    private ?User $candidate = null;

    public function getCandidate(): ?User
    {
        return $this->candidate;
    }

    public function setCandidate(?User $candidate): self
    {
        $this->candidate = $candidate;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $date_time = null;

    public function getDate_time(): ?\DateTimeInterface
    {
        return $this->date_time;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return $this->getDate_time();
    }

    public function setDate_time(\DateTimeInterface $date_time): self
    {
        $this->date_time = $date_time;
        return $this;
    }

    public function setDateTime(\DateTimeInterface $date_time): self
    {
        return $this->setDate_time($date_time);
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $meet_link = null;

    public function getMeet_link(): ?string
    {
        return $this->meet_link;
    }

    public function getMeetLink(): ?string
    {
        return $this->getMeet_link();
    }

    public function setMeet_link(?string $meet_link): self
    {
        $this->meet_link = $meet_link;
        return $this;
    }

    public function setMeetLink(?string $meet_link): self
    {
        return $this->setMeet_link($meet_link);
    }

    #[ORM\ManyToOne(targetEntity: JobApplication::class)]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id')]
    private ?JobApplication $application = null;

    public function getApplication(): ?JobApplication
    {
        return $this->application;
    }

    public function setApplication(?JobApplication $application): self
    {
        $this->application = $application;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(name: 'offer_id', referencedColumnName: 'id')]
    private ?Offer $offer = null;

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): self
    {
        $this->offer = $offer;
        return $this;
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
