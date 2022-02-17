<?php

namespace Classroom\Tests;

use Classroom\Entity\LtiTool;
use PHPUnit\Framework\TestCase;

class LtiToolTest extends TestCase{
    private $ltiTool;

    public function setUp() :void{
        $this->ltiTool = new LtiTool();
    }

    public function tearDown() :void{
        $this->ltiTool = null;
    }

    public function testGetIdIsNullByDefault(){
        $this->assertNull($this->ltiTool->getId());
    }

    /** @dataProvider provideIds */
    public function testGetIdReturnsId($providedValue){
        
        $fakeLtiToolSetterDeclaration = function() use ($providedValue){
            return $this->id = $providedValue;
        };

        $fakeLtiToolSetterExecution = \Closure::bind(
            $fakeLtiToolSetterDeclaration,
            $this->ltiTool,
            LtiTool::class
        );

        $fakeLtiToolSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getId());
    }

    public function testGetApplicationIdIsNullByDefault(){
        $this->assertNull($this->ltiTool->getApplicationId());
    }

    /** @dataProvider provideIds */
    public function testGetApplicationIdReturnsId($providedValue){

        $fakeApplicationIdSetterDeclaration = function() use($providedValue){
            return $this->applicationId = $providedValue;
        };

        $fakeApplicationIdSetterExecution = \Closure::bind(
            $fakeApplicationIdSetterDeclaration,
            $this->ltiTool,
            LtiTool::class
        );

        $fakeApplicationIdSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getApplicationId());
    }




    public function provideIds(){
        return array(
            array(1),
            array(112),
            array(1000),
        );
    }
}