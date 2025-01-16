<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;

#[ORM\Entity(repositoryClass: "Classroom\Repository\ClassroomRepository")]
#[ORM\Table(name: "classrooms")]
class Classroom implements \JsonSerializable, \Utils\JsonDeserializer
{
    const MAX_PICTURE_SIZE = 10000000;
    const ALPHANUMERIC = "abcdefghijklmnopqrstuvwxyz0123456789";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private $name;

    #[ORM\Column(name: "school", type: "string", length: 255, nullable: false)]
    private $school;

    #[ORM\Column(name: "gar_code", type: "string", length: 255, nullable: true)]
    private $garCode;

    #[ORM\Column(name: "link", type: "string", length: 5, nullable: false)]
    private $link;

    #[ORM\Column(name: "is_changed", type: "boolean", nullable: true)]
    private $isChanged = false;

    #[ORM\Column(name: "is_blocked", type: "boolean", nullable: true)]
    private $isBlocked = false;

    #[ORM\Column(name: "uai", type: "string", nullable: true)]
    private $uai;

    public function __construct($name = "default", $school = "default")
    {
        $this->setName($name);
        $this->setSchool($school);
        $this->setIsChanged(false);
        $this->setIsBlocked(false);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        if (is_int($id) && $id > 0) {
            $this->id = $id;
        } else {
            throw new EntityDataIntegrityException("id needs to be integer and positive");
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        if (is_string($name) && strlen($name) > 0 && strlen($name) < 255) {
            $this->name = $name;
        } else {
            throw new EntityDataIntegrityException("name needs to be string with a length greater than 0 and lesser than 255");
        }
    }

    public function getSchool()
    {
        return $this->school;
    }

    public function setSchool($school)
    {
        if (is_string($school) && strlen($school) < 255) {
            $this->school = $school;
        } else {
            throw new EntityDataIntegrityException("name needs to be string with a length lesser than 255");
        }
    }

    public function getGarCode()
    {
        return $this->garCode;
    }

    public function setGarCode($garCode)
    {
        if (is_string($garCode) && strlen($garCode) < 255) {
            $this->garCode = $garCode;
        } else {
            throw new EntityDataIntegrityException("gar code needs to be string with a length lesser than 255");
        }
    }

    public function getLink()
    {
        return $this->link;
    }

    public function setLink($link)
    {
        if (!is_string($link) || !preg_match('/[0-9a-z]{5}/', $link)) {
            throw new EntityDataIntegrityException("The link needs to be alphanumerical string with 5 characters");
        }
        $this->link = $link;
    }

    public function getIsChanged()
    {
        return $this->isChanged;
    }

    public function setIsChanged($isChanged)
    {
        if (is_bool($isChanged)) {
            $this->isChanged = $isChanged;
        } else {
            throw new EntityDataIntegrityException("isChanged needs to be boolean");
        }
    }

    public function getIsBlocked()
    {
        return $this->isBlocked;
    }

    public function setIsBlocked($isBlocked)
    {
        if ($isBlocked == "false") {
            $isBlocked = false;
        }
        if ($isBlocked == "true") {
            $isBlocked = true;
        }
        if (is_bool($isBlocked)) {
            $this->isBlocked = $isBlocked;
        } else {
            throw new EntityDataIntegrityException("isBlocked needs to be boolean");
        }
    }

    public function getUai()
    {
        return $this->uai;
    }

    public function setUai($uai)
    {
        if (!is_string($uai)) {
            throw new EntityDataIntegrityException("The uai has to be a string");
        }
        $this->uai = $uai;

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'school' => $this->getSchool(),
            'garCode' => $this->getGarCode(),
            'link' => $this->getLink(),
            'isChanged' => $this->getIsChanged(),
            'isBlocked' => $this->getIsBlocked(),
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
