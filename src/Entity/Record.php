<?php

namespace App\Entity;

use App\Repository\RecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecordRepository::class)]
class Record
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column]
    private ?int $amount = null;

    #[ORM\Column(length: 255)]
    private ?string $telegram_user = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $truckNumber = null;

    #[ORM\Column]
    private ?int $mileage = null;

    public function __construct()
    {
        $this->date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTelegramUser(): ?string
    {
        return $this->telegram_user;
    }

    public function setTelegramUser(string $telegram_user): static
    {
        $this->telegram_user = $telegram_user;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'date' => $this->date->format('Y-m-d H:i:s'),
            'amount' => $this->amount,
            'telegram_user' => $this->telegram_user,
            'type' => $this->type,
            'truck_number' => $this->truckNumber,
            'mileage' => $this->mileage,
        ];
    }

    public function getTruckNumber(): ?string
    {
        return $this->truckNumber;
    }

    public function setTruckNumber(string $truckNumber): static
    {
        $this->truckNumber = $truckNumber;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }
}
