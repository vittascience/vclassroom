<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ClassroomRepository")
 * @ORM\Table(name="classrooms")
 */
class Classroom implements \JsonSerializable, \Utils\JsonDeserializer
{
    const MAX_PICTURE_SIZE = 10000000;
    const ALPHANUMERIC = "abcdefghijklmnopqrstuvwxyz0123456789";
    /** 
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @var string
     */
    private $name;
    /**
     * @ORM\Column(name="school", type="string", length=255, nullable=false)
     * @var string
     */
    private $school;
    /**
     * @ORM\Column(name="groupe", type="string", length=255, nullable=true)
     * @var string
     */
    private $groupe;
    /**
     * @ORM\Column(name="link", type="string", length=5, nullable=false)
     * @var string
     */
    private $link;

    /**
     * @ORM\Column(name="is_changed", type="boolean", nullable=true)
     * @var bool
     */
    private $isChanged = false;

    public function __construct($name = "default", $school = "default")
    {
        $this->setName($name);
        $this->setSchool($school);
        $this->setLink();
        $this->setIsChanged(false);
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
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else
            throw new EntityDataIntegrityException("id needs to be integer and positive");
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function setName($name)
    {
        if (is_string($name) && strlen($name) > 0 && strlen($name) < 255) {
            $this->name = $name;
        } else {
            throw new EntityDataIntegrityException("name needs to be string with a lenght greater than 0 and lesser than 255");
        }
    }

    /**
     * @return string
     */
    public function getSchool()
    {
        return $this->school;
    }
    /**
     * @param string $school
     */
    public function setSchool($school)
    {
        if (is_string($school) && strlen($school) < 255) {
            $this->school = $school;
        } else {
            throw new EntityDataIntegrityException("name needs to be string with a lenght lesser than 255");
        }
    }

    /**
     * @return string
     */
    public function getGroupe()
    {
        return $this->groupe;
    }
    /**
     * @param string $groupe
     */
    public function setGroupe($groupe)
    {
        if (is_string($groupe) && strlen($groupe) < 255) {
            $this->groupe = $groupe;
        } else {
            throw new EntityDataIntegrityException("groupe needs to be string with a lenght lesser than 255");
        }
    }
    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }
    /**
     * @param string $link
     */
    public function setLink()
    {
        $link = "";
        for ($i = 0; $i < 5; $i++) {
            $link .= substr(self::ALPHANUMERIC, rand(0, 35), 1);
        }
        if (preg_match('/[0-9a-z]{5}/', $link)) {
            $this->link = $link;
        } else {
            throw new EntityDataIntegrityException("link needs to be alphanumerical string with 5 characters");
        }
        $this->link = $link;
    }

    /**
     * @return bool
     */
    public function getIsChanged()
    {
        return $this->isChanged;
    }
    /**
     * @param bool $isChanged
     */
    public function setIsChanged($isChanged)
    {
        if (is_bool($isChanged)) {
            $this->isChanged = $isChanged;
        } else {
            throw new EntityDataIntegrityException("isChanged needs to be boolean");
        }
    }


    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'school' => $this->getSchool(),
            'groupe' => $this->getGroupe(),
            'link' => $this->getLink(),
            'isChanged' => $this->getIsChanged(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self(self::linkGenerator());
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
    public static function linkGenerator()
    {
        return "12345";
    }
}
