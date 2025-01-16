<?php

namespace Classroom\Entity;

use User\Entity\User;
use Classroom\Entity\Groups;
use Doctrine\ORM\Mapping as ORM;
use Classroom\Repository\UsersLinkGroupsRepository;

#[ORM\Entity(repositoryClass: UsersLinkGroupsRepository::class)]
#[ORM\Table(name: "classroom_users_link_groups")]
class UsersLinkGroups implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: Groups::class)]
    #[ORM\JoinColumn(name: "group_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $group;

    #[ORM\Column(name: "rights", type: "integer", length: 2, nullable: true)]
    private $rights;

    public function getUser()
    {
        return $this->user->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getGroup()
    {
        return $this->group->getId();
    }

    public function setGroup(Groups $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getRights(): ?int
    {
        return $this->rights;
    }

    public function setRights(int $rights): self
    {
        $this->rights = $rights;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'group_id' => $this->getGroup(),
            'user_id' => $this->getUser(),
            'rights' => $this->getRights()
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
