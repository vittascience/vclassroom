<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\RestrictionsRepository")
 * @ORM\Table(name="restrictions")
 */
class Restrictions implements \JsonSerializable, \Utils\JsonDeserializer
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
     * @ORM\Column(type="json", nullable=true)
     * @var array
     */
    private $restrictions;


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
     * @return Restrictions
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Json
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * @param Json $description
     * @return Restrictions
     */
    public function setRestrictions($restrictions): self
    {
        $this->restrictions = $restrictions;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'restrictions' => $this->getRestrictions()
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
