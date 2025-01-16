<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: "Classroom\Repository\ApplicationsRepository")]
#[ORM\Table(name: "classroom_applications")]
class Applications
{
    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private $name;

    #[ORM\Column(name: "description", type: "text", length: 1500, nullable: true)]
    private $description;

    #[ORM\Column(name: "image", type: "string", length: 255, nullable: true)]
    private $image;

    #[ORM\Column(name: "is_lti", type: "boolean", nullable: true)]
    private $isLti;

    #[ORM\Column(name: "color", type: "string", length: 10, nullable: true)]
    private $color;

    #[ORM\Column(name: "max_per_teachers", type: "integer", nullable: true)]
    private $maxPerTeachers;

    #[ORM\Column(name: "sort", type: "integer", nullable: true)]
    private $sort;

    #[ORM\Column(name: "background_image", type: "string", length: 255, nullable: true)]
    private $backgroundImage;

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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getIsLti(): ?bool
    {
        return $this->isLti;
    }

    public function setIsLti(bool $isLti): self
    {
        $this->isLti = $isLti;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getMaxPerTeachers(): ?int
    {
        return $this->maxPerTeachers;
    }

    public function setMaxPerTeachers(?int $maximum): self
    {
        $this->maxPerTeachers = $maximum;
        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    public function getBackgroundImage(): ?string
    {
        return $this->backgroundImage;
    }

    public function setBackgroundImage(?string $backgroundImage): self
    {
        $this->backgroundImage = $backgroundImage;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'image' => $this->getImage(),
            'isLti' => $this->getIsLti(),
            'color' => $this->getColor(),
            'max_per_teachers' => $this->getMaxPerTeachers(),
            'sort' => $this->getSort(),
            'background_image' => $this->getBackgroundImage()
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
