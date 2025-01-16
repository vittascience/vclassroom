<?php

namespace Classroom\Entity;

use User\Entity\User;
use Classroom\Entity\Classroom;
use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Classroom\Repository\ClassroomLinkUserRepository;

#[ORM\Entity(repositoryClass: ClassroomLinkUserRepository::class)]
#[ORM\Table(name: "classroom_users_link_classrooms")]
class ClassroomLinkUser implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "id_user", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private User $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Classroom::class)]
    #[ORM\JoinColumn(name: "id_classroom", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private Classroom $classroom;

    #[ORM\Column(name: "rights", type: "integer", length: 2, nullable: true)]
    private int $rights;

    public function __construct(User $user, Classroom $classroom, int $rights = 0)
    {
        $this->setUser($user);
        $this->setClassroom($classroom);
        $this->setRights($rights);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        if ($user instanceof User) {
            $this->user = $user;
        } else {
            throw new EntityDataIntegrityException("user attribute needs to be an instance of User");
        }
    }

    public function getClassroom(): Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(Classroom $classroom): void
    {
        if ($classroom instanceof Classroom) {
            $this->classroom = $classroom;
        } else {
            throw new EntityDataIntegrityException("classroom attribute needs to be an instance of Classroom");
        }
    }

    public function getRights(): int
    {
        return $this->rights;
    }

    public function setRights(int $rights): void
    {
        if ($rights >= 0 && $rights <= 2) {
            $this->rights = $rights;
        } else {
            throw new EntityDataIntegrityException("id needs to be integer and between 0 and 2");
        }
    }

    public function jsonSerialize(): array
    {
        return [
            'user' => $this->getUser()->jsonSerialize(),
            'classroom' => $this->getClassroom()->jsonSerialize(),
            'rights' => $this->getRights(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded): self
    {
        $classInstance = new self(new User(), new Classroom("aaaaa"));
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
