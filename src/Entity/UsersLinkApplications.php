<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\UsersLinkApplicationsRepository")
 * @ORM\Table(name="classroom_users_link_applications")
 */
class UsersLinkApplications
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\Applications")
     * @ORM\JoinColumn(name="application_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Groups
     */
    private $application;

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
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UsersLinkApplications
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Applications
     */
    public function getApplication()
    {
        return $this->application->getId();
    }

    /**
     * @param Applications
     * @return UsersLinkApplications
     */
    public function setApplication(Applications $application): self
    {
        $this->application = $application;
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
     * @return Applications
     */
    public function setmaxActivitiesPerTeachers(Int $maximum): self
    {
        $this->maxActivitiesPerTeachers = $maximum;
        return $this;
    }


    public function jsonSerialize(): mixed
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
