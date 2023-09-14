<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\UsersLinkGroupsRepository")
 * @ORM\Table(name="classroom_users_link_groups")
 */
class UsersLinkGroups implements \JsonSerializable, \Utils\JsonDeserializer
{
    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="User\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\Groups")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Groups
     */
    private $group;

    /**
     * @ORM\Column(name="rights", type="integer", length=2, nullable=true)
     * 0=user, 1=admin (TBD : Classroom come from admin status on user_regular table @ is_admin)
     * @var integer
     */
    private $rights;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user->getId();
    }

    /**
     * @return Int
     */
    public function getId(): ?int
    {
        return $this->id;
    }


    /**
     * @param User $user
     * @return UsersLinkGroups
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Groups
     */
    public function getGroup()
    {
        return $this->group->getId();
    }

    /**
     * @param Groups $group
     * @return UsersLinkGroups
     */
    public function setGroup(Groups $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Integer
     */
    public function getRights(): ?int
    {
        return $this->rights;
    }

    /**
     * @param Integer $rights
     * @return UsersLinkGroups
     */
    public function setRights(int $rights): self
    {
        $this->rights = $rights;
        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'group_id' => $this->getGroup(),
            'user_id' => $this->getUser(),
            'rights' => $this->getRights()
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
