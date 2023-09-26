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

    public function setUp(): void
    {
        $this->classroom = new Classroom();
    }

    public function tearDown(): void
    {
        $this->classroom = null;
    }

    public function testIdIsSet()
    {
        //$this->classroom = new Classroom();
        $this->classroom->setId(TestConstants::TEST_INTEGER); // right argument
        $this->assertEquals($this->classroom->getId(), 5);
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setId(-1); // negative
        $this->classroom->setId(true); // boolean
        $this->classroom->setId(null); // null
    }

    public function testNameIsSet()
    {
        //$classroom = new Classroom();

        $acceptedName = 'aaaa';
        $nonAcceptedName = '';
        for ($i = 0; $i <= TestConstants::NAME_CLASSROOM_MAX_LENGTH; $i++) //add more than 255 characters 
            $nonAcceptedName .= 'a';

        $this->classroom->setName($acceptedName); // right argument
        $this->assertEquals($this->classroom->getName(), $acceptedName);
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setName(TestConstants::TEST_INTEGER); // integer
        $this->classroom->setName(true); // boolean
        $this->classroom->setName(null); // null
        $this->classroom->setName($nonAcceptedName); // more than 255 chars
    }

    public function testSchoolIsSet()
    {
        //$classroom = new Classroom();

        $acceptedSchool = 'aaaa';
        $nonAcceptedSchool = '';
        for ($i = 0; $i <= TestConstants::NAME_CLASSROOM_MAX_LENGTH; $i++) //add more than 255 characters 
            $nonAcceptedSchool .= 'a';

            $this->classroom->setSchool($acceptedSchool); // right argument
            $this->assertEquals($this->classroom->getSchool(), $acceptedSchool);
            $this->expectException(EntityDataIntegrityException::class);
            $this->classroom->setSchool(TestConstants::TEST_INTEGER); // integer
            $this->classroom->setSchool(true); // boolean
            $this->classroom->setSchool($nonAcceptedSchool); // more than 255 chars
    }

    /** @dataProvider provideNonStringValues */
    public function testSetGarCodeRejectsNonStringValue($providedValue)
    {
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setGarCode($providedValue);
    }

    /** @dataProvider provideGarCodeStrings */
    public function testSetGarCodeAcceptsStringValue($providedValue){
        $this->assertNull($this->classroom->getGarCode());
        
        $this->classroom->setGarCode($providedValue);
        $this->assertSame($providedValue, $this->classroom->getGarCode());
    }

   /** @dataProvider provideAlphanumStrings */
   public function testLinkIsSet($providedValue)
   {
       //$classroom = new Classroom();
       $this->classroom->setLink($providedValue); // right argument
       $this->assertEquals(preg_match('/[a-z0-9]{5}/', $this->classroom->getLink()), true);
   }

    /** @dataProvider provideNonBooleanValues */
    public function testSetIsChangedRejectsNonBooleanValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setIsChanged($providedValue);
    }

    /** @dataProvider provideNonBooleanValues */
    public function testSetIsBlockedRejectsNonBooleanValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setIsBlocked($providedValue);
    }

    /** @dataProvider provideTrueAndFalseAsString */
    public function testSetIsBlockedAcceptsTrueAndFalseAsString($providedValue){
        $this->assertFalse($this->classroom->getIsBlocked());

        $this->classroom->setIsBlocked($providedValue);
        $this->assertIsBool($this->classroom->getIsBlocked());
    }

    public function testGetUaiIsNullByDefault(){
        $this->assertNull($this->classroom->getUai());
    }

    /** @dataProvider provideNonStringValues */
    public function testSetUaiRejectsNonStringValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->classroom->setUai($providedValue);
    }
    
    /** @dataProvider provideUaiStrings */
    public function testSetUaiAcceptsValidStringValue($providedValue){
        $this->assertNull($this->classroom->getUai());

        $this->classroom->setUai($providedValue);
        $this->assertSame($providedValue, $this->classroom->getUai());
    }

    public function testjsonSerialize()
    {
        //$classroom = new Classroom();
        $this->classroom->setId(TestConstants::TEST_INTEGER);
        //test array
        $test = [
            'id' => 5,
            'name' => 'default',
            'school' => 'default',
            'isChanged' => false,
            'isBlocked' => false,
            'garCode' => null
        ];
        $serialized = $this->classroom->jsonSerialize();
        unset($serialized["link"]);
        $this->assertEquals($serialized, $test);
    }
    
    /**
     * dataProvider for 
     * => testSetGarCodeRejectsNonStringValue
     * => testGetUaiRejectsNonStringValue
     */
    public static function provideNonStringValues()
    {
        return array(
            array([]),
            array(1),
            array(new \stdClass),
        );
    }

      /** dataProvider testSetGarCodeAcceptsStringValue */
      public static function provideGarCodeStrings(){
        return array(
            array('2B'),
            array('BTS1SN'),
            array('10255~GOA21_3-SC_GR'),
            array('10255~GOA21_3-SC_GR3'),
            array('10255~GOA21_3-SC_GR2')
        );
    }

     /** dataProvider testSetIsChangedRejectsNonBooleanValue */
     public static function provideNonBooleanValues(){
        return array(
            array([]),
            array(new \stdClass()),
            array('')
        );
    }

    public static function provideTrueAndFalseAsString(){
        return array(
            array('true'),
            array('false')
        );
    }
    
    /** dataProvider for testSetUaiAcceptsValidStringValue */
    public static function provideUaiStrings(){
        return array(
            array('0180591V'),
            array('0180591B'),
            array('1250591C')
        );
    }

    /** dataProvider for testLinkIsSet */
    public static function provideAlphanumStrings(){
        return array(
            array('gov1w'),
            array('k2wv6'),
            array('518xw'),
            array('93i8q')
        );
    }
}
