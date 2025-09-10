<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'log_entries')]
#[ORM\Index(name: 'idx_logs_created_at', columns: ['createdAt'])]
class LogEntry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 10)]
    private string $method;

    #[ORM\Column(type: 'string', length: 255)]
    private string $path;

    #[ORM\Column(type: 'integer')]
    private int $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $action = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $object = null;

    public function __construct(string $method, string $path, int $status, ?string $message = null, ?int $userId = null, ?string $ip = null, ?string $action = null, ?string $object = null)
    {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->method = $method;
        $this->path = $path;
        $this->status = $status;
        $this->message = $message;
        $this->userId = $userId;
        $this->ip = $ip;
        $this->action = $action;
        $this->object = $object;
    }

    public function getId(): ?int { return $this->id; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getMethod(): string { return $this->method; }
    public function getPath(): string { return $this->path; }
    public function getStatus(): int { return $this->status; }
    public function getMessage(): ?string { return $this->message; }
    public function getUserId(): ?int { return $this->userId; }
    public function getIp(): ?string { return $this->ip; }
    public function getAction(): ?string { return $this->action; }
    public function getObject(): ?string { return $this->object; }
}
