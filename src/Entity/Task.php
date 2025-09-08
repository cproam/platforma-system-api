<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tasks')]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $createdBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    #[ORM\ManyToOne(targetEntity: Franchise::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Franchise $franchise = null;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $deadline;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'boolean')]
    private bool $seen = false;

    public function __construct(User $createdBy, User $assignedTo, string $description, \DateTimeImmutable $deadline, ?Franchise $franchise = null)
    {
        $this->createdBy = $createdBy;
        $this->assignedTo = $assignedTo;
        $this->description = $description;
        $this->deadline = $deadline;
        $this->franchise = $franchise;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int { return $this->id; }
    public function getCreatedBy(): User { return $this->createdBy; }
    public function getAssignedTo(): User { return $this->assignedTo; }
    public function setAssignedTo(User $u): void { $this->assignedTo = $u; }
    public function getFranchise(): ?Franchise { return $this->franchise; }
    public function setFranchise(?Franchise $f): void { $this->franchise = $f; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): void { $this->description = $d; }
    public function getDeadline(): \DateTimeImmutable { return $this->deadline; }
    public function setDeadline(\DateTimeImmutable $d): void { $this->deadline = $d; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function isSeen(): bool { return $this->seen; }
    public function setSeen(bool $seen): void { $this->seen = $seen; }
}
