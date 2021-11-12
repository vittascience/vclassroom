<?php

namespace Classroom\Tests;

use PHPUnit\Framework\TestCase;
use Classroom\Entity\Classroom;
use User\Entity\User;
use Classroom\Entity\ClassroomLinkUser;
use Utils\TestConstants;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class ClassroomLinkUserTest extends TestCase
{
    public const TEST_RIGHTS = 1;
    public function testUserIsSet()
    {
        $user = new User();
        $classroom = new Classroom();
        $classroomLinkUser = new ClassroomLinkUser($user, $classroom);
        $classroomLinkUser->setUser($user); // right argument
        $this->assertEquals($classroomLinkUser->getUser(), $user);
        $this->expectException(EntityDataIntegrityException::class);
        $classroomLinkUser->setUser(TestConstants::TEST_INTEGER); // integer
        $classroomLinkUser->setUser(true); // boolean
        $classroomLinkUser->setUser(null); // null
    }

    public function testClassroomIsSet()
    {
        $user = new User();
        $classroom = new Classroom();
        $classroomLinkUser = new ClassroomLinkUser($user, $classroom);
        $classroomLinkUser->setClassroom($classroom); // right argument
        $this->assertEquals($classroomLinkUser->getClassroom(), $classroom);
        $this->expectException(EntityDataIntegrityException::class);
        $classroomLinkUser->setClassroom(TestConstants::TEST_INTEGER); // integer
        $classroomLinkUser->setClassroom(true); // boolean
        $classroomLinkUser->setClassroom(null); // null
    }
    public function testRightsIsSet()
    {
        $user = new User();
        $classroom = new Classroom();
        $classroomLinkUser = new ClassroomLinkUser($user, $classroom);
        $classroomLinkUser->setRights(self::TEST_RIGHTS); // right argument
        $this->assertEquals($classroomLinkUser->getRights(), 1);
        $this->expectException(EntityDataIntegrityException::class);
        $classroomLinkUser->setRights(-1); // negative
        $classroomLinkUser->setRights(true); // boolean
        $classroomLinkUser->setRights(5); // null
        $classroomLinkUser->setRights("1"); // null
    }

    public function testjsonSerialize()
    {
        $user = new User();
        $classroom = new Classroom();
        $classroom->setId(TestConstants::TEST_INTEGER);
        //$user->setId(TestConstants::TEST_INTEGER);
        $classroomLinkUser = new ClassroomLinkUser($user, $classroom);
        //test array
        $test = [
            'user' => $user->jsonSerialize(),
            'classroom' => $classroom->jsonSerialize(),
            'rights' => 0
        ];
        $serialized = $classroomLinkUser->jsonSerialize();
        $this->assertEquals($serialized, $test);
    }
}
