<?php

namespace App\Model\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\Security\Passwords;

#[ORM\Entity]
#[ORM\Table(name: "users")]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 50)]
    private string $role;

    public const
        ROLE_ADMIN = 'admin',
        ROLE_AUTHOR = 'author',
        ROLE_READER = 'reader';

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $password): void
    {
        $passwords = new Passwords();
        $this->passwordHash = $passwords->hash($password);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function getData(): array
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'name' => $this->getName(),
            'role' => $this->getRole(),
        ];
    }

    public function hasRole(string $role): bool
    {
        return $this->getRole() === $role;
    }
}