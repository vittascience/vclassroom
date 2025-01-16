<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Classroom\Entity\Applications;
use Classroom\Entity\Groups;

#[ORM\Entity(repositoryClass: "Classroom\Repository\GroupsLinkApplicationsRepository")]
#[ORM\Table(name: "classroom_groups_link_applications")]
class GroupsLinkApplications
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: "Classroom\Entity\Groups")]
    #[ORM\JoinColumn(name: "group_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $group;

    #[ORM\ManyToOne(targetEntity: "Classroom\Entity\Applications")]
    #[ORM\JoinColumn(name: "application_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $application;

    #[ORM\Column(name: "max_activities_per_groups", type: "integer", nullable: true)]
    private $maxActivitiesPerGroups;

    #[ORM\Column(name: "max_activities_per_teachers", type: "integer", nullable: true)]
    private $maxActivitiesPerTeachers;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getApplication(): Applications
    {
        return $this->application;
    }

    public function setApplication(Applications $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getmaxActivitiesPerGroups(): ?int
    {
        return $this->maxActivitiesPerGroups;
    }

    public function setmaxActivitiesPerGroups(int $maximum): self
    {
        $this->maxActivitiesPerGroups = $maximum;
        return $this;
    }

    public function getmaxActivitiesPerTeachers(): ?int
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
            'group_id' => $this->getGroup(),
            'application_id' => $this->getApplication(),
            'max_activities_per_group' => $this->getmaxActivitiesPerGroups(),
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
