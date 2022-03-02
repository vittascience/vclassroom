<?php

namespace Classroom\Tests;

use User\Entity\User;
use Learn\Entity\Course;
use Utils\TestConstants;
use Learn\Entity\Activity;
use Interfaces\Entity\Project;
use PHPUnit\Framework\TestCase;
use Classroom\Entity\ActivityLinkUser;
use Utils\Exceptions\EntityOperatorException;
use Utils\Exceptions\EntityDataIntegrityException;

class ActivityLinkUserTest extends TestCase
{
    public const TEST_RIGHTS = 1;
    public const TEXT_MAX_LENGTH = 2000;
    private $activityLinkUser ;
    private $activity;
    private $user;

    public function setUp():void{
        $mockedUser = $this->createMock(User::class);
        $mockedActivity = $this->createMock(Activity::class);
        $mockedActivity->method('getTitle')->willReturn('title');
        $mockedActivity->method('getContent')->willReturn('content');
        $mockedActivity->method('getUser')->willReturn(77);
        
        $this->user = $mockedUser; // new User();
        $this->activity = $mockedActivity;// new Activity("title", "content", 77);
        $this->activityLinkUser = new ActivityLinkUser($this->activity, $this->user);
    }

    public function tearDown(): void{
        $this->user = null;
        $this->activity = null;
        $this->activityLinkUser = null;
    }
   
    /** @dataProvider provideIds */
    public function testGetIdReturnsValue($providedValue){
        $this->assertNull($this->activityLinkUser->getId());

        $fakeIdSetterDeclaration = function() use($providedValue){
            return $this->id = $providedValue;
        };

        $fakeIdSetterExecution = $fakeIdSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class
        );

