<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;
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
    
    public function getStudentsActivityByClassroomAndActivityRef($classroomId, $reference){
        $studentsActivities = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('alu')
            ->from(ActivityLinkUser::class,'alu')
            ->leftJoin(ClassroomLinkUser::class,'clu','WITH','clu.user=alu.user')
            ->andWhere('clu.classroom= :classroomId AND alu.reference= :reference')
            ->setParameters(
                array(
                    'classroomId' => $classroomId,
                    'reference' => $reference
                )
            )
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
