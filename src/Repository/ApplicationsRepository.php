<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\UsersLinkGroups;
use Classroom\Entity\UsersLinkApplications;

class ApplicationsRepository extends EntityRepository
{
    /**
     *  Check the teacher limitation 
     *  @return true if the limit is reached
     */
    public function isStudentsLimitReachedForTeacher(Int $teacher_id): ?array {
        // replace "limitationStudentsPerTeachers" with const
        $limitationStudentsPerTeachers = 0;
        $totalStudentsTeacher = 0;

        $usersApplications = $this->getEntityManager()->getRepository(UsersLinkApplications::class)->findBy(['user' => $teacher_id]);
        if ($usersApplications) {
            foreach ($usersApplications as $application) {
                // If the application is expired, we do not take its limit
                if ($application->getDateEnd() < new \DateTime('NOW')) {
                    continue;
                }
                // get the limitation for the teacher
                if (!empty($application->getmaxStudentsPerTeachers())) {
                    if ($application->getmaxStudentsPerTeachers() > $limitationStudentsPerTeachers) {
                        $limitationStudentsPerTeachers = $application->getmaxStudentsPerTeachers();
                    }
                }  
            }
        }

        $teacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['user' => $teacher_id, 'rights'=> 2]);
        foreach($teacherClassrooms as $classroomObject) {
            // retrieve all student for the current classroom
            $studentsInClassroom = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomObject->getClassroom()->getId(),'rights'=> 0]);
            $totalStudentsTeacher += count($studentsInClassroom);
        }

        if ($limitationStudentsPerTeachers != 0) {
            $teacherLimit = ($limitationStudentsPerTeachers <= $totalStudentsTeacher);
        } else {
            $teacherLimit = false;
        }

        return ['isLimited' => $teacherLimit, 
                'totalStudentsTeacher' => $totalStudentsTeacher, 
                'limitStudentsTeacher' => $limitationStudentsPerTeachers];
    }

    /**
     * 'StudentsPerTeachers' -> return true if the limit is reached
     * 'StudentsPerGroups' -> return true if the limit is reached
     * @var Integer $teacher_id
     * @return Array
     */
    public function isStudentsLimitReachedForTeacherInGroup(Int $teacher_id): ?array {
        $limitationStudentsPerTeachers = 0;
        $totalStudentsTeacher = 0;
        $limitationStudentsPerGroups = 0;
        $totalStudentsGroup = 0;

        $group = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['user' => $teacher_id]);
        if ($group) {
            // Get the limitation for the group and teacher
            $applications = $this->getEntityManager()
            ->getRepository(UsersLinkGroupsLinkApplicationsRepository::class)
            ->findAll(['user' => $teacher_id]);

            if ($applications) {
                foreach ($applications as $application) {
                    // get the limitation for the group
                    if (!empty($application->getmaxStudentsPerGroups())) {
                        if ($application->getmaxStudentsPerGroups() > $limitationStudentsPerGroups) {
                            $limitationStudentsPerGroups = $application->getmaxStudentsPerGroups();
                        }
                    }
                    // get the limitation for the teacher
                    if (!empty($application->getmaxStudentsPerTeachers())) {
                        if ($application->getmaxStudentsPerTeachers() > $limitationStudentsPerTeachers) {
                            $limitationStudentsPerTeachers = $application->getmaxStudentsPerTeachers();
                        }
                    }
                }
            }
            // Get the students, from the teachers in the group
            $usersFromGroup = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['group' => $group[0]->getGroup()]);
            foreach ($usersFromGroup as $teacher) {
                $teacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['user' => $teacher->getUser(), 'rights'=> 2]);
                foreach($teacherClassrooms as $classroomObject) {
                    // retrieve all student for the current classroom
                    $studentsInClassroom = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomObject->getClassroom()->getId(),'rights'=> 0]);
                    // add classroom students to the total
                    if ($teacher->getUser() == $teacher_id) {
                        $totalStudentsTeacher += count($studentsInClassroom);
                    }
                    $totalStudentsGroup += count($studentsInClassroom);
                }
            }
        }

        if ($limitationStudentsPerGroups != 0) {
            $groupLimit = ($limitationStudentsPerGroups <= $totalStudentsGroup);
        } else {
            $groupLimit = false;
        }

        if ($limitationStudentsPerTeachers != 0) {
            $teacherLimit = ($limitationStudentsPerTeachers <= $totalStudentsTeacher);
        } else {
            $teacherLimit = false;
        }

        return ['studentsPerTeachers' => $teacherLimit, 
                'studentsPerGroups' => $groupLimit, 
                'totalStudentsTeacher' => $totalStudentsTeacher, 
                'totalStudentsGroup' => $totalStudentsGroup,
                'limitStudentsTeacher' => $limitationStudentsPerTeachers,
                'limitStudentsGroup' => $limitationStudentsPerGroups];
    }

}


/* $group = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['user' => $teacher_id]);
        if ($group) {
            // Get the limitation for the group and teacher
            $applications = $this->getEntityManager()->getRepository(GroupsLinkApplications::class)->findAll(['group' => $group[0]->getGroup()]);
            if ($applications) {
                foreach ($applications as $application) {
                    $app = $this->getEntityManager()->getRepository(Applications::class)->findOneBy(['id' => $application->getApplication()]);
                    // get the limitation for the group
                    if (!empty($app->getmaxStudentsPerGroups())) {
                        if ($app->getmaxStudentsPerGroups() > $limitationStudentsPerGroups) {
                            $limitationStudentsPerGroups = $app->getmaxStudentsPerGroups();
                        }
                    }
                    // get the limitation for the teacher
                    if (!empty($app->getmaxStudentsPerTeachers())) {
                        if ($app->getmaxStudentsPerTeachers() > $limitationStudentsPerTeachers) {
                            $limitationStudentsPerTeachers = $app->getmaxStudentsPerTeachers();
                        }
                    }  
                }
            }
            // Get the students, from the teachers in the group
            $usersFromGroup = $this->getEntityManager()->getRepository(UsersLinkGroups::class)->findBy(['group' => $group[0]->getGroup()]);
            foreach ($usersFromGroup as $teacher) {
                $teacherClassrooms = $this->getEntityManager()->getRepository('Classroom\Entity\ClassroomLinkUser')->findBy(['user' => $teacher->getUser(), 'rights'=> 2]);
                foreach($teacherClassrooms as $classroomObject) {
                    // retrieve all student for the current classroom
                    $studentsInClassroom = $this->getEntityManager()->getRepository('Classroom\Entity\ClassroomLinkUser')->findBy(['classroom' => $classroomObject->getClassroom()->getId(),'rights'=> 0]);
                    // add classroom students to the total
                    if ($teacher->getUser() == $teacher_id) {
                        $totalStudentsTeacher += count($studentsInClassroom);
                    }
                    $totalStudentsGroup += count($studentsInClassroom);
                }
            }
        } */