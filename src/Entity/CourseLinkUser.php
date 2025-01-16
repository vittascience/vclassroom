<?php

namespace Classroom\Entity;

use User\Entity\User;
use Learn\Entity\Course;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Classroom\Repository\CourseLinkUserRepository")]
#[ORM\Table(name: "classroom_users_link_courses")]
class CourseLinkUser implements \JsonSerializable, \Utils\JsonDeserializer
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: "User\Entity\User")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $user;

    #[ORM\ManyToOne(targetEntity: "Learn\Entity\Course")]
    #[ORM\JoinColumn(name: "course_id", referencedColumnName: "id", onDelete: "CASCADE", nullable: false)]
    private $course;

    #[ORM\Column(name: "reference", type: "string", length: 13, nullable: true)]
    private $reference;

    #[ORM\Column(name: "activities_references", type: "text", nullable: true)]
    private $activitiesReferences;

    #[ORM\Column(name: "activities_data", type: "text", nullable: true)]
    private $activitiesData;

    #[ORM\Column(name: "date_begin", type: "datetime", nullable: true)]
    private $dateBegin;

    #[ORM\Column(name: "date_end", type: "datetime", nullable: true)]
    private $dateEnd;

    #[ORM\Column(name: "course_state", type: "integer", nullable: false)]
    private $courseState;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getCourse()
    {
        return $this->course;
    }

    public function setCourse(Course $course)
    {
        $this->course = $course;
    }

    public function getActivitiesData()
    {
        return $this->activitiesData;
    }

    public function setActivitiesData($activitiesData)
    {
        $this->activitiesData = $activitiesData;
    }

    public function getDateBegin()
    {
        return $this->dateBegin;
    }

    public function setDateBegin(?\DateTime $dateBegin)
    {
        $this->dateBegin = $dateBegin;
    }

    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTime $dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    public function getCourseState()
    {
        return $this->courseState;
    }

    public function setCourseState($courseState)
    {
        $this->courseState = $courseState;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    public function getActivitiesReferences()
    {
        return $this->activitiesReferences;
    }

    public function setActivitiesReferences($activitiesReferences)
    {
        $this->activitiesReferences = $activitiesReferences;
    }

    public function jsonSerialize()
    {
        $activitiesReferences = null;
        if ($this->activitiesReferences != null) {
            $activitiesReferences = json_decode($this->activitiesReferences);
        }

        return [
            'id' => $this->id,
            'user' => $this->user,
            'course' => $this->course,
            'activitiesData' => $this->activitiesData,
            'dateBegin' => $this->dateBegin,
            'dateEnd' => $this->dateEnd,
            'courseState' => $this->courseState,
            'reference' => $this->reference,
            'activitiesReferences' => $activitiesReferences
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
