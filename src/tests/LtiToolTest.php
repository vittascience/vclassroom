<?php

namespace Classroom\Tests;

use Classroom\Entity\Applications;
use Classroom\Entity\LtiTool;
use PHPUnit\Framework\TestCase;
use Utils\Exceptions\EntityDataIntegrityException;

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

    public function testGetApplicationIsNullByDefault(){
        $this->assertNull($this->ltiTool->getApplication());
    }

    /** @dataProvider provideValidApplicationValues */
    public function testGetApplicationReturnsApplicationObject($providedValue){

        $fakeApplicationIdSetterDeclaration = function() use($providedValue){
            return $this->application = $providedValue;
        };

        $fakeApplicationIdSetterExecution = \Closure::bind(
            $fakeApplicationIdSetterDeclaration,
            $this->ltiTool,
            LtiTool::class
        );

        $fakeApplicationIdSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getApplication());
    }

    /** @dataProvider provideInvalidApplicationValues */
    public function testSetApplicationRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setApplication($providedValue);
    }

    /** @dataProvider provideValidApplicationValues */
    public function testSetApplicationAcceptsValidValue($providedValue){
        $this->ltiTool->setApplication($providedValue);

        $this->assertInstanceOf(Applications::class,$this->ltiTool->getApplication());
    }

    public function testGetClientIdIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getClientId());
    }

    /** @dataProvider provideStringValues */
    public function testGetClientIdReturnsValidValue($providedValue){
        $fakeClientIdSetterDeclaration = function() use($providedValue){
            return $this->clientId = $providedValue;
        };

        $fakeClientIdSetterExecution = \Closure::bind(
            $fakeClientIdSetterDeclaration,
            $this->ltiTool,
            LtiTool::class
        );

        $fakeClientIdSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getClientId());

    }

    /** @dataProvider provideNonStringValue */
    public function testSetClientIdRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setClientId($providedValue);
    }

    public function testGetDeploymentIdIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getDeploymentId());
    }

    /** @dataProvider provideStringValues */
    public function testGetDeploymentIdReturnsValue($providedValue){

        $fakeDeploymentIdSetterDeclaration = function() use($providedValue){
            return $this->deploymentId = $providedValue;
        };

        $fakeDeploymentIdSetterExecution = \Closure::bind(
            $fakeDeploymentIdSetterDeclaration,
            $this->ltiTool,
            LtiTool::class
        );

        $fakeDeploymentIdSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getDeploymentId());
    }


    /** @dataProvider provideNonStringValue */
    public function testSetDeploymentIdRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setDeploymentId($providedValue);
    }

    /** @dataProvider provideStringValues */
    public function testSetDeploymentIdAcceptsValidValue($providedValue){
        $this->assertEquals('', $this->ltiTool->getDeploymentId());

        $this->ltiTool->setDeploymentId($providedValue);

        $this->assertEquals($providedValue, $this->ltiTool->getDeploymentId());
    }

    public function testGetToolUrlIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getToolUrl());
    }

    /** @dataProvider provideUrls */
    public function testGetToolUrlReturnsUrl($providedValue){
        $fakeGetToolUrlSetterDeclaration = function() use($providedValue){
            return $this->toolUrl = $providedValue;
        };

        $fakeGetToolUrlSetterExecution = $fakeGetToolUrlSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class
        );

        $fakeGetToolUrlSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getToolUrl());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetToolUrlRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setToolUrl($providedValue);
    }

    /** @dataProvider provideUrls */
    public function testSetToolUrlAcceptsValidValue($providedValue){
        $this->assertSame('', $this->ltiTool->getToolUrl());

        $this->ltiTool->setToolUrl($providedValue);
        $this->assertSame($providedValue, $this->ltiTool->getToolUrl());
    }

    public function testGetPublicKeySetIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getPublicKeySet());
    }

    /** @dataProvider provideUrls */
    public function testGetPublicKeySetReturnsValue($providedValue){
        $fakePublicKeySetSetterDeclaration = function() use ($providedValue){
            return $this->publicKeySet = $providedValue;
        };

        $fakePublicKeySetSetterExecution = $fakePublicKeySetSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class 
        );

        $fakePublicKeySetSetterExecution();

        $this->assertSame($providedValue, $this->ltiTool->getPublicKeySet());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetPublicKeySetRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setPublicKeySet($providedValue);
    }

    /** @dataProvider provideUrls */
    public function testSetPublicKeySetAcceptsValidValue($providedValue){
        $this->assertSame('', $this->ltiTool->getPublicKeySet());

        $this->ltiTool->setPublicKeySet($providedValue);
        $this->assertSame($providedValue, $this->ltiTool->getPublicKeySet());
    }




    /** dataProvider for testGetIdReturnsId */
    public function provideIds(){
        return array(
            array(1),
            array(112),
            array(1000),
        );
    }

    /** dataProvider for testSetApplicationRejectsInvalidValue */
    public function provideInvalidApplicationValues(){
        return array(
            array(new \stdClass),
            array(true),
            array(1),
            array([]),
            array('null'),
        );
    }

    /** dataProvider for testSetApplicationAcceptsValidValue*/
    public function provideValidApplicationValues(){
        $application1 = new Applications;
        $application1->setName('some name 1');
        $application2 = new Applications;
        $application2->setName('some name 2');
        $application3 = new Applications;
        $application3->setName('some name 3');
        return array(
            array($application1),
            array($application2),
            array($application3)
        );
    }

    /** 
     * dataProvider for 
     * => testGetClientIdReturnsValidValue 
     * => 
     */
    public function provideStringValues(){
        return array(
            array('test-lms_vittasciences'),
            array('48f36e10-c1c1-4df0-af8b-85c857d1634a'),
            array('opensteam-lms_vittascience'),
            array('1'),
            array('10'),
        );
    }

    /** 
     * dataProvider for 
     * => testGetClientIdRejectsInValidValue 
     * => testSetDeploymentIdRejectsInvalidValue
     * => testSetToolUrlRejectsInvalidValue
     * => testSetPublicKeySetRejectsInvalidValue
     * */
    public function provideNonStringValue(){
        return array(
            array(new \stdClass),
            array(true),
            array(1),
            array([])
        );
    }

    /** 
     * dataProvider for 
     * => testGetToolUrlReturnsUrl 
     * => testSetPublicKeySetAcceptsValidValue
     * */
    public function provideUrls(){
        return array(
            array('https://fr.vittascience.com/python/?mode=mixed&console=right'),
            array('https://goole.com'),
            array('https://fr.vittascience.com'),
        );
    }
   
}