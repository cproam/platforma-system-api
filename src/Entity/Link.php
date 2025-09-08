<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'links')]
class Link
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Franchise::class, inversedBy: 'links')]
    #[ORM\JoinColumn(nullable: false)]
    private Franchise $franchise;

    #[ORM\Column(type: 'string', length: 255)]
    private string $url;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $label = null;

    public function __construct(Franchise $franchise, string $url, ?string $label = null)
    {
        $this->franchise = $franchise;
        $this->url = $url;
        $this->label = $label;
    }

    public function getId(): ?int { return $this->id; }
    public function getFranchise(): Franchise { return $this->franchise; }
    public function setFranchise(Franchise $f): void { $this->franchise = $f; }
    public function getUrl(): string { return $this->url; }
    public function setUrl(string $u): void { $this->url = $u; }
    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $l): void { $this->label = $l; }
}
