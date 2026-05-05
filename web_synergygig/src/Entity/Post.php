<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\PostRepository;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Reaction::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $reactions;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Bookmark::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $bookmarks;

    public function __construct()
    {
        $this->reactions = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->bookmarks = new ArrayCollection();
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

    public function getReactions(): Collection { return $this->reactions; }
    public function getComments(): Collection { return $this->comments; }
    public function getBookmarks(): Collection { return $this->bookmarks; }

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id')]
    private ?User $author = null;

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $content = null;

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $visibility = null;

    public function getVisibility(): ?string
    {
        return $this->visibility;
    }

    public function setVisibility(?string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: CommunityGroup::class, inversedBy: 'posts')]
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $likes_count = null;

    public function getLikes_count(): ?int
    {
        return $this->likes_count;
    }

    public function getLikesCount(): ?int
    {
        return $this->getLikes_count();
    }

    public function setLikes_count(?int $likes_count): self
    {
        $this->likes_count = $likes_count;
        return $this;
    }

    public function setLikesCount(?int $likes_count): self
    {
        return $this->setLikes_count($likes_count);
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $comments_count = null;

    public function getComments_count(): ?int
    {
        return $this->comments_count;
    }

    public function getCommentsCount(): ?int
    {
        return $this->getComments_count();
    }

    public function setComments_count(?int $comments_count): self
    {
        $this->comments_count = $comments_count;
        return $this;
    }

    public function setCommentsCount(?int $comments_count): self
    {
        return $this->setComments_count($comments_count);
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $shares_count = null;

    public function getShares_count(): ?int
    {
        return $this->shares_count;
    }

    public function getSharesCount(): ?int
    {
        return $this->getShares_count();
    }

    public function setShares_count(?int $shares_count): self
    {
        $this->shares_count = $shares_count;
        return $this;
    }

    public function setSharesCount(?int $shares_count): self
    {
        return $this->setShares_count($shares_count);
    }

}
