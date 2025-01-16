<?php

namespace Classroom\Repository;

use User\Entity\User;
use Classroom\Entity\Classroom;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\EntityRepository;
use Classroom\Entity\CourseLinkUser;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\ClassroomLinkUser;
use Doctrine\Common\Collections\ArrayCollection;

class ClassroomLinkUserRepository extends EntityRepository
{
    public function getAllStudentsInClassroom($classroom, $rights, $demoStudent=null)
    {

        $students = $this->getStudentsOrdered($classroom, $rights, $demoStudent);

        $arrayStudents = [];
        foreach ($students as $student) {

            if ($student == null) {
                continue;
            }
            
            // get the activities for each student
            $activities = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('alu')
                ->from(ActivityLinkUser::class,'alu')
                ->join(User::class, 'u','WITH','alu.user = u.id')
                ->where('alu.user = :userId')
                ->setParameter('userId', $student->getUser()->getId())
                ->getQuery()
                ->getResult();

            $courseLinkUser = $this->getEntityManager()
                ->createQueryBuilder()
                ->select('clu')
                ->from(CourseLinkUser::class,'clu')
                ->join(User::class, 'u','WITH','clu.user = u.id')
                ->where('clu.user = :userId')
                ->setParameter('userId', $student->getUser()->getId())
                ->getQuery()
                ->getResult();
            
            // fill the students array
            $arrayStudents[] = array(
                'user' => $student->getUser()->jsonSerialize(), 
                'activities' => $activities, 
                'courses' => $courseLinkUser,
                'pwd' => $student->getUser()->getPassword()
            );
        }
        return $arrayStudents;
    }

    public function getDemoStudentWithWrongPseudo($pseudo)
    {
       return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class,'u')
            ->where('u.firstname = :firstname')
            ->andWhere('u.surname = :surname')
            ->andWhere('u.pseudo != :name')
            ->setParameter('firstname', 'élève')
            ->setParameter('surname', 'modèl')
            ->setParameter('name', $pseudo)
            ->getQuery()
            ->getResult();
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
            ->setParameter('rights', $rights)
            ->setParameter('classroom', $classroomId)
            ->setParameter('demoStudent', $demoStudent)
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
            ->andWhere('u.firstname = :firstname')
            ->andWhere('u.surname = :surname')
            ->setParameter('rights', $rights)
            ->setParameter('classroom', $classroomId)
            ->setParameter('demoStudent', $demoStudent)
            ->setParameter('firstname', 'élève')
            ->setParameter('surname', 'modèl')
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
                                ->setParameter('teacherId', $teacherId)
                                ->setParameter('uai', $uai)
                                ->getQuery()
                                ->getResult();
        return  $classrooms;
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
                            ->setParameter('teacherId', $teacherId)
                            ->setParameter('classroomName', $classroomName)
                            ->setParameter('uai', $uai)
                            ->setParameter('classroomCode', $classroomCode)
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
                                ->setParameter('classroomId', $classroomId)
                                ->setParameter('studentId', $studentId)
                                ->getQuery()
                                ->getOneOrNullResult();
        return $studentClassrooms;
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
                                                    ->setParameter('classroomName', $classroomName)
                                                    ->setParameter('uai', $uai)
                                                    ->setParameter('rights', 2)
                                                    ->getQuery()
                                                    ->getResult();
        return $studentClassroomsAndRelatedTeacher;
    }


    public function getAllDemoStudent($demoStudent) {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class,'u')
            ->where('u.firstname = :firstname')
            ->andWhere('u.surname = :surname')
            ->andWhere('u.pseudo = :demoStudent')
            ->setParameter('firstname', 'élève')
            ->setParameter('surname', 'modèl')
            ->setParameter('demoStudent', $demoStudent)
            ->getQuery()
            ->getResult();
    }
}
