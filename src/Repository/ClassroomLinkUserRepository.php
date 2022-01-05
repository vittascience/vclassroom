<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;
use User\Entity\User;

class ClassroomLinkUserRepository extends EntityRepository
{
    public function getAllStudentsInClassroom($classroom, $rights, $demoStudent=null)
    {

        $students = $this->getStudentsOrdered($classroom, $rights, $demoStudent);

        $arrayStudents = [];
        foreach ($students as $student) {
            // get the activities for each student
            $activities = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('alu')
                ->from(ActivityLinkUser::class,'alu')
                ->join(User::class, 'u','WITH','alu.user = u.id')
                ->where('alu.user = :userId')
                ->setParameters(array(
                    'userId' => $student->getUser()->getId()
                ))
                ->getQuery()
                ->getResult();
            
            // fill the students array
            $arrayStudents[] = array(
                'user' => $student->getUser()->jsonSerialize(), 
                'activities' => $activities, 
                'pwd' => $student->getUser()->getPassword()
            );
        }
        return $arrayStudents;
    }


    public function getStudentsOrdered($classroomId, $rights, $demoStudent)
    {
        // get all students but not demoStudent
        $tmpStudentsWithoutDemostudent = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('clu')
            ->from(ClassroomLinkUser::class, 'clu')
            ->leftJoin(User::class, 'u', 'WITH', 'clu.user = u.id')
            ->where('clu.rights = :rights')
            ->andWhere('clu.classroom = :classroom')
            ->andWhere('u.pseudo != :demoStudent')
            ->setParameters(array(
                'rights' => $rights,
                'classroom' => $classroomId,
                'demoStudent' => $demoStudent
            ))
            ->orderby('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
        $studentsArr = [];

        // push them into the $studentsArr
        foreach ($tmpStudentsWithoutDemostudent as $tmpStudent) {
            array_push( $studentsArr, $tmpStudent );    
        }

        // get demoStudent and add it to the end of the $studentArr
        $tmpDemostudent = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('clu')
            ->from(ClassroomLinkUser::class, 'clu')
            ->leftJoin(User::class, 'u', 'WITH', 'clu.user = u.id')
            ->where('clu.rights = :rights')
            ->andWhere('clu.classroom = :classroom')
            ->andWhere('u.pseudo = :demoStudent')
            ->setParameters(array(
                'rights' => $rights,
                'classroom' => $classroomId,
                'demoStudent' => $demoStudent
            ))
            ->getQuery()
            ->getOneOrNullResult();
        
            array_unshift($studentsArr,$tmpDemostudent);

        return $studentsArr;
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

    public function getTeacherClassroomBy($teacherId,$classroomName,$uai,$classroomCode){
        $classroom = $this->getEntityManager()
                            ->createQueryBuilder()
                            ->select('c')
                            ->from(Classroom::class,'c')
                            ->leftJoin(ClassroomLinkUser::class,'clu','WITH','c.id = clu.classroom')
                            ->where('clu.user = :teacherId')
                            ->andWhere('c.name = :classroomName')
                            ->andWhere('c.uai = :uai')
                            ->andWhere('c.garCode = :classroomCode')
                            ->setParameters(array(
                                'teacherId' => $teacherId,
                                'classroomName' => $classroomName,
                                'uai' => $uai,
                                'classroomCode' => $classroomCode
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
                                                    ->select('c.id,c.name,c.groupe,c.link,u.pseudo AS teacher,clu.rights')
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
