<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Classroom\Entity\Applications;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ActivityRestrictionsRepository")
 * @ORM\Table(name="activities_restrictions")
 */
class ActivityRestrictions implements \JsonSerializable, \Utils\JsonDeserializer
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Classroom\Applications")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $application;

    /**
     * @ORM\Column(name="activity_type", type="string", length=255, nullable=false)
     * @var string
     */
    private $activityType;

    /**
     * @ORM\Column(name="max_per_teachers", type="integer", nullable=true)
     * @var integer
     */
    private $maxPerTeachers;


    /**
     * @return Int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Applications
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Applications $app
     * @return ActivityLinkUser
     */
    public function setApplication(Applications $app): self
    {
        $this->application = $app;
        return $this;
    }

    /**
     * @return activityType
     */
    public function getActivityType()
    {
        return $this->activityType;
    }

    /**
     * @param String $ActivityType
     * @return ActivityLinkUser
     */
    public function setActivityType(String $ActivityType): self
    {
        $this->activityType = $ActivityType;
        return $this;
    }


    /**
     * @return Integer
     */
    public function getMaxPerTeachers()
    {
        return $this->maxPerTeachers;
    }

    /**
     * @param Integer $maximum
     * @return ActivityLinkUser
     */
    public function setMaxPerTeachers(Int $maximum): self
    {
        $this->maxPerTeachers = $maximum;
        return $this;
    }



    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'application' => $this->getActivityType(),
            'activity_type' => $this->getActivityType(),
            'max_per_teachers' => $this->getMaxPerTeachers()
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
