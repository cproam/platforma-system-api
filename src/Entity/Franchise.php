<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'franchises')]
class Franchise
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 32, unique: true)]
    private string $code;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(enumType: FranchiseStatus::class)]
    private FranchiseStatus $status = FranchiseStatus::UNPUBLISHED;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $webhookUrl = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $telegramId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $cost = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $investment = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $paybackPeriod = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $monthlyIncome = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $unpublishedAt = null;

    #[ORM\OneToMany(mappedBy: 'franchise', targetEntity: Comment::class, cascade: ['persist', 'remove'])]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'franchise', targetEntity: Link::class, cascade: ['persist', 'remove'])]
    private Collection $links;

    public function __construct(string $name, string $code)
    {
        $this->name = $name;
        $this->code = $code;
        $this->createdAt = new \DateTimeImmutable('now');
        $this->comments = new ArrayCollection();
        $this->links = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): void { $this->code = $code; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getStatus(): FranchiseStatus { return $this->status; }
    public function setStatus(FranchiseStatus $status): void { 
        $this->status = $status;
        if ($status === FranchiseStatus::PUBLISHED && $this->publishedAt === null) {
            $this->publishedAt = new \DateTimeImmutable('now');
        }
        if ($status === FranchiseStatus::UNPUBLISHED) {
            $this->unpublishedAt = new \DateTimeImmutable('now');
        }
    }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): void { $this->email = $v; }
    public function getWebhookUrl(): ?string { return $this->webhookUrl; }
    public function setWebhookUrl(?string $v): void { $this->webhookUrl = $v; }
    public function getTelegramId(): ?string { return $this->telegramId; }
    public function setTelegramId(?string $v): void { $this->telegramId = $v; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): void { $this->description = $v; }
    public function getCost(): ?float { return $this->cost; }
    public function setCost(?float $v): void { $this->cost = $v; }
    public function getInvestment(): ?float { return $this->investment; }
    public function setInvestment(?float $v): void { $this->investment = $v; }
    public function getPaybackPeriod(): ?float { return $this->paybackPeriod; }
    public function setPaybackPeriod(?float $v): void { $this->paybackPeriod = $v; }
    public function getMonthlyIncome(): ?float { return $this->monthlyIncome; }
    public function setMonthlyIncome(?float $v): void { $this->monthlyIncome = $v; }

    public function getPublishedDurationDays(): ?int {
        if ($this->publishedAt === null) { return null; }
        $end = $this->unpublishedAt ?? new \DateTimeImmutable('now');
        return $this->publishedAt->diff($end)->days;
    }

    /** @return Collection<int, Comment> */
    public function getComments(): Collection { return $this->comments; }
    public function addComment(Comment $c): void { if (!$this->comments->contains($c)) { $this->comments->add($c); $c->setFranchise($this); } }
    public function removeComment(Comment $c): void { $this->comments->removeElement($c); }

    /** @return Collection<int, Link> */
    public function getLinks(): Collection { return $this->links; }
    public function addLink(Link $l): void { if (!$this->links->contains($l)) { $this->links->add($l); $l->setFranchise($this); } }
    public function removeLink(Link $l): void { $this->links->removeElement($l); }
}
