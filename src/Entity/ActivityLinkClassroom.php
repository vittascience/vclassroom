<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use Classroom\Entity\Classroom;
use User\Entity\User;
use Learn\Entity\Activity;
use Learn\Entity\Course;
use Interfaces\Entity\Project;
use DateTime;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ActivityLinkClassroomRepository")
 * @ORM\Table(name="classroom_activities_link_classroom")
 */
class ActivityLinkClassroom implements \JsonSerializable, \Utils\JsonDeserializer
{
    public const MAX_TRIES = 10;
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\Classroom")
     * @ORM\JoinColumn(name="id_classroom", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Classroom
     */
    private $classroom;


    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Activity")
     * @ORM\JoinColumn(name="id_activity", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Activity
     */
    private $activity;

    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Course")
     * @ORM\JoinColumn(name="id_course", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @var Course
     */
    private $course;
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
     * @ORM\Column(name="coefficient", type="integer", length=2, nullable=true)
     * @var int
     */
    private $coefficient = 1;
    /**
     * @ORM\Column(name="commentary", type="string",length=2000, nullable=true)
     * @var string
     */
    private $commentary = "";
    /**
     * @ORM\Column(name="introduction", type="string",length=2000, nullable=true)
     * @var string
     */
    private $introduction = "";
    /**
     * @ORM\Column(name="is_autocorrected", type="boolean",nullable=false, options={"default":false})
     * @var bool
     */
    private $autocorrection = false;
    /**
     * @ORM\Column(name="is_evaluation", type="boolean",nullable=false, options={"default":false})
     * @var bool
     */
    private $evaluation = false;

    /**
     * @ORM\Column(name="reference", type="string",length=13, nullable=false)
     * @var string
     */
    private $reference;

    public function __construct(Activity $activity, Classroom $classroom, $dateBegin = null, $dateEnd = null, $evaluation = false, $autocorrection = false,  $introduction = "", $reference = '', $commentary = "", $coefficient = 1)
    {
        $this->setClassroom($classroom);
        $this->setActivity($activity);
        $this->setDateBegin($dateBegin);
        $this->setDateEnd($dateEnd);
        $this->setCoefficient($coefficient);
        $this->setIntroduction($introduction);
        $this->setCommentary($commentary);
        $this->setEvaluation($evaluation);
        $this->setAutocorrection($autocorrection);
        $this->setReference($reference);
    }
    /**
     * @return Classroom
     */
    public function getClassroom()
    {
        return $this->classroom;
    }
    /**
     * @param Classroom $classroom
     */
    public function setClassroom($classroom)
    {
        if ($classroom instanceof Classroom) {
            $this->classroom = $classroom;
        } else {
            throw new EntityDataIntegrityException("classroom attribute needs to be an instance of Classroom");
        }
    }
    /**
     * @return Activity 
     */
    public function getActivity()
    {
        return $this->activity;
    }
    /**
     * @param Activity $activity
     */
    public function setActivity($activity)
    {
        if ($activity instanceof Activity) {
            $this->activity = $activity;
        } else {
            throw new EntityDataIntegrityException("activity attribute needs to be an instance of Activity");
        }
    }


    /**
     * @return Course 
     */
    public function getCourse()
    {
        return $this->course;
    }
    /**
     * @param Course $course
     */
    public function setCourse($course)
    {
        if ($course instanceof Course) {
            $this->course = $course;
        } else {
            throw new EntityDataIntegrityException("course attribute needs to be an instance of Course");
        }
    }

    /**
     * @return \DateTime
     */
    public function getDateBegin()
    {
        return $this->dateBegin;
    }

    /**
     * @param \DateTime $dateBegin
     */
    public function setDateBegin($dateBegin)
    {

        if ($dateBegin instanceof \DateTime || $dateBegin == "")
            $this->dateBegin = $dateBegin;
        else
            throw new EntityDataIntegrityException("dateBegin needs to be DateTime or null");
    }

    /**
     * @return \DateTime
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * @param \DateTime $dateEnd
     */
    public function setDateEnd($dateEnd)
    {

        if ($dateEnd instanceof \DateTime || $dateEnd == "")
            $this->dateEnd = $dateEnd;
        else
            throw new EntityDataIntegrityException("dateEnd needs to be DateTime or null");
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @param int $id
     */
    public function setId($id)
    {

        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getCoefficient()
    {
        return $this->coefficient;
    }
    /**
     * @param int $coefficient
     */
    public function setCoefficient($coefficient)
    {
        if (is_int($coefficient) && $coefficient > 0 && $coefficient < 100) {
            $this->coefficient = $coefficient;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and between 1 and 99");
    }

    /**
     * @return string
     */
    public function getIntroduction()
    {
        return $this->introduction;
    }
    /**
     * @param string $introduction
     */
    public function setIntroduction($introduction)
    {
        if (is_string($introduction)  && strlen($introduction) < 2000) {
            $this->introduction = $introduction;
        } else {
            throw new EntityDataIntegrityException("introduction needs to be string with a lenght lesser than 2000 and" . strlen($introduction));
        }
    }
    /**
     * @return string
     */
    public function getCommentary()
    {
        return $this->commentary;
    }
    /**
     * @param string $commentary
     */
    public function setCommentary($commentary)
    {
        if (is_string($commentary)  && strlen($commentary) < 2000) {
            $this->commentary = $commentary;
        } else {
            throw new EntityDataIntegrityException("commentary needs to be string with a lenght lesser than 2000");
        }
    }


    /**
     * @return bool
     */
    public function getAutocorrection()
    {
        return $this->autocorrection;
    }

    /**
     * @param bool $autocorrection
     */
    public function setAutocorrection($autocorrection)
    {
        if ($autocorrection == "false" || $autocorrection == false) {
            $this->autocorrection = false;
        } else {
            $this->autocorrection = true;
        }
    }

    /**
     * @return bool
     */
    public function getEvaluation()
    {
        return $this->evaluation;
    }

    /**
     * @param bool $evaluation
     */
    public function setEvaluation($evaluation)
    {
        if ($evaluation == "false" || $evaluation == false) {
            $this->evaluation = false;
        } else {
            $this->evaluation = true;
        }
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }
    /**
     * @param string $commentary
     */
    public function setReference($reference)
    {
        if (is_string($reference)  && strlen($reference) < 13) {
            $this->reference = $reference;
        } else {
            throw new EntityDataIntegrityException("reference needs to be string with a lenght lesser than 13");
        }
    }

    public function jsonSerialize()
    {
        if ($this->getCourse() != null) {
            $course = $this->getCourse()->jsonSerialize();
        } else {
            $course = null;
        }
        return [
            'id' => $this->getId(),
            'classroom' => $this->getClassroom()->jsonSerialize(),
            'activity' => $this->getActivity()->jsonSerialize(),
            'course' => $course,
            'introduction' => $this->getIntroduction(),
            'commentary' => $this->getCommentary(),
            'dateBegin' => $this->getDateBegin(),
            'dateEnd' => $this->getDateEnd(),
            'coefficient' => $this->getCoefficient(),
            'autocorrection' => $this->getAutocorrection(),
            'evaluation' => $this->getEvaluation(),
            'reference' => $this->getReference(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self(new Activity("title", "content", new User()), new Classroom());
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
