<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkClassroom;
use Learn\Entity\Activity;


class ActivityLinkClassroomRepository extends EntityRepository
{
    public function getRetroAttributedActivitiesByClassroom($classroom){
        
        $retroAttributedActivities = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('alc')
            ->from(ActivityLinkClassroom::class,'alc')
            ->where("alc.classroom = :classroom AND (alc.dateEnd >= :dateTrigger OR alc.dateEnd IS NULL)")
            ->setParameter('classroom', $classroom)
            ->setParameter('dateTrigger', new \DateTime('now'))
            ->getQuery()
            ->getResult();

        return $retroAttributedActivities;
    }
}
