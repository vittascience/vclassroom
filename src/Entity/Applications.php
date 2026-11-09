<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Table(name: 'classroom_applications')]
#[ORM\Entity(repositoryClass: \Classroom\Repository\ApplicationsRepository::class)]
class Applications
{

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: false)]
    private $name;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'text', length: 1500, nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[ORM\Column(name: 'image', type: 'string', length: 255, nullable: true)]
    private $image;


    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_lti', type: 'boolean', nullable: true)]
    private $isLti;

    /**
     * @var string
     */
    #[ORM\Column(name: 'color', type: 'string', length: 10, nullable: true)]
    private $color;

    /**
     * @var integer
     */
    #[ORM\Column(name: 'max_per_teachers', type: 'integer', nullable: true)]
    private $maxPerTeachers;


    /**
     * @var integer
     */
    #[ORM\Column(name: 'sort', type: 'integer', nullable: true)]
    private $sort;

    /**
     * @var string
     */
    #[ORM\Column(name: 'background_image', type: 'string', length: 255, nullable: true)]
    private $backgroundImage;


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

    /**
     * @return Mixed
     */
    public function getMaxPerTeachers()
    {
        return $this->maxPerTeachers;
    }

    /**
     * @param mixed $maximum
     * @return Applications
     */
    public function setMaxPerTeachers($maximum): self
    {
        $this->maxPerTeachers = $maximum;
        return $this;
    }

    /**
     * @return Mixed
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param mixed $sort
     * @return Applications
     */
    public function setSort($sort): self
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @return Mixed
     */
    public function getBackgroundImage()
    {
        return $this->backgroundImage;
    }

    /**
     * @param mixed $backgroundImage
     * @return Applications
     */
    public function setBackgroundImage($backgroundImage): self
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
