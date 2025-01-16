<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;


#[ORM\Entity(repositoryClass: "Classroom\Repository\GroupsRepository")]
#[ORM\Table(name: "classroom_groups")]
class Groups implements \JsonSerializable, \Utils\JsonDeserializer
{
    const ALPHANUMERIC = "abcdefghijklmnopqrstuvwxyz0123456789";

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private $name;

    #[ORM\Column(name: "link", type: "string", length: 5, nullable: false)]
    private $link;

    #[ORM\Column(name: "description", type: "string", length: 255, nullable: false)]
    private $description;

    #[ORM\Column(name: "date_begin", type: "datetime", nullable: true)]
    private $dateBegin = null;

    #[ORM\Column(name: "date_end", type: "datetime", nullable: true)]
    private $dateEnd = null;

    #[ORM\Column(name: "max_students", type: "integer", nullable: true)]
    private $maxStudents;

    #[ORM\Column(name: "max_teachers", type: "integer", nullable: true)]
    private $maxTeachers;

    #[ORM\Column(name: "max_students_per_teachers", type: "integer", nullable: true)]
    private $maxStudentsPerTeachers;

    #[ORM\Column(name: "max_classrooms_per_teachers", type: "integer", nullable: true)]
    private $maxClassroomsPerTeachers;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getDateBegin(): ?\DateTime
    {
        return $this->dateBegin;
    }

    public function setDateBegin(\DateTime $dateBegin): self
    {
        $this->dateBegin = $dateBegin;
        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTime $dateEnd): self
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    public function getmaxStudents()
    {
        return $this->maxStudents;
    }

    public function setmaxStudents(Int $maximum): self
    {
        $this->maxStudents = $maximum;
        return $this;
    }

    public function getmaxTeachers(): ?string
    {
        return $this->maxTeachers;
    }

    public function setmaxTeachers(Int $maximum): self
    {
        $this->maxTeachers = $maximum;
        return $this;
    }

    public function getmaxStudentsPerTeachers()
    {
        return $this->maxStudentsPerTeachers;
    }

    public function setmaxStudentsPerTeachers(Int $maximum): self
    {
        $this->maxStudentsPerTeachers = $maximum;
        return $this;
    }

    public function getmaxClassroomsPerTeachers()
    {
        return $this->maxClassroomsPerTeachers;
    }

    public function setmaxClassroomsPerTeachers(Int $maximum): self
    {
        $this->maxClassroomsPerTeachers = $maximum;
        return $this;
    }

    public function setLink(): self
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
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'link' => $this->getLink(),
            'date_begin' => $this->getDateBegin(),
            'date_end' => $this->getDateEnd(),
            'max_students_per_group' => $this->getmaxStudents(),
            'max_teachers_per_group' => $this->getmaxTeachers(),
            'max_students_per_teachers' => $this->getmaxStudentsPerTeachers(),
        ];
    }

    public static function jsonDeserialize($jsonDecoded)
    {
        $classInstance = new self();
        foreach ($jsonDecoded as $attributeName => $attributeValue) {
            $classInstance->{$attributeName} = $attributeValue;
        }
        return $classInstance;
    }
}
