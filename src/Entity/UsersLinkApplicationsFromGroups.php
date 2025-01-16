<?php

namespace Classroom\Entity;

use User\Entity\User;
use Classroom\Entity\Groups;
use Doctrine\ORM\Mapping as ORM;
use Classroom\Entity\Applications;

#[ORM\Entity(repositoryClass: "Classroom\Repository\UsersLinkApplicationsFromGroupsRepository")]
#[ORM\Table(name: "classroom_users_link_applications_from_groups")]
class UsersLinkApplicationsFromGroups
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: "Classroom\Entity\Applications")]
    #[ORM\JoinColumn(name: "application_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $application;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: "Classroom\Entity\Groups")]
    #[ORM\JoinColumn(name: "group_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $group;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): Applications
    {
        return $this->application;
    }

    public function setApplication(Applications $app): self
    {
        $this->application = $app;
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

    public function getGroup(): Groups
    {
        return $this->group;
    }

    public function setGroup(Groups $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'application' => $this->getApplication(),
            'user' => $this->getUser(),
            'group' => $this->getGroup()
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
