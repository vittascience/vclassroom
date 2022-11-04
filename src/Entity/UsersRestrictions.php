<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\UsersRestrictionsRepository")
 * @ORM\Table(name="classroom_users_restrictions")
 */
class UsersRestrictions implements \JsonSerializable, \Utils\JsonDeserializer
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;


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
     * @ORM\Column(name="max_students", type="integer", nullable=true)
     * @var integer
     */
    private $maxStudents;

    /**
     * @ORM\Column(name="max_classrooms", type="integer", nullable=true)
     * @var integer
     */
    private $maxClassrooms;
    


    /**
     * @return Int
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return UsersRestrictions
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
     * @return UsersRestrictions
     */
    public function setDateEnd(\DateTime $dateEnd)
    {
        $this->dateEnd = $dateEnd;
        return $this;
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
     * @return UsersRestrictions
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Int
     */
    public function getMaxStudents(): ?int
    {
        return $this->maxStudents;
    }

    /**
     * @param Int $maxStudents
     * @return UsersRestrictions
     */
    public function setMaxStudents(Int $maxStudents): self
    {
        $this->maxStudents = $maxStudents;
        return $this;
    }


    /**
     * @return Int
     */
    public function getMaxClassrooms(): ?int
    {
        return $this->maxClassrooms;
    }

    /**
     * @param Int $maxClassrooms
     * @return UsersRestrictions
     */
    public function setMaxClassrooms(Int $maxClassrooms): self
    {
        $this->maxClassrooms = $maxClassrooms;
        return $this;
    }
    

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'user' => $this->getUser(),
            'dateBegin' => $this->getDateBegin(),
            'dateEnd' => $this->getDateEnd(),
            'maxStudents' => $this->getMaxStudents(),
            'maxClassrooms' => $this->getMaxClassrooms(),
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
