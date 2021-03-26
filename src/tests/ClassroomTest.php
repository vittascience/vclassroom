<?php

namespace Classroom\Tests;

use PHPUnit\Framework\TestCase;
use Classroom\Entity\Classroom;
use Utils\TestConstants;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class ClassroomTest extends TestCase
{
    public const TEST_CLASSROOM_LINK = "aaaaa";
    public function testIdIsSet()
    {
        $classroom = new Classroom();
        $classroom->setId(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($classroom->getId(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $classroom->setId(-1); // negative
        $classroom->setId(true); // boolean
        $classroom->setId(null); // null
    }

    public function testNameIsSet()
    {
        $classroom = new Classroom();

        $acceptedName = 'aaaa';
        $nonAcceptedName = '';
        for ($i = 0; $i <= TestConstants::NAME_CLASSROOM_MAX_LENGTH; $i++) //add more than 255 characters 
            $nonAcceptedName .= 'a';

        $classroom->setName($acceptedName); // right argument
        $this->assertEquals($classroom->getName(), $acceptedName);
        $this->expectException(EntityDataIntegrityException::class);
        $classroom->setName(TestConstants::TEST_INTEGER); // integer
        $classroom->setName(true); // boolean
        $classroom->setName(null); // null
        $classroom->setName($nonAcceptedName); // more than 255 chars
    }

    public function testSchoolIsSet()
    {
        $classroom = new Classroom();

        $acceptedSchool = 'aaaa';
        $nonAcceptedSchool = '';
        for ($i = 0; $i <= TestConstants::NAME_CLASSROOM_MAX_LENGTH; $i++) //add more than 255 characters 
            $nonAcceptedSchool .= 'a';

        $classroom->setSchool($acceptedSchool); // right argument
        $this->assertEquals($classroom->getSchool(), $acceptedSchool);
        $this->expectException(EntityDataIntegrityException::class);
        $classroom->setSchool(TestConstants::TEST_INTEGER); // integer
        $classroom->setSchool(true); // boolean
        $classroom->setSchool($nonAcceptedSchool); // more than 255 chars
    }

    public function testLinkIsSet()
    {
        $classroom = new Classroom();
        $classroom->setLink(); // right argument
        $this->assertEquals(preg_match('/[a-z0-9]{5}/', $classroom->getLink()), true);
    }
    public function testjsonSerialize()
    {
        $classroom = new Classroom();
        $classroom->setId(TestConstants::TEST_INTEGER);
        //test array
        $test = [
            'id' => 5,
            'name' => 'default',
            'school' => 'default',
            'isChanged' => false,
            'groupe' => null
        ];
        $serialized = $classroom->jsonSerialize();
        unset($serialized["link"]);
        $this->assertEquals($serialized, $test);
    }
}
