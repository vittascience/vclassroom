<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;
use User\Entity\User;

class ClassroomLinkUserRepository extends EntityRepository
{
    public function getAllStudentsInClassroom($classroom, $rights)
    {
        $students = $this->_em->createQuery('SELECT u FROM Classroom\Entity\ClassroomLinkUser u WHERE u.classroom = ' . $classroom . 'AND u.rights=' . $rights)
            ->getResult();
        $arrayStudents = [];
        foreach ($students as $s) {
            $activities = $this->_em->createQuery('SELECT t FROM Classroom\Entity\ActivityLinkUser t WHERE t.user = ' . $s->getUser()->getId())
                ->getResult();
            $arrayStudents[] = array('user' => $s->getUser()->jsonSerialize(), 'activities' => $activities, 'pwd' => $s->getUser()->getPassword());
        }
        return $arrayStudents;
    }

    public function getTeacherClassrooms($teacherId,$uai){
        $classrooms = $this->getEntityManager()
                                ->createQueryBuilder()
                                ->select('c.name')
                                ->from(Classroom::class,'c')
                                ->leftJoin(ClassroomLinkUser::class,'clu','WITH','c.id = clu.classroom')
                                ->where('clu.user = :teacherId')
                                ->andWhere('c.uai = :uai')
                                ->setParameters(array('teacherId'=>$teacherId,'uai'=>$uai))
                                ->getQuery()
                                ->getResult();
        return  $classrooms;
        /* 
        // another query that procude the same results as above
        $query = $this->_em->createQuery("
            SELECT c.name
            FROM Classroom\Entity\Classroom c
            LEFT JOIN Classroom\Entity\ClassroomLinkUser clu
            WITH c.id = clu.classroom
            WHERE clu.user=$teacherId

        ");
        $classrooms = $query->getResult(); 
        */
       
    }

    public function getTeacherClassroomBy($teacherId,$classroomName,$uai,$relatedGroup){
        $classroom = $this->getEntityManager()
                            ->createQueryBuilder()
                            ->select('c')
                            ->from(Classroom::class,'c')
                            ->leftJoin(ClassroomLinkUser::class,'clu','WITH','c.id = clu.classroom')
                            ->where('clu.user = :teacherId')
                            ->andWhere('c.name = :classroomName')
                            ->andWhere('c.uai = :uai')
                            ->andWhere('c.groupe = :relatedGroup')
                            ->setParameters(array(
                                'teacherId' => $teacherId,
                                'classroomName' => $classroomName,
                                'uai' => $uai,
                                'relatedGroup' => $relatedGroup
                            ))
                            ->getQuery()
                            ->getResult();
        return $classroom;
    }

    public function getStudentAndClassroomByIds($studentId,$classroomId){
        $studentClassrooms = $this->getEntityManager()
                                ->createQueryBuilder()
                                ->select('clu')
                                ->from(ClassroomLinkUser::class,'clu')
                                ->leftJoin(User::class,'u','WITH','u.id = clu.user')
                                ->Join(Classroom::class,'c','WITH','c.id = clu.classroom')
                                ->where('c.id = :classroomId')
                                ->andWhere('u.id = :studentId')
                                ->setParameters(array(
                                    'classroomId' => $classroomId,
                                    'studentId' => $studentId
                                ))
                                ->getQuery()
                                ->getOneOrNullResult();
        return $studentClassrooms;
        /* $studentClassrooms = $this->getEntityManager()
                                ->createQueryBuilder()
                                ->select('c,clu')
                                ->from(Classroom::class,'c')
                                ->join(ClassroomLinkUser::class,'clu','WITH','c.id = clu.classroom')
                                ->join(User::class,'u','WITH','u.id = clu.user')
                                ->where('c.id = :classroomId')
                                ->andWhere('u.id = :studentId')
                                ->setParameters(array(
                                    'classroomId' => $classroomId,
                                    'studentId' => $studentId
                                ))
                                ->getQuery()
                                ->getResult();
        return $studentClassrooms; */
    }

    public function getStudentClassroomsAndRelatedTeacher($classroomName,$uai){
        $studentClassroomsAndRelatedTeacher = $this->getEntityManager()
                                                    ->createQueryBuilder()
                                                    ->select('c.name,c.groupe,c.link,u.pseudo AS teacher,clu.rights')
                                                    ->from(Classroom::class,'c')
                                                    ->Join(ClassroomLinkUser::class,'clu','WITH','c.id = clu.classroom')
                                                    ->join(User::class,'u','WITH','u.id = clu.user')
                                                    ->where('c.name = :classroomName')
                                                    ->andWhere('c.uai = :uai')
                                                    ->andWhere('clu.rights = :rights')
                                                    ->setParameters(array(
                                                        'classroomName'=> $classroomName,
                                                        'uai'=> $uai,
                                                        'rights'=> 2
                                                    ))
                                                    ->getQuery()
                                                    ->getResult();
        return $studentClassroomsAndRelatedTeacher;
    }
}
