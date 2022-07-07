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

    /** @dataProvider provideDateValues */
    public function testGetDateSendReturnValidValue($providedValue){
        $fakeDateSendSetterDeclaration = function() use($providedValue){
            return $this->dateSend = $providedValue;
        };

        $fakeDateSendSetterExecution = $fakeDateSendSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );
        $fakeDateSendSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getDateSend());
    }

    /** @dataProvider provideInvalidDateValues */
    public function testSetDateSendRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setDateSend($providedValue);
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

    /** @dataProvider provideIntegerValues */
    public function testGetCorrectionReturnsValue($providedValue){
        $fakeCorrectionSetterDeclaration = function() use ($providedValue){
            return $this->correction = $providedValue;
        };

        $fakeCorrectionSetterExecution = $fakeCorrectionSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeCorrectionSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getCorrection());
    }

    /** @dataProvider provideInvalidValues */
    public function testSetCorrectionRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setCorrection($providedValue);
    }

     /** @dataProvider provideIntegerValues */
    public function testCorrectionAcceptsValidValue($providedValue){
        $this->assertNull($this->activityLinkUser->getCorrection());

        $this->activityLinkUser->setCorrection($providedValue);
        $this->assertEquals($providedValue, $this->activityLinkUser->getCorrection());
        $this->assertIsInt($this->activityLinkUser->getCorrection());

    }

     /** @dataProvider provideReferenceValues */
     public function testGetReferenceReturnsValue($providedValue){
        $fakeReferenceSetterDeclaration = function() use($providedValue){
            return $this->reference = $providedValue;
        };

        $fakeReferenceSetterExecution = $fakeReferenceSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeReferenceSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getReference());
    }

    /** @dataProvider provideNonStringValues */
    public function testSetReferenceRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setReference($providedValue);
    }

    /** @dataProvider provideIntegerValues */
    public function testGetNoteReturnsValue($providedValue){
        $fakeNoteSetterDeclaration = function()use($providedValue){
            return $this->note = $providedValue;
        };

        $fakeNoteSetterExecution = $fakeNoteSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeNoteSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getNote());
        $this->assertIsInt($this->activityLinkUser->getNote());
    }

    /** @dataProvider provideBooleanValues */
    public function testGetAutoCorrectionReturnsValue($providedValue){
        $fakeAutoCorrectionSetterDeclaration = function() use($providedValue){
            return $this->autocorrection = $providedValue;
        };

        $fakeAutoCorrectionSetterExecution = $fakeAutoCorrectionSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeAutoCorrectionSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getAutocorrection());
    }

     /** @dataProvider provideBooleanValues */
     public function testSetAutoCorrectionAcceptsValidValue($providedValue){
        $this->assertFalse($this->activityLinkUser->getAutocorrection());

         $this->activityLinkUser->setAutocorrection($providedValue);
         $this->assertEquals($providedValue, $this->activityLinkUser->getAutocorrection());
     }

    //  /** @dataProvider provideInvalidValues */
    //  public function testSetAutoCorrectionRejectsInvalidValue($providedValue){
    //      $this->expectException(EntityDataIntegrityException::class);
    //      $this->activityLinkUser->setAutocorrection($providedValue);
    //  }

      /** @dataProvider provideBooleanValues */
      public function testGetEvaluationReturnsValue($providedValue){
        $fakeEvaluationSetterDeclaration = function() use($providedValue){
            return $this->evaluation = $providedValue;
        };

        $fakeEvaluationSetterExecution = $fakeEvaluationSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeEvaluationSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getEvaluation());
     }

    //  /** @dataProvider provideInvalidValues */
    //  public function testSetEvaluationRejectsInvalidValue($providedValue){
    //      $this->expectException(EntityDataIntegrityException::class);
    //      $this->activityLinkUser->setEvaluation($providedValue);
    //  }

     /** @dataProvider provideUrls */
     public function testGetUrlReturnsValue($providedValue){
        $fakeUrlSetterDeclaration = function() use($providedValue){
            return $this->url = $providedValue;
        };

        $fakeUrlSetterExecution = $fakeUrlSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeUrlSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getUrl());
    }

    /** @dataProvider provideNonStringValues */
    public function testSetUrlRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->activityLinkUser->setUrl($providedValue);
    }

     /** @dataProvider provideStringValues */
     public function testGetResponseReturnsValue($providedValue){
        $fakeResponseSetterDeclaration = function() use($providedValue){
            return $this->response = $providedValue;
        };

        $fakeResponseSetterExecution = $fakeResponseSetterDeclaration->bindTo(
            $this->activityLinkUser,
            ActivityLinkUser::class 
        );

        $fakeResponseSetterExecution();

        $this->assertEquals($providedValue, $this->activityLinkUser->getResponse());
    }

    /** @dataProvider provideNonStringValues */
    // public function testSetResponseRejectsInvalidValue($providedValue){
    //     $this->expectException(EntityDataIntegrityException::class);
    //     $this->activityLinkUser->setResponse($providedValue);
    // }

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

   /** 
     * dataProvider for 
     * => testSetDateBeginRejectsInvalidValue 
     * => testSetDateEndRejectsInvalidValue
     * => testSetDateSendRejectsInvalidValue
     */
    public function provideInvalidDateValues(){
        return array(
            array(new \stdClass()),
            array([]),
            array(1)
        );
    }

    /** dataProvider testGetDateSendReturnValidValue */
    public function provideDateValues(){
        return array(
            array('2021-12-13 00:00:00'),
            array('2022-01-13 15:00:00'),
            array('2022-01-13 00:00:00'),
        );
    }
    
    /** dataProvider for testGetCorrectionReturnsValue */
    public function  provideIntegerValues(){
        return array(
            array(0),
            array(1),
            array(2),
            array(3),
        );
     }
 
    /**
     *  dataProvider for 
     * => testSetCorrectionRejectsInvalidValue 
     * => testSetAutoCorrectionRejectsInvalidValue
     */
     public function provideInvalidValues(){
         return array(
             array('1'),
             array([]),
             array(new \stdClass()),
             array(2000)
         );
     }
 
     /** dataProvider for testGetReferenceReturnsValue */
    public function provideReferenceValues(){
        return array(
            array('1638797610'),
            array('1638801064'),
            array('1638803456'),
        );
    }

    /** dataProvider for testSetReferenceRejectsInvalidValue */
    public function provideNonStringValues(){
        return array(
            array(1),
            array(new \stdClass()),
            array([]),
        );
    }

    /** 
     * dataProvider for
     * => testGetAutoCorrectionReturnsValue
     */
    public function provideBooleanValues(){
        return array(
            array(true),
            array(false),
        );
    }

    /** dataProvider for  */
    public function provideUrls(){
        return array(
            array('https://fr.vittascience.com/python/?mode=mixed&console=right'),
            array('https://goole.com'),
            array('https://fr.vittascience.com'),
        );
    }

    /** dataProvider for testSetResponseRejectsInvalidValue */
    public function provideStringValues(){
        return array(
            array('response1'),
            array('string1'),
            array('some more string example')
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
