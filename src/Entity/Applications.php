<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\ApplicationsRepository")
 * @ORM\Table(name="classroom_applications")
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
     * @ORM\Column(name="is_lti", type="boolean", nullable=true)
     * @var bool
     */
    private $isLti;

    /**
     * @ORM\Column(name="color", type="string", length=10, nullable=true)
     * @var string
     */
    private $color;


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


    public function getIsLti(): ?bool
    {
        return $this->isLti;
    }

    public function setIsLti(bool $isLti): self
    {
        $this->isLti = $isLti;

        return $this;
    }


    // color fields
    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

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
