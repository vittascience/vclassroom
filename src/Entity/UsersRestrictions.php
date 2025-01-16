<?php

namespace Classroom\Entity;

use User\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Classroom\Repository\UsersRestrictionsRepository;

#[ORM\Entity(repositoryClass: UsersRestrictionsRepository::class)]
#[ORM\Table(name: "classroom_users_restrictions")]
class UsersRestrictions implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\Column(name: "date_begin", type: "datetime", nullable: true)]
    private ?\DateTime $dateBegin = null;

    #[ORM\Column(name: "date_end", type: "datetime", nullable: true)]
    private ?\DateTime $dateEnd = null;

    #[ORM\Column(name: "max_students", type: "integer", nullable: true)]
    private ?int $maxStudents = null;

    #[ORM\Column(name: "max_classrooms", type: "integer", nullable: true)]
    private ?int $maxClassrooms = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateBegin(): ?\DateTime
    {
        return $this->dateBegin;
    }

    public function setDateBegin(\DateTime $dateBegin): self
    {
        $this->dateBegin = $dateBegin;
        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTime $dateEnd): self
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getMaxStudents(): ?int
    {
        return $this->maxStudents;
    }

    public function setMaxStudents(?int $maxStudents): self
    {
        $this->maxStudents = $maxStudents;
        return $this;
    }

    public function getMaxClassrooms(): ?int
    {
        return $this->maxClassrooms;
    }

    public function setMaxClassrooms(?int $maxClassrooms): self
    {
        $this->maxClassrooms = $maxClassrooms;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'dateBegin' => $this->getDateBegin(),
            'dateEnd' => $this->getDateEnd(),
            'maxStudents' => $this->getMaxStudents(),
            'maxClassrooms' => $this->getMaxClassrooms(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
