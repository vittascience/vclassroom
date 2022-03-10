<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use User\Entity\User;
use Learn\Entity\Activity;
use Learn\Entity\Course;
use Interfaces\Entity\Project;
use DateTime;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ActivityLinkUserRepository")
 * @ORM\Table(name="classroom_activities_link_classroom_users")
 */
class ActivityLinkUser implements \JsonSerializable, \Utils\JsonDeserializer
{
    public const MAX_TRIES = 10;
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="id_user", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;
    /**
     * @ORM\Column(name="reference", type="string",length=13, nullable=false)
     * @var string
     */
    private $reference;


    /**
     * @ORM\ManyToOne(targetEntity="Learn\Entity\Activity")
     * @ORM\JoinColumn(name="id_activity", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Activity
     */
    private $activity;
    /**
     * @ORM\ManyToOne(targetEntity="Interfaces\Entity\Project")
     * @ORM\JoinColumn(name="project", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     * @var Project
     */
    private $project;

    /**
     * @ORM\Column(name="correction",type="integer", nullable=true,length=2)
     * @var int
     */
    private $correction;

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
     * @ORM\Column(name="date_send", type="datetime", nullable=true)
     * @var \DateTime
     */
    private $dateSend = null;
    /**
     * @ORM\Column(name="time_passed", type="integer", length=11, nullable=true)
     * @var int
     */
    private $timePassed = 0;
    /**
     * @ORM\Column(name="tries", type="integer", length=3, nullable=true)
     * @var int
     */
    private $tries = 0;
    /**
     * @ORM\Column(name="coefficient", type="integer", length=2, nullable=true)
     * @var int
     */
    private $coefficient = 1;
    /**
     * @ORM\Column(name="note", type="smallint", nullable=false)
     * @var int
     */
    private $note = 0;
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
     * @ORM\Column(name="url", type="string",length=255, nullable=true)
     * @var string
     */
    private $url="";

    /**
     * @ORM\Column(name="response", type="text", nullable=true)
     * @var String
     */
    private $response;

    public function __construct(Activity $activity, User $user, $dateBegin = null, $dateEnd = null, $evaluation = false, $autocorrection = false,$url="",  $introduction = "", $reference = 'aaaaa', $commentary = "", $tries = 0, $timePassed = 0, $coefficient = 1, $note = 0, $response = null)
    {
        $this->setUser($user);
        $this->setActivity($activity);
        $this->setDateBegin($dateBegin);
        $this->setDateEnd($dateEnd);
        $this->setTries($tries);
        $this->setTimePassed($timePassed);
        $this->setDateSend(new DateTime('NOW'));
        $this->setNote($note);
        $this->setCoefficient($coefficient);
        $this->setIntroduction($introduction);
        $this->setCommentary($commentary);
        $this->setEvaluation($evaluation);
        $this->setAutocorrection($autocorrection);
        $this->setReference($reference);
        $this->setUrl($url);
        $this->setResponse($response);
    }
    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
    /**
     * @param User $user
     */
    public function setUser($user)
    {
        if ($user instanceof User) {
            $this->user = $user;
        } else {
            throw new EntityDataIntegrityException("User attribute needs to be an instance of User");
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
     * @return Project 
     */
    public function getProject()
    {
        return $this->project;
    }
    /**
     * @param Project $project
     */
    public function setProject($project)
    {
        if ($project instanceof Project) {
            $this->project = $project;
        } else {
            throw new EntityDataIntegrityException("project attribute needs to be an instance of Project");
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
     * @return \DateTime
     */
    public function getDateSend()
    {
        return $this->dateSend;
    }

    /**
     * @param \DateTime $dateSend
     */
    public function setDateSend($dateSend)
    {

        if ($dateSend instanceof \DateTime || $dateSend == "")
            $this->dateSend = $dateSend;
        else
            throw new EntityDataIntegrityException("dateSend needs to be DateTime or null");
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
    public function getTries()
    {
        return $this->tries;
    }
    /**
     * @param int $tries
     */
    public function setTries($tries)
    {
        if (is_int($tries) && $tries >= 0 && $tries < 1000) {
            $this->tries = $tries;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and between 0 and 999");
    }

    /**
     * @return int
     */
    public function getCorrection()
    {
        return $this->correction;
    }
    /**
     * @param int $correction
     */
    public function setCorrection($correction)
    {
        if (is_int($correction) && $correction >= 0 && $correction < 1000) {
            $this->correction = $correction;
        } else
            throw new EntityDataIntegrityException("correction needs to be integer and between 0 and 999");
    }

    /**
     * @return int
     */
    public function getTimePassed()
    {
        return $this->timePassed;
    }
    /**
     * @param int $timePassed
     */
    public function setTimePassed($timePassed)
    {
        if (is_int($timePassed) && $timePassed >= 0) {
            $this->timePassed = $timePassed;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and positive or null");
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
    /**
     * @return int
     */
    public function getNote()
    {
        return $this->note;
    }
    /**
     * @param int $note
     */
    public function setNote($note)
    {
        if (is_int($note) && $note >= 0 && $note < 4) {
            $this->note = $note;
        } else {
            throw new EntityDataIntegrityException("note needs to be int, between 0 and 3 included");
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
        $autocorrection = $autocorrection == 'true' ? true : false; 
        if(!is_bool($autocorrection)  ){
            throw new EntityDataIntegrityException("The auto correction field has to be a boolean value");
        }
        $this->autocorrection = $autocorrection;
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
        $evaluation = $evaluation == 'true' ? true : false; 
        if(!is_bool($evaluation)  ){
            throw new EntityDataIntegrityException("The evaluation field has to be a boolean value");
        }
        $this->evaluation = $evaluation;
    }

    /**
     * Get the value of url
     *
     * @return  string
     */ 
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the value of url
     *
     * @param  string  $url
     *
     * @return  self
     */ 
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }


    /**
     * Get the value of dateSend
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the value of response
     * @param  string  $response
     * @return  self
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }
    
    public function jsonSerialize()
    {
        if ($this->getCourse() != null) {
            $course = $this->getCourse()->jsonSerialize();
        } else {
            $course = null;
        }
        if ($this->getProject() != null) {
            $project = $this->getProject()->jsonSerialize();
        } else {
            $project = null;
        }

        $unserialized = @unserialize($this->getResponse());
        if ($unserialized) {
            $response = json_encode($unserialized);
        } else {
            $response = $this->getResponse();
        }
        return [
            'id' => $this->getId(),
            'user' => $this->getUser()->jsonSerialize(),
            'activity' => $this->getActivity()->jsonSerialize(),
            'course' => $course,
            'note' => $this->getNote(),
            'tries' => $this->getTries(),
            'introduction' => $this->getIntroduction(),
            'commentary' => $this->getCommentary(),
            'dateBegin' => $this->getDateBegin(),
            'dateEnd' => $this->getDateEnd(),
            'dateSend' => $this->getDateSend(),
            'timePassed' => $this->getTimePassed(),
            'coefficient' => $this->getCoefficient(),
            'correction' => $this->getCorrection(),
            'autocorrection' => $this->getAutocorrection(),
            'evaluation' => $this->getEvaluation(),
            'project' => $project,
            'reference' => $this->getReference(),
            'url'=> $this->getUrl(),
            'response' => $response
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self(new Activity("title", "content", new User(), false), new User(), null, null, false, false,null);
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
