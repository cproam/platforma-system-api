<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'packages')]
class Package
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(enumType: PackageType::class)]
    private PackageType $type;

    #[ORM\Column(type: 'integer')]
    private int $leadCount;

    public function __construct(string $name, PackageType $type, int $leadCount)
    {
        $this->name = $name;
        $this->type = $type;
        $this->leadCount = $leadCount;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    public function getType(): PackageType { return $this->type; }
    public function setType(PackageType $type): void { $this->type = $type; }
    public function getLeadCount(): int { return $this->leadCount; }
    public function setLeadCount(int $leadCount): void { $this->leadCount = $leadCount; }
}
