<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ApplicationsRepository")
 * @ORM\Table(name="applications")
 */
class Applications
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     * @var string
     */
    private $image;

    /**
     * @ORM\Column(name="max_students_per_groups", type="integer", nullable=true)
     * @var integer
     */
    private $maxStudentsPerGroups;

    /**
     * @ORM\Column(name="max_teachers_per_groups", type="integer", nullable=true)
     * @var integer
     */
    private $maxTeachersPerGroups;


    /**
     * @ORM\Column(name="max_students_per_teachers", type="integer", nullable=true)
     * @var integer
     */
    private $maxStudentsPerTeachers;

    /**
     * @return Integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return String
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param String $name
     * @return Applications
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return String
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param String $description
     * @return Applications
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return String
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param String $image
     * @return Applications
     */
    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxStudentsPerGroups()
    {
        return $this->maxStudentsPerGroups;
    }

    /**
     * @param Integer $image
     * @return Applications
     */
    public function setmaxStudentsPerGroups(Int $maximum): self
    {
        $this->maxStudentsPerGroups = $maximum;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxTeachersPerGroups(): ?string
    {
        return $this->maxTeachersPerGroups;
    }

    /**
     * @param Integer $maximum
     * @return Applications
     */
    public function setmaxTeachersPerGroups(Int $maximum): self
    {
        $this->maxTeachersPerGroups = $maximum;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getmaxStudentsPerTeachers()
    {
        return $this->maxStudentsPerTeachers;
    }

    /**
     * @param Integer $maximum
     * @return Applications
     */
    public function setmaxStudentsPerTeachers(Int $maximum): self
    {
        $this->maxStudentsPerTeachers = $maximum;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'image' => $this->getImage(),
            'maxStudentsPerGroup' => $this->getmaxStudentsPerGroups(),
            'maxTeachersPerGroup' => $this->getmaxTeachersPerGroups(),
            'maxStudentsPerTeachers' => $this->getmaxStudentsPerTeachers()
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
