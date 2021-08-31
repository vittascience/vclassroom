<?php

namespace Classroom\Repository;

use Classroom\Entity\UsersLinkApplications;
use Doctrine\ORM\EntityRepository;
use User\Entity\User;

class UsersLinkApplicationsRepository extends EntityRepository
{
    public function getAllMembersFromApplication(int $application_id) {

        $queryBuilder = $this->getEntityManager()
        ->createQueryBuilder();

        $queryBuilder->select("u")
            ->from(UsersLinkApplications::class,'g')
            ->innerJoin(User::class,'u')
            ->where('g.application = :id AND u.id = g.user')
            ->setParameter('id',$application_id);
        $result = $queryBuilder->getQuery()->getResult();
        $Result_Users=[];
        foreach ($result as $key => $value) {
            $Result_Users[] = $value->jsonSerialize();
        }
        return $Result_Users;
    }
}
