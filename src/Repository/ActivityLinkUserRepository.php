<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Learn\Entity\Activity;


class ActivityLinkUserRepository extends EntityRepository
{

    function getOwnedActivitiesCount($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('COUNT(t)')
            ->from(Activity::class, 't')
            ->where('(t.user = ' .  $userId . ' AND t.isFromClassroom = true)');
        $query = $queryBuilder->getQuery();
        return intVal($query->getSingleScalarResult());
    }


    function getTodoActivitiesCount($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND t.note =0 AND t.tries<' . ActivityLinkUser::MAX_TRIES . ' AND t.course IS NULL)');
        $query = $queryBuilder->getQuery();
        return intVal($query->getSingleScalarResult());
    }
    function getDoneActivitiesCount($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND (t.note >0 OR t.tries>' . ActivityLinkUser::MAX_TRIES . ') AND t.course IS NULL)');
        $query = $queryBuilder->getQuery();
        return intVal($query->getSingleScalarResult());
    }
    function getTodoCoursesCount($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND t.note =0 AND t.tries<' . ActivityLinkUser::MAX_TRIES . ' AND t.course IS NOT NULL)');
        $query = $queryBuilder->getQuery();
        return intVal($query->getSingleScalarResult());
    }
    function getDoneCoursesCount($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('COUNT(t)')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND (t.note >0 OR t.tries>' . ActivityLinkUser::MAX_TRIES . ') AND t.course IS NOT NULL)');
        $query = $queryBuilder->getQuery();
        return intVal($query->getSingleScalarResult());
    }
    function getNewActivities($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND (t.correction IS NULL OR t.correction = 0))');
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
    function getCurrentActivities($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND  t.correction = 1)');
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
    function getDoneActivities($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND t.correction > 1)');
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
    function getSavedActivities($userId)
    {
        $queryBuilder = $this->getEntityManager()
            ->createQueryBuilder();
        $queryBuilder->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = ' .  $userId . ' AND (t.correction IS NULL OR t.correction = 0) AND t.project IS NOT NULL)');
        $query = $queryBuilder->getQuery();
        return $query->getResult();
    }
}
