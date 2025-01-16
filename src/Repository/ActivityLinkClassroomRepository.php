<?php

namespace Classroom\Repository;

use Learn\Entity\Activity;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkClassroom;
use Doctrine\Common\Collections\ArrayCollection;


class ActivityLinkClassroomRepository extends EntityRepository
{
    public function getRetroAttributedActivitiesByClassroom($classroom){
        
        $retroAttributedActivities = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('alc')
            ->from(ActivityLinkClassroom::class,'alc')
            ->where("alc.classroom = :classroom AND alc.dateEnd >= :dateTrigger ")
            ->setParameter('classroom', $classroom)
            ->setParameter('dateTrigger', new \DateTime('now'))
            ->getQuery()
            ->getResult();

        return $retroAttributedActivities;
    }
}
