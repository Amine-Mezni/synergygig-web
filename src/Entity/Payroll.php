<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'payrolls')]
class Payroll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $payrollDate;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $baseSalary = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $bonus = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $deductions = '0';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $netAmount = '0';

    #[ORM\Column(length: 50)]
    private string $status = 'PENDING'; // PENDING, PROCESSED, PAID, FAILED

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getPayrollDate(): \DateTimeInterface { return $this->payrollDate; }
    public function setPayrollDate(\DateTimeInterface $payrollDate): self { $this->payrollDate = $payrollDate; return $this; }
    public function getBaseSalary(): string { return $this->baseSalary; }
    public function setBaseSalary(string $baseSalary): self { $this->baseSalary = $baseSalary; return $this; }
    public function getNetAmount(): string { return $this->netAmount; }
    public function setNetAmount(string $netAmount): self { $this->netAmount = $netAmount; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
}
