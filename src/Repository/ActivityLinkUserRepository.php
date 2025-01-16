<?php

namespace Classroom\Repository;

use Learn\Entity\Activity;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;
use Doctrine\Common\Collections\ArrayCollection;


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
            ->where('(t.user = :id AND t.note =0 AND t.tries < :maxTries AND t.course IS NULL AND t.isFromCourse = 0)')
            ->setParameter('id', $userId)
            ->setParameter('maxTries', $this->maxTries)
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
            ->where('(t.user = :id AND (t.note >0 OR t.tries > :maxTries) AND t.course IS NULL AND t.isFromCourse = 0)')
            ->setParameter('id', $userId)
            ->setParameter('maxTries', $this->maxTries)
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
            ->setParameter('id', $userId)
            ->setParameter('maxTries', $this->maxTries)
            ->getQuery()
            ->getSingleScalarResult();
        return intVal($query);
    }

    public function getDoneCoursesCount(int $userId): int
    {
        try {
            $query = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('COUNT(t)')
                ->from(ActivityLinkUser::class, 't')
                ->where('(t.user = :id AND (t.note > 0 OR t.tries > :maxTries) AND t.course IS NOT NULL)')
                ->setParameter('id', $userId)
                ->setParameter('maxTries', $this->maxTries)
                ->getQuery()
                ->getSingleScalarResult();
    
            return intval($query);
        } catch (\Exception $e) {
            // Log or handle the error
            return 0;
        }
    }

    function getNewActivities($userId)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('t')
            ->from(ActivityLinkUser::class, 't')
            ->where('(t.user = :id AND (t.correction IS NULL OR t.correction = 0) AND t.project IS NULL AND t.response IS NULL AND t.isFromCourse = 0)')
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
            ->where('(t.user = :id AND  t.correction = 1 AND t.isFromCourse = 0)')
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
            ->where('(t.user = :id AND t.correction > 1 AND t.isFromCourse = 0)')
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
            ->where('(t.user = :id AND (t.correction IS NULL OR t.correction = 0) AND (t.project IS NOT NULL OR t.response IS NOT NULL) AND t.isFromCourse = 0)')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
        return $query;
    }
    
    public function getStudentsActivityByClassroomAndActivityRef($classroomId, $reference){
        $studentsActivities = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('alu')
            ->from(ActivityLinkUser::class,'alu')
            ->leftJoin(ClassroomLinkUser::class,'clu','WITH','clu.user=alu.user')
            ->andWhere('clu.classroom= :classroomId AND alu.reference= :reference')
            ->setParameter('classroomId', $classroomId)
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getResult();
        return $studentsActivities;
    }

    public function addRetroAttributedActivitiesToStudent($classroomRetroAttributedActivities,$user){
        foreach($classroomRetroAttributedActivities as $classroomRetroAttributedActivity){
            $activity = $classroomRetroAttributedActivity->getActivity();
            $dateBegin = $classroomRetroAttributedActivity->getDateBegin();
            $dateEnd = $classroomRetroAttributedActivity->getDateEnd();
            $evaluation = $classroomRetroAttributedActivity->getEvaluation();
            $autocorrection = $classroomRetroAttributedActivity->getAutocorrection();
            $introduction = $classroomRetroAttributedActivity->getIntroduction();
            $reference = $classroomRetroAttributedActivity->getReference();
            $commentary = $classroomRetroAttributedActivity->getCommentary();

            $course = $classroomRetroAttributedActivity->getCourse();
            $coefficient = $classroomRetroAttributedActivity->getCoefficient();
            $linkActivityToUser = new ActivityLinkUser(
                $activity, 
                $user, 
                $dateBegin,  
                $dateEnd, 
                $evaluation, 
                $autocorrection, 
                "", 
                $introduction, 
                $reference,
                $commentary
            );
            if($course){
                $linkActivityToUser->setCourse($course);
            }
            $linkActivityToUser->setCoefficient($coefficient);

            $this->getEntityManager()->persist($linkActivityToUser);
            $this->getEntityManager()->flush();
            
        }
    }
}
