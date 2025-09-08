<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Franchise::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private Franchise $franchise;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(Franchise $franchise, string $content)
    {
        $this->franchise = $franchise;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int { return $this->id; }
    public function getFranchise(): Franchise { return $this->franchise; }
    public function setFranchise(Franchise $f): void { $this->franchise = $f; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $c): void { $this->content = $c; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
