<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_users_ip', columns: ['ipAddress'])]
#[ORM\Index(name: 'idx_users_email', columns: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $passwordHash;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'boolean')]
    private bool $banned = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true, unique: true)]
    private ?string $anonKey = null;

    public function __construct(string $email, string $passwordHash, Role $role, ?string $ipAddress = null, bool $banned = false, ?string $anonKey = null)
    {
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role;
        $this->ipAddress = $ipAddress;
        $this->banned = $banned;
        $this->anonKey = $anonKey;
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $passwordHash): void { $this->passwordHash = $passwordHash; }
    public function getRole(): Role { return $this->role; }
    public function setRole(Role $role): void { $this->role = $role; }

    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function setIpAddress(?string $ip): void { $this->ipAddress = $ip; }

    public function isBanned(): bool { return $this->banned; }
    public function setBanned(bool $banned): void { $this->banned = $banned; }

    public function getAnonKey(): ?string { return $this->anonKey; }
    public function setAnonKey(?string $anonKey): void { $this->anonKey = $anonKey; }
}
