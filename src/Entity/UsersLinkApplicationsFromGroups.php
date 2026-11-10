<?php

namespace Classroom\Entity;

use User\Entity\User;
use Classroom\Entity\Groups;
use Doctrine\ORM\Mapping as ORM;
use Classroom\Entity\Applications;


#[ORM\Table(name: 'classroom_users_link_applications_from_groups')]
#[ORM\Entity(repositoryClass: \Classroom\Repository\UsersLinkApplicationsFromGroupsRepository::class)]
class UsersLinkApplicationsFromGroups
{

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;


    /**
     * @var Applications
     */
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Classroom\Entity\Applications::class)]
    private $application;

    /**
     * @var User
     */
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \User\Entity\User::class)]
    private $user;

    /**
     * @var Groups
     */
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    #[ORM\ManyToOne(targetEntity: \Classroom\Entity\Groups::class)]
    private $group;


    /**
     * @return Integer
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Applications
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param Applications $app
     * @return Applications
     */
    public function setApplication(Applications $app): self
    {
        $this->application = $app;
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
        return $this->group;
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
            'application' => $this->getApplication(),
            'user' => $this->getUser(),
            'group' => $this->getGroup()
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
