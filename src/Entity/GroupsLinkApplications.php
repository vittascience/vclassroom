<?php

namespace Classroom\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;
use Classroom\Entity\Applications;
use Classroom\Entity\Groups;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\GroupsLinkApplicationsRepository")
 * @ORM\Table(name="classroom_groups_link_applications")
 */
class GroupsLinkApplications
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\Groups")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Groups
     */
    private $group;

    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\Applications")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Applications
     */
    private $application;


    /**
     * @ORM\Column(name="max_activities_per_groups", type="integer", nullable=true)
     * @var integer
     */
    private $maxActivitiesPerGroups;


    /**
     * @ORM\Column(name="max_activities_per_teachers", type="integer", nullable=true)
     * @var integer
     */
    private $maxActivitiesPerTeachers;


    /**
     * @return Int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Groups
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Groups $group
     * @return GroupsLinkApplications
     */
    public function setGroup(Groups $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Applications application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Applications
     * @return GroupsLinkApplications
     */
    public function setApplication(Applications $application): self
    {
        $this->application = $application;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxActivitiesPerGroups()
    {
        return $this->maxActivitiesPerGroups;
    }

    /**
     * @param Integer $maximum
     * @return GroupsLinkApplications
     */
    public function setmaxActivitiesPerGroups(Int $maximum): self
    {
        $this->maxActivitiesPerGroups = $maximum;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxActivitiesPerTeachers()
    {
        return $this->maxActivitiesPerTeachers;
    }

    /**
     * @param Integer $maximum
     * @return GroupsLinkApplications
     */
    public function setmaxActivitiesPerTeachers(Int $maximum): self
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
