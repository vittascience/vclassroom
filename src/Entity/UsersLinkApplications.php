<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

#[ORM\Entity(repositoryClass: "Classroom\Repository\UsersLinkApplicationsRepository")]
#[ORM\Table(name: "classroom_users_link_applications")]
class UsersLinkApplications
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: "Classroom\Entity\Applications")]
    #[ORM\JoinColumn(name: "application_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $application;

    #[ORM\Column(name: "max_activities_per_teachers", type: "integer", nullable: true)]
    private $maxActivitiesPerTeachers;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getApplication()
    {
        return $this->application->getId();
    }

    public function setApplication(Applications $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getmaxActivitiesPerTeachers()
    {
        return $this->maxActivitiesPerTeachers;
    }

    public function setmaxActivitiesPerTeachers(int $maximum): self
    {
        $this->maxActivitiesPerTeachers = $maximum;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'application' => $this->getApplication(),
            'user_id' => $this->getUser(),
            'max_activities_per_teachers' => $this->getmaxActivitiesPerTeachers()
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
