<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\CustomerType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 20, enumType: CustomerType::class)]
    private ?CustomerType $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getType(): ?CustomerType
    {
        return $this->type;
    }

    public function setType(CustomerType $type): static
    {
        $this->type = $type;

        return $this;
    }
}
