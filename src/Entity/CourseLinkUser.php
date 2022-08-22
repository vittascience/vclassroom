<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\CourseLinkUserRepository")
 * @ORM\Table(name="classroom_courses_link_classroom_users")
 */
class CourseLinkUser implements \JsonSerializable, \Utils\JsonDeserializer
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
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Course")
     * @ORM\JoinColumn(name="course_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $course;

    /**
     * @ORM\Column(name="actual_activity_index", type="integer", nullable=false)
     * @var int
     */
    private $index;



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


    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex(?int $index)
    {
        $this->index = $index;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'user' => $this->user,
            'course' => $this->course,
            'index' => $this->index
        ];
    } 
}
