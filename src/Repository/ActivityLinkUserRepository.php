<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Learn\Entity\Activity;


class ActivityLinkUserRepository extends EntityRepository
{

    private $maxTries = ActivityLinkUser::MAX_TRIES;

    function getOwnedActivitiesCount($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t)')
            ->from(Activity::class, 't')
            ->where('t.user = :id AND t.isFromClassroom = true')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }


    function getTodoActivitiesCount($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND t.note =0 AND t.tries < :maxTries AND t.course IS NULL)')
            ->setParameters(['id' => $userId, 'maxTries' => $this->maxTries])
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }

    function getDoneActivitiesCount($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND (t.note >0 OR t.tries > :maxTries) AND t.course IS NULL)')
            ->setParameters(['id' => $userId, 'maxTries' => $this->maxTries])
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }

    function getTodoCoursesCount($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND t.note =0 AND t.tries < :maxTries AND t.course IS NOT NULL)')
            ->setParameters(['id' => $userId, 'maxTries' => $this->maxTries])
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }

    function getDoneCoursesCount($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND (t.note >0 OR t.tries > :maxTries) AND t.course IS NOT NULL)')
            ->setParameters(['id' => $userId, 'maxTries' => $this->maxTries])
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }

    function getNewActivities($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND (t.correction IS NULL OR t.correction = 0) AND t.project IS NULL AND t.response IS NULL)')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
        return $query;
    }

    function getCurrentActivities($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND  t.correction = 1)')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
        return $query;
    }

    function getDoneActivities($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND t.correction > 1)')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
        return $query;
    }

    function getSavedActivities($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND (t.correction IS NULL OR t.correction = 0) AND (t.project IS NOT NULL OR t.response IS NOT NULL))')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
        return $query;
    }
}
