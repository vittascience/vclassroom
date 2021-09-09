<?php

namespace Classroom\Entity;

use Doctrine\ORM\Mapping as ORM;
use User\Entity\User;


/**
 * @ORM\Entity(repositoryClass="Classroom\Repository\UsersLinkGroupsLinkApplicationsRepository")
 * @ORM\Table(name="applications")
 */
class UsersLinkGroupsLinkApplications
{

    /** 
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Classroom\Entity\GroupsLinkApplications")
     * @ORM\JoinColumn(name="groupslinkapplications_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @var Applications
     */
    private $groupsLinkApplications;

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
     * @return Integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return GroupsLinkApplications
     */
    public function getGroupsLinkApplications()
    {
        return $this->groupsLinkApplications;
    }

    /**
     * @param User $user
     * @return GroupsLinkApplications
     */
    public function setGroupsLinkApplications(GroupsLinkApplications $group): self
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return UsersLinkApplications
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

    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'groups_link_applications' => $this->getGroupsLinkApplications(),
            'user' => $this->getUser()
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
