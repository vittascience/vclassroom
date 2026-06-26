<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ActivityLinkClassroom;
use Classroom\Entity\ActivityLinkUser;
use Classroom\Entity\Classroom;
use Classroom\Entity\ClassroomLinkUser;
use Classroom\Entity\CourseLinkUser;
use User\Entity\User;

class ClassroomLinkUserRepository extends EntityRepository
{
    /**
     * Returns lightweight stats for a classroom: student count and pending corrections count.
     * Used by get_by_user to avoid loading full student data at initial page load.
     */
    public function getClassroomStats($classroomId, $demoStudent)
    {
        $em = $this->getEntityManager();

        $studentCount = (int) $em->createQueryBuilder()
            ->select('COUNT(clu.user)')
            ->from(ClassroomLinkUser::class, 'clu')
            ->join('clu.user', 'u')
            ->where('clu.classroom = :classroomId')
            ->andWhere('clu.rights = 0')
            ->andWhere('u.pseudo != :demoStudent')
            ->setParameters(['classroomId' => $classroomId, 'demoStudent' => $demoStudent])
            ->getQuery()
            ->getSingleScalarResult();

        $activitiesCount = (int) $em->createQueryBuilder()
            ->select('COUNT(alc.id)')
            ->from(ActivityLinkClassroom::class, 'alc')
            ->where('alc.classroom = :classroomId')
            ->setParameter('classroomId', $classroomId)
            ->getQuery()
            ->getSingleScalarResult();

        if ($studentCount === 0) {
            return ['studentCount' => 0, 'pendingCorrections' => 0, 'activitiesCount' => $activitiesCount];
        }

        $pendingCorrections = (int) $em->createQueryBuilder()
            ->select('COUNT(alu.id)')
            ->from(ActivityLinkUser::class, 'alu')
            ->join(ClassroomLinkUser::class, 'clu', 'WITH', 'clu.user = alu.user')
            ->join('clu.user', 'u')
            ->where('clu.classroom = :classroomId')
            ->andWhere('clu.rights = 0')
            ->andWhere('alu.correction = 1')
            ->andWhere('u.pseudo != :demoStudent')
            ->setParameters(['classroomId' => $classroomId, 'demoStudent' => $demoStudent])
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'studentCount'       => $studentCount,
            'pendingCorrections' => $pendingCorrections,
            'activitiesCount'    => $activitiesCount,
        ];
    }

    public function getAllStudentsInClassroom($classroom, $rights, $demoStudent=null)
    {
        $students = $this->getStudentsOrdered($classroom, $rights, $demoStudent);

        // Collect non-null student IDs in a single pass
        $studentIds = [];
        foreach ($students as $student) {
            if ($student !== null) {
                $studentIds[] = $student->getUser()->getId();
            }
        }

        if (empty($studentIds)) {
            return [];
        }

        // Batch query: fetch all activities for all students at once (replaces N individual queries).
        $allActivities = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('alu')
            ->from(ActivityLinkUser::class, 'alu')
            ->where('alu.user IN (:userIds)')
            ->setParameter('userIds', $studentIds)
            ->getQuery()
            ->getResult();

        // Batch query: fetch all course links for all students at once (replaces N individual queries).
        $allCourses = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('clu')
            ->from(CourseLinkUser::class, 'clu')
            ->where('clu.user IN (:userIds)')
            ->setParameter('userIds', $studentIds)
            ->getQuery()
            ->getResult();

        // Serialize and group by userId for fast O(1) distribution
        $activitiesByUser = [];
        foreach ($allActivities as $alu) {
            $activitiesByUser[$alu->getUser()->getId()][] = $alu->jsonSerialize();
        }
        $coursesByUser = [];
        foreach ($allCourses as $clu) {
            $coursesByUser[$clu->getUser()->getId()][] = $clu->jsonSerialize();
        }

        // Build the final students array, preserving the order from getStudentsOrdered()
        $arrayStudents = [];
        foreach ($students as $student) {
            if ($student === null) {
                continue;
            }
            $userId = $student->getUser()->getId();
            $arrayStudents[] = [
                'user'       => $student->getUser()->jsonSerialize(),
                'activities' => $activitiesByUser[$userId] ?? [],
                'courses'    => $coursesByUser[$userId] ?? [],
                'pwd'        => $student->getUser()->getPassword(),
            ];
        }

        return $arrayStudents;
    }

    /**
     * Count all students (excluding demoStudent) across all classrooms of a given teacher.
     * Single query, no entity hydration — returns a scalar int.
     */
    public function countStudentsForTeacher(int $teacherId, string $demoStudent): int
    {
        return (int) $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(student_clu.user)')
            ->from(ClassroomLinkUser::class, 'teacher_clu')
            ->innerJoin(
                ClassroomLinkUser::class, 'student_clu', 'WITH',
                'student_clu.classroom = teacher_clu.classroom AND student_clu.rights = 0'
            )
            ->innerJoin(User::class, 'u', 'WITH', 'student_clu.user = u.id')
            ->where('teacher_clu.user = :teacherId')
            ->andWhere('teacher_clu.rights = 2')
            ->andWhere('u.pseudo != :demoStudent')
            ->setParameters([
                'teacherId' => $teacherId,
                'demoStudent' => $demoStudent,
            ])
            ->getQuery()
            ->getSingleScalarResult();
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
        // Single query replacing the original 2 queries: fetch all students with their User data
        // in one shot (fetch join on 'u' populates the Doctrine identity map, preventing N lazy loads later).
        $allStudents = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('clu', 'u')
            ->from(ClassroomLinkUser::class, 'clu')
            ->join('clu.user', 'u')
            ->where('clu.rights = :rights')
            ->andWhere('clu.classroom = :classroom')
            ->setParameters([
                'rights'    => $rights,
                'classroom' => $classroomId,
            ])
            ->orderBy('u.pseudo', 'ASC')
            ->getQuery()
            ->getResult();

        // Separate demoStudent from the rest in PHP (avoids a second SQL query)
        $demo   = null;
        $others = [];
        foreach ($allStudents as $student) {
            $user = $student->getUser();
            if (
                $user->getPseudo()    === $demoStudent &&
                $user->getFirstname() === 'élève' &&
                $user->getSurname()   === 'modèl'
            ) {
                $demo = $student;
            } else {
                $others[] = $student;
            }
        }

        // Prepend demoStudent (null if classroom has no demo student)
        array_unshift($others, $demo);

        return $others;
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
