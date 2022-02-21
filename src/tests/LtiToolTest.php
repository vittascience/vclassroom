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

    public function testGetLoginUrlIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getLoginUrl());
    }

    /** @dataProvider provideUrls */
    public function testGetLoginUrlReturnsValidValue($providedValue){
        $fakeLoginUrlSetterDeclaration = function() use($providedValue){
            return $this->loginUrl = $providedValue;
        };

        $fakeLoginUrlSetterExecution = $fakeLoginUrlSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class 
        );

        $fakeLoginUrlSetterExecution();

        $this->assertSame($providedValue, $this->ltiTool->getLoginUrl());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetLoginUrlRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setLoginUrl($providedValue);
    }

    /** @dataProvider provideUrls */
    public function testSetLoginUrlAcceptsValidValue($providedValue){
        $this->assertSame('', $this->ltiTool->getLoginUrl());

        $this->ltiTool->setLoginUrl($providedValue);
        $this->assertSame($providedValue, $this->ltiTool->getLoginUrl());
    }

    public function testGetRedirectionUrlIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getRedirectionUrl());
    }

    /** @dataProvider provideUrls */
    public function testGetRedirectionUrlReturnsValue($providedValue){
        $fakeRedirectionUrlSetterDeclaration = function() use($providedValue){
            return $this->redirectionUrl = $providedValue;
        };

        $fakeRedirectionUrlExecution = $fakeRedirectionUrlSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class
        );

        $fakeRedirectionUrlExecution();

        $this->assertSame($providedValue, $this->ltiTool->getRedirectionUrl());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetRedirectionUrlRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
    $this->ltiTool->setRedirectionUrl($providedValue);
    }

    /** @dataProvider provideUrls */
    public function testSetRedirectionUrlAcceptsValidValue($providedValue){
        $this->assertSame('', $this->ltiTool->getRedirectionUrl());

        $this->ltiTool->setRedirectionUrl($providedValue);
        $this->assertSame($providedValue, $this->ltiTool->getRedirectionUrl());
    }

    public function testGetDeepLinkIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getDeepLinkUrl());
    }

    /** @dataProvider provideUrls */
    public function testGetDeepLinkUrlReturnsValue($providedValue){
        $fakeDeepLinkUrlSetterDeclaration = function() use($providedValue){
            return $this->deepLinkUrl = $providedValue;
        };

        $fakeDeepLinkUrlSetterExecution = $fakeDeepLinkUrlSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class 
        );

        $fakeDeepLinkUrlSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getDeepLinkUrl());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetDeepLinkUrlRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setDeepLinkUrl($providedValue);
    }

    /** @dataProvider provideUrls */
    public function testSetDeepLinkUrlAcceptsValidValue($providedValue){
        $this->assertSame('',$this->ltiTool->getDeepLinkUrl());

        $this->ltiTool->setDeepLinkUrl($providedValue);
        $this->assertSame($providedValue, $this->ltiTool->getDeepLinkUrl());
    }

    public function testGetPrivateKeyIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getPrivateKey());
    }

    /** @dataProvider providePrivateKeys */
    public function testGetPrivateKeyReturnsValue($providedValue){
        $fakePrivateKeySetterDeclaration = function() use ($providedValue){
            $this->privateKey = $providedValue;
        };

        $fakePrivateKeySetterExecution = $fakePrivateKeySetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class 
        );

        $fakePrivateKeySetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getPrivateKey());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetPrivateKeyRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setPrivateKey($providedValue);
    }

    /** @dataProvider providePrivateKeys */
    public function testSetPrivateKeyAcceptsValidValue($providedValue){
        $this->assertSame('',$this->ltiTool->getPrivateKey());

        $this->ltiTool->setPrivateKey($providedValue);
        $this->assertEquals($providedValue, $this->ltiTool->getPrivateKey());
    }

    public function testGetKidIsNotNullByDefault(){
        $this->assertNotNull($this->ltiTool->getKid());
    }

    /** @dataProvider provideStringValues */
    public function testGetKidReturnsValue($providedValue){
        $fakeKidSetterDeclaration = function() use ($providedValue){
            return $this->kid = $providedValue;
        };

        $fakeKidSetterExecution = $fakeKidSetterDeclaration->bindTo(
            $this->ltiTool,
            LtiTool::class
        );

        $fakeKidSetterExecution();

        $this->assertEquals($providedValue, $this->ltiTool->getKid());
    }

    /** @dataProvider provideNonStringValue */
    public function testSetKidRejectsInvalidValue($providedValue){
        $this->expectException(EntityDataIntegrityException::class);
        $this->ltiTool->setKid($providedValue);
    }

    /** @dataProvider provideStringValues */
    public function testSetKidAcceptsValidValue($providedValue){
        $this->assertEquals('', $this->ltiTool->getKid());

        $this->ltiTool->setKid($providedValue);
        $this->assertEquals($providedValue,$this->ltiTool->getKid() );
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
     * => testGetDeploymentIdReturnsValue
     * => testSetDeploymentIdAcceptsValidValue
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
     * => testSetLoginUrlRejectsInvalidValue
     * => testSetRedirectionUrlAcceptsValidValue
     * => testGetDeepLinkUrlReturnsValue
     * => testSetDeepLinkUrlRejectsInvalidValue
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
     * => testGetLoginUrlReturnsValidValue
     * => testSetLoginUrlAcceptsValidValue
     * => testGetRedirectionUrlReturnsValue
     * => testSetDeepLinkUrlAcceptsValidValue
     * */
    public function provideUrls(){
        return array(
            array('https://fr.vittascience.com/python/?mode=mixed&console=right'),
            array('https://goole.com'),
            array('https://fr.vittascience.com'),
        );
    }

    /**
     *  dataProvider for 
     * => testGetPrivateKeyReturnsValue 
     * => testSetPrivateKeyAcceptsValidValue
     * */
    public function providePrivateKeys(){
        return array(
            array('-----BEGIN RSA PRIVATE KEY-----
            MIIEowIBAAKCAQEA12qnagqeOVhD95g4Xt3Z8MKhzVwu8Du6HWzadvoUqk7xOYHf
            HhulR2ro6nSU+x7l97Eq18PU1Ho2BBxVTsa90YGvWHNOXz5vitmVVg795dSEfkYG
            GOfsDhpliv2S/eQxAGwU7gPukNnGTnHG2UIKlV55i/Lk5gnlE1dvZWwaTm1K6PW6
            Q1OIPkwDyFQ7gB6AplqqC5EvJyynPE7yRrMXRkzWK63fpGMKiJLx9uopGAylCzg3
            MtXBGTL6X0uQBChPunfJzD5oLpT9HZdgbLiZ8tJOUo9tjEo5ZWd/8xlozfASjy0n
            HWQ7opy7o5xFD9vXEMm027YSDOT9lMKox9m35QIDAQABAoIBAC8DYNw8ywFa6SJ9
            Pzg9FNZ9s9Bc4QWfE2ReoGM8+wucRPs3A9nPUMgAZirdHLKdsLTZHq+OVsG0lltZ
            T6jsqPqzYfBc4erZdoCIMhZhGTpyoiPo5mXDH/qH+kdWRiFRDvy3me3EP+mvDZ+L
            J0m9JxAoWUCY1yn5WNxaxb4N1MPuoaZXY66cvjSh02mQEQh+lqT5BeHvBHqlXB7i
            8sw8GsevrAgoIyne9C9TIzB2fpNRLJHclSQWRZJ/Y92Bk2D0k1BXsSI2/4zMESCa
            yfGmLgBrfGvKilV2nGfcyr8Re8IfWgihuazMC8I4CHprBgbN+8HnOWIII9448LD0
            R2PvaNUCgYEA+nin99auSJLd4FvB3Dp//ymEwhZt/c5mvDzhTgp1fgAaAb/229qc
            gBqR+eycGRskZeDrXJm+sE/9OaYThT6y/8q7h2O8YJpxTkaPgyJUehFm6DEEXI5j
            RzI7MKHZsWJCUhnjhEBNSFkB+pWdECkWMrZhTORz6GA3Oi/nP33lrGcCgYEA3Cvp
            2bpe0m3pvlhftYzBAuaH/0emjyhzUj+3yDnBJrqDBPbRWv0fsP7ilhJmkImFZW5m
            kuKgg1bpbYQWt6jpRVL/3u5QRsNzmbU45WQE8oiHEpgxKSeNdOvBUg7ucXaKgdUH
            up+PZXA7nKjF1aGzwGJNJE43li+Jd4rtaS17CdMCgYBSAUj1MvuS1UsBlukswpZ/
            o0dNCGzwqTAnt0MI+xGmtD/PjNs09ilBI/HhQt+EtMdA99f3VHsDXN0Kj95aRMH0
            T5sAY94cPtSUDTQVehrwcFwh71J/Pzsv5zlL3eHZWtNd8A32kdr7sfCc63kl/l2/
            Msk+lJmCXmYWjfKHbh/RRwKBgQCj1gmue4EUFbZabmjKMHNwNRv+WtMWtIMcMU4R
            MOkKaMAWcZRYoQN0MjdqdUbdR3h8girSItJO6d3KIQDGqmrrq1e8DJqwDcF4H+K2
            0DbeQ7o/nAD5HvWki8rPxUyqIgvvkRavSQzr7xhs+yo8Tpf0ETJWUd4LZFRnIHqK
            Sc3FAQKBgG55jUpSZQbS9P/SD1/a1Z0P0kC+ceS16YjfeYR0KqYNc3Ff+tJr6m4P
            MOkmhN3SqY6dVNFFFrEhgdQu6SOgsizKpTt4y8L2wj0sMRGbikinjEIQ+r7F0MYE
            9ypDPx/QLmqNUoFuN8H8e++mvatzZS7VaCOiM4QMSKVak/ebFjcS
            -----END RSA PRIVATE KEY-----'),
            array('-----BEGIN RSA PRIVATE KEY-----
            zsgivsigvoijqù^A8osiSa75nmqmakwNNocLA2N2huWM9At/tjSZOFX1r4+PDclS
            zxhMw+ZcgHH+E/05Ec6Vcfd75i8Z+Bxu4ctbYk2FNIvRMN5UgWqxZ5Pf70n8UFxj
            GqdwhUA7/n5KOFoUd9F6wLKa6OzsoiizgzsepzsrgpzXhZxNrJjCqxSEkLkOK3xJ
            0J2npuZ59kipDEDZkRTWz3al09wQ0nvAgCc96DGH+jCgy0msA0OZQ9SmDE9CCMbD
            T86ogLugPFCvo5g5zqBBX9Ak3czsuLS6Ni9Wco8ZSxoaCIsPXK0RJpt6Jvbjclqb
            4imsobifxy5LsAV0l/weNWmU2DpzJsLgeK6VVwIDAQABAoIBAQC2R1RUdfjJUrOQ
            rWk8so7XVBfO15NwEXhAkhUYnpmPAF/zlgijqszq$psozVIW6bbLKCtuRCVMX9ev
            fIbkkLU0ErhqPi3QATcXL/z1r8+bAUprhpNAg9fvfM/ZukXDRged6MPNMC11nseE
            p8HUU4oHNwXVyL6FvmstrHyYoEnkjIiMk34O2MFjAavoIJhM0gkoXVnxRP5MNi1n
            GPVhK+TfZyRri20x1Rh3CsIq36PUyxCICWkD7jftLGqVdQBfuii600LP5v7nuHz9
            LDsCeY7xRJu0eLdDk7/9ukb8fuq6/+3VYMYChYWvpw4DaH8qDHxZfWzMyaI489ma
            l27lhgdxAoGBAPkxH6WuZM/GOowjySuruRjAVyJ4stfe9l/x8MrqnFA2Q8stqK69
            60Y9LDrSaAx7QutvzZ64br2WMlvnGdJw868z4/JmvoAqW3IHUXzqRAHgOk/8Y3ze
            Sjd7t3R0O3v6qAbQjyRYYgfAMZo7PzXW8FKNGsakAedEKW0b94HYndKpAoGBAPkr
            grtARp2nnd1WGuxgQMjX++HjT0p9x7fTMCtfvYhZguU9AlCx53VHFeGc6fqsDkUm
            BFv0dqMnw0TPzEQqLElBIh87TGS4JSXmcbQcejIx+ry2kMFuyMZIPuvZCnLfB/d7
            Qu2DU6mdeIBME/8AX5kBqn1ekddioESdSkHkkif/AoGAaPCeAjjZ7YHuP/wGCOUN
            UvYU+8hWkIAtwyPxIpMAdusTS6oTwlrqjK7QRIk9FhyGhv2TWwcSY7avyHIfNrco
            eBzjHr7T9MdhsTiRwYgqUZvrEqoX/4rhOFJaZKlaL5DUV+JWlZi+18LBYNEYgoTc
            ufcAUqzYvFrBE1jWt5DQjdkCgYATs6sMn1J2GNDUtYA/fITi3KEgBVc5rqRiFqLS
            aymTZHCDK8XJF6gTj+FdC4k8tuoR8aWal8Phtr0r7bpbEXKbADlwesHZnO3jB0uq
            UC4hVe5biZv8j4P0mbXP9ENtPdFlciuimCW/XaIvktRp71+fu4/9hcLGYxgFFOLQ
            PwCHhQKBgGMCxIcueUkLnI9r0KkjtXap9mIgdgERwQPN0Cm9Tx35ZEzRp95kf4C6
            MPsVOwZk5gNvvQngx4iaw9fNYG+PF2yNuDZ+EFwI0vpmGCKRQEke9/VCOFucMsjg
            jMhbU+jrqRIJKisP7MCE1NRhymCPpQf/stEPl0nS5rj+mZJHQEGq
            -----END RSA PRIVATE KEY-----')
        );
    }
   
}