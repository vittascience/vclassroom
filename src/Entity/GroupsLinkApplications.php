<?php

namespace Classroom\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;
use Classroom\Entity\Applications;
use Classroom\Entity\Groups;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\GroupsLinkApplicationsRepository")
 * @ORM\Table(name="groups_link_applications")
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
     * @ORM\Column(name="date_begin", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $dateBegin = null;

    /**
     * @ORM\Column(name="date_end", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $dateEnd = null;

    /**
     * @ORM\Column(name="max_students_per_groups", type="integer", nullable=true)
     * @var integer
     */
    private $maxStudentsPerGroups;

    /**
     * @ORM\Column(name="max_teachers_per_groups", type="integer", nullable=true)
     * @var integer
     */
    private $maxTeachersPerGroups;


    /**
     * @ORM\Column(name="max_students_per_teachers", type="integer", nullable=true)
     * @var integer
     */
    private $maxStudentsPerTeachers;


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
     * @return \DateTime
     */
    public function getDateBegin(): ?\DateTime
    {
        return $this->dateBegin;
    }

    /**
     * @param \DateTime $dateBegin
     * @return GroupsLinkApplications
     */
    public function setDateBegin(\DateTime $dateBegin): self
    {
        $this->dateBegin = $dateBegin;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    /**
     * @param \DateTime $dateEnd
     * @return GroupsLinkApplications
     */
    public function setDateEnd(\DateTime $dateEnd)
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxStudentsPerGroups()
    {
        return $this->maxStudentsPerGroups;
    }

    /**
     * @param Integer $maximum
     * @return GroupsLinkApplications
     */
    public function setmaxStudentsPerGroups(Int $maximum): self
    {
        $this->maxStudentsPerGroups = $maximum;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxTeachersPerGroups(): ?string
    {
        return $this->maxTeachersPerGroups;
    }

    /**
     * @param Integer $maximum
     * @return GroupsLinkApplications
     */
    public function setmaxTeachersPerGroups(Int $maximum): self
    {
        $this->maxTeachersPerGroups = $maximum;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxStudentsPerTeachers()
    {
        return $this->maxStudentsPerTeachers;
    }

    /**
     * @param Integer $maximum
     * @return GroupsLinkApplications
     */
    public function setmaxStudentsPerTeachers(Int $maximum): self
    {
        $this->maxStudentsPerTeachers = $maximum;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'group_id' => $this->getGroup(),
            'application_id' => $this->getApplication(),
            'date_begin' => $this->getDateBegin(),
            'date_end' => $this->getDateEnd(),
            'max_students_per_group' => $this->getmaxStudentsPerGroups(),
            'max_teachers_per_group' => $this->getmaxTeachersPerGroups(),
            'max_students_per_teachers' => $this->getmaxStudentsPerTeachers()
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
