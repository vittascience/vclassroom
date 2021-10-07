<?php

namespace Classroom\Tests;

use PHPUnit\Framework\TestCase;
use Learn\Entity\Activity;
use Learn\Entity\Course;
use User\Entity\User;
use Classroom\Entity\ActivityLinkUser;
use Utils\TestConstants;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class ActivityLinkUserTest extends TestCase
{
    public const TEST_RIGHTS = 1;
    public const TEXT_MAX_LENGTH = 2000;
    public function testUserIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setUser($classroomUser); // right argument
        $this->assertEquals($tutorialPartLinkUser->getUser(), $classroomUser);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setUser(TestConstants::TEST_INTEGER); // integer
        $tutorialPartLinkUser->setUser(true); // boolean
        $tutorialPartLinkUser->setUser(null); // null
    }

    public function testActivityIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setActivity($tutorialPart); // right argument
        $this->assertEquals($tutorialPartLinkUser->getActivity(), $tutorialPart);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setActivity(TestConstants::TEST_INTEGER); // integer
        $tutorialPartLinkUser->setActivity(true); // boolean
        $tutorialPartLinkUser->setActivity(null); // null
    }
    public function testTriesIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setTries(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($tutorialPartLinkUser->getTries(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setTries(-1); // negative
        $tutorialPartLinkUser->setTries(true); // boolean
        $tutorialPartLinkUser->setTries(50000000000000000000000000000000000); // null
        $tutorialPartLinkUser->setTries("1"); // null
    }

    public function testTimePassedIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setTimePassed(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($tutorialPartLinkUser->getTimePassed(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setTimePassed(-1); // negative
        $tutorialPartLinkUser->setTimePassed(true); // boolean
        $tutorialPartLinkUser->setTimePassed(50000000000000000000000000000000000); // null
        $tutorialPartLinkUser->setTimePassed("1"); // string
    }

    public function testCoefficientIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setCoefficient(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($tutorialPartLinkUser->getCoefficient(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setCoefficient(-1); // negative
        $tutorialPartLinkUser->setCoefficient(true); // boolean
        $tutorialPartLinkUser->setCoefficient(500000); // null
        $tutorialPartLinkUser->setCoefficient("1"); // string
    }

    public function testDateEndSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $date = new \DateTime();
        $tutorialPartLinkUser->setDateEnd($date);
        $this->assertEquals($tutorialPartLinkUser->getDateEnd(), $date);
    }

    public function testDateBeginIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $date = new \DateTime();
        $tutorialPartLinkUser->setDateBegin($date);
        $this->assertEquals($tutorialPartLinkUser->getDateBegin(), $date);
    }

    public function testNoteIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setNote(TestConstants::TEST_STRING); // should ne be a string
        $tutorialPartLinkUser->setNote(null); // should not be null
    }

    public function testIntroductionIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);

        $acceptedIntroduction = 'aaaa';
        $nonAcceptedIntroduction = '';
        for ($i = 0; $i <= self::TEXT_MAX_LENGTH; $i++) //add more than 1000 characters 
            $nonAcceptedIntroduction .= 'a';

        $tutorialPartLinkUser->setIntroduction($acceptedIntroduction); // right value
        $this->assertEquals($tutorialPartLinkUser->getIntroduction(), $acceptedIntroduction);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setIntroduction(null); // null
        $tutorialPartLinkUser->setIntroduction($nonAcceptedIntroduction);
        $tutorialPartLinkUser->setIntroduction(TestConstants::TEST_INTEGER); // integer
    }

    public function testCommentaryIsSet()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", 77);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);

        $acceptedCommentary = 'aaaa';
        $nonAcceptedCommentary = '';
        for ($i = 0; $i <= self::TEXT_MAX_LENGTH; $i++) //add more than 1000 characters 
            $nonAcceptedCommentary .= 'a';

        $tutorialPartLinkUser->setCommentary($acceptedCommentary); // right value
        $this->assertEquals($tutorialPartLinkUser->getCommentary(), $acceptedCommentary);
        $this->expectException(EntityDataIntegrityException::class);
        $tutorialPartLinkUser->setCommentary(null); // null
        $tutorialPartLinkUser->setCommentary($nonAcceptedCommentary);
        $tutorialPartLinkUser->setCommentary(TestConstants::TEST_INTEGER); // integer
    }

    public function testjsonSerialize()
    {
        $classroomUser = new User();
        $tutorialPart = new Activity("title", "content", $classroomUser);
        $date = new \DateTime();
        $classroomUser->setId(TestConstants::TEST_INTEGER);
        $tutorialPart->setId(TestConstants::TEST_INTEGER);
        $tutorialPartLinkUser = new ActivityLinkUser($tutorialPart, $classroomUser);
        $tutorialPartLinkUser->setDateBegin($date);
        $tutorialPartLinkUser->setDateEnd($date);
        $tutorialPartLinkUser->setDateSend($date);
        //test array
        $test = [
            'id' => null,
            'user' => $classroomUser->jsonSerialize(),
            'activity' => $tutorialPart->jsonSerialize(),
            'reference' => 'aaaaa',
            'course' => null,
            'dateSend' => $date,
            'commentary' => '',
            'introduction' => '',
            'tries' => 0,
            'coefficient' => 1,
            'timePassed' => 0,
            'dateBegin' => $date,
            'dateEnd' => $date,
            'note' => 0,
            'correction' => null,
            'autocorrection' => false,
            'evaluation' => false,
            'project' => null,
            'url'=> ''
        ];
        $serialized = $tutorialPartLinkUser->jsonSerialize();
        $this->assertEquals($serialized, $test);
    }
}
