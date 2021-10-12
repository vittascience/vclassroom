<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use Utils\Exceptions\EntityDataIntegrityException;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\GroupsRepository")
 * @ORM\Table(name="classroom_groups")
 */
class Groups implements \JsonSerializable, \Utils\JsonDeserializer
{
    const ALPHANUMERIC = "abcdefghijklmnopqrstuvwxyz0123456789";

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
     * @ORM\Column(name="link", type="string", length=5, nullable=false)
     * @var string
     */
    private $link;

    /**
     * @ORM\Column(name="description", type="string", length=255, nullable=false)
     * @var string
     */
    private $description;


    /**
     * @return Int
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
     * @return Groups
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
     * @return Groups
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return String
     */
    public function getLink(): ?string
    {
        return $this->link;
    }

    /**
     * @param String $link
     * @return Groups
     */
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
            'link' => $this->getLink()
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
