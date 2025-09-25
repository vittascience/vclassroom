<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;
use Classroom\Entity\CourseLinkUser;
use User\Entity\User;

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
                                ->select('partial alu.{id, reference, dateBegin, dateEnd, dateSend}', 'partial a.{id, title}')
                                ->from(ActivityLinkUser::class, 'alu')
                                ->join('alu.activity', 'a')
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
            ->setParameters(array(
                'firstname' => 'élève',
                'surname' => 'modèl',
                'name' => $pseudo
            ))
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
            ->andWhere('u.firstname = :firstname')
            ->andWhere('u.surname = :surname')
            ->setParameters(array(
                'rights' => $rights,
                'classroom' => $classroomId,
                'demoStudent' => $demoStudent,
                'firstname' => 'élève',
                'surname' => 'modèl'
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


    public function getAllDemoStudent($demoStudent) {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u')
            ->from(User::class,'u')
            ->where('u.firstname = :firstname')
            ->andWhere('u.surname = :surname')
            ->andWhere('u.pseudo = :demoStudent')
            ->setParameters(array(
                'demoStudent' => $demoStudent,
                'firstname' => 'élève',
                'surname' => 'modèl'
            ))
            ->getQuery()
            ->getResult();
    }
}