        $fakeIdSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getId());
    }

    public function testUserIsSet()
    {
        $this->activityLinkUser->setUser( $this->user); // right argument
        $this->assertEquals($this->activityLinkUser->getUser(), $this->user);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setUser(TestConstants::TEST_INTEGER); // integer
        $this->activityLinkUser->setUser(true); // boolean
        $this->activityLinkUser->setUser(null); // null
    }

    public function testActivityIsSet()
    {
        $this->activityLinkUser->setActivity($this->activity); // right argument
        $this->assertEquals($this->activityLinkUser->getActivity(), $this->activity);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setActivity(TestConstants::TEST_INTEGER); // integer
        $this->activityLinkUser->setActivity(true); // boolean
        $this->activityLinkUser->setActivity(null); // null
    }
    
    /** @dataProvider provideProjectObjects */
    public function testGetProjectReturnsAnInstanceOfProject($providedValue){
        $fakeProjectSetterDeclaration = function() use($providedValue){
            $this->project = $providedValue;
        };

        $fakeProjectSetterExecution = $fakeProjectSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeProjectSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getProject());
        $this->assertInstanceOf(Project::class, $this->activityLinkUser->getProject());
    }

    /** @dataProvider provideInvalidObjectValues */
    public function testSetProjectRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setProject($providedValue);
    }

    /** @dataProvider provideProjectObjects */
    public function testSetProjectAcceptsValidValue($providedValue){
        $this->assertNull($this->activityLinkUser->getProject());

        $this->activityLinkUser->setProject($providedValue);
        $this->assertEquals($providedValue, $this->activityLinkUser->getProject());
        $this->assertInstanceOf(Project::class, $this->activityLinkUser->getProject());
    }

    /** @dataProvider provideCourseObjects */
    public function testGetCourseReturnsCourseObject($providedValue){
        $fakeCourseSetterDeclaration = function() use($providedValue){
            return $this->course = $providedValue;
        };

        $fakeCourseSetterExecution = $fakeCourseSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeCourseSetterExecution();

        $this->assertInstanceOf(Course::class, $this->activityLinkUser->getCourse());
    }

    /** @dataProvider provideInvalidObjectValues */
    public function testSetCourseRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setCourse($providedValue);
    }

    /** @dataProvider provideCourseObjects */
    public function testSetCourseAcceptsValidCourseValue($providedValue){
        $this->assertNull($this->activityLinkUser->getCourse());

        $this->activityLinkUser->setCourse($providedValue);
        $this->assertEquals($providedValue, $this->activityLinkUser->getCourse());
        $this->assertInstanceOf(Course::class, $this->activityLinkUser->getCourse());
    }

    /** @dataProvider provideInvalidDateValues */
    public function testSetDateBeginRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setDateBegin($providedValue);
    }

     /** @dataProvider provideInvalidDateValues */
     public function testSetDateEndRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setDateEnd($providedValue);
    }
    
    public function testTriesIsSet()
    {
        $this->activityLinkUser->setTries(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($this->activityLinkUser->getTries(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setTries(-1); // negative
        $this->activityLinkUser->setTries(true); // boolean
        $this->activityLinkUser->setTries(50000000000000000000000000000000000); // null
        $this->activityLinkUser->setTries("1"); // null
    }

    public function testTimePassedIsSet()
    {
        $this->activityLinkUser->setTimePassed(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($this->activityLinkUser->getTimePassed(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setTimePassed(-1); // negative
        $this->activityLinkUser->setTimePassed(true); // boolean
        $this->activityLinkUser->setTimePassed(50000000000000000000000000000000000); // null
        $this->activityLinkUser->setTimePassed("1"); // string
    }

    public function testCoefficientIsSet()
    {
        $this->activityLinkUser->setCoefficient(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($this->activityLinkUser->getCoefficient(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setCoefficient(-1); // negative
        $this->activityLinkUser->setCoefficient(true); // boolean
        $this->activityLinkUser->setCoefficient(500000); // null
        $this->activityLinkUser->setCoefficient("1"); // string
    }

    public function testDateEndSet()
    {
        $date = new \DateTime();
        $this->activityLinkUser->setDateEnd($date);
        $this->assertEquals($this->activityLinkUser->getDateEnd(), $date);
    }

    public function testDateBeginIsSet()
    {
        $date = new \DateTime();
        $this->activityLinkUser->setDateBegin($date);
        $this->assertEquals($this->activityLinkUser->getDateBegin(), $date);
    }

    public function testNoteIsSet()
    {
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setNote(TestConstants::TEST_STRING); // should ne be a string
        $this->activityLinkUser->setNote(null); // should not be null
    }

    public function testIntroductionIsSet()
    {
        $acceptedIntroduction = 'aaaa';
        $nonAcceptedIntroduction = '';
        for ($i = 0; $i <= self::TEXT_MAX_LENGTH; $i++) //add more than 1000 characters 
            $nonAcceptedIntroduction .= 'a';

        $this->activityLinkUser->setIntroduction($acceptedIntroduction); // right value
        $this->assertEquals($this->activityLinkUser->getIntroduction(), $acceptedIntroduction);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setIntroduction(null); // null
        $this->activityLinkUser->setIntroduction($nonAcceptedIntroduction);
        $this->activityLinkUser->setIntroduction(TestConstants::TEST_INTEGER); // integer
    }

    public function testCommentaryIsSet()
    {
        $acceptedCommentary = 'aaaa';
        $nonAcceptedCommentary = '';
        for ($i = 0; $i <= self::TEXT_MAX_LENGTH; $i++) //add more than 1000 characters 
            $nonAcceptedCommentary .= 'a';

        $this->activityLinkUser->setCommentary($acceptedCommentary); // right value
        $this->assertEquals($this->activityLinkUser->getCommentary(), $acceptedCommentary);
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setCommentary(null); // null
        $this->activityLinkUser->setCommentary($nonAcceptedCommentary);
        $this->activityLinkUser->setCommentary(TestConstants::TEST_INTEGER); // integer
    }



    /** dataProvider for testGetIdReturnValue */
    public function provideIds(){
        return array(
            array(1),
            array(65),
            array(1000),
        );
    }

    /**
     * dataProvider for 
     * => testGetProjectReturnsAnInstanceOfProject
     * => testSetProjectAcceptsValidValue
     */
    public function provideProjectObjects(){
        $project1 = $this->createMock(Project::class);
        $project2 = $this->createMock(Project::class);
        $project3 = $this->createMock(Project::class);

        return array(
            array($project1),
            array($project2),
            array($project3)
        );
    }

    /** 
     * dataProvider for 
     * => testSetProjectRejectsInvalidValue 
     * => testSetCourseRejectsInvalidValue
     * */
    public function provideInvalidObjectValues(){
        return array(
            array(new \stdClass()),
            array([]),
            array(1),
            array('1251'),
        );
    }

    /** dataProvider for testGetCourseReturnsCourseObject */
    public function provideCourseObjects(){
        $mockedCourse1 = $this->createMock(Course::class);
        $mockedCourse2 = $this->createMock(Course::class);
        $mockedCourse3 = $this->createMock(Course::class);

        return array(
            array($mockedCourse1),
            array($mockedCourse2),
            array($mockedCourse3),
        );
    }

    /** dataProvider for testSetDateBeginRejectsInvalidValue */
    public function provideInvalidDateValues(){
        return array(
            array(new \stdClass()),
            array([]),
            array(1)
        );
    }

/*     public function testjsonSerialize()
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
    } */
}
