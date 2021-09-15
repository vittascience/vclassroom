<?php

namespace Classroom\Repository;

use Doctrine\ORM\EntityRepository;
use Classroom\Entity\ClassroomLinkUser;
use Classroom\Entity\UsersLinkApplications;
use Classroom\Entity\GroupsLinkApplications;
use Classroom\Entity\UsersLinkApplicationsFromGroups;

class ApplicationsRepository extends EntityRepository
{
    /**
     *  Check the teacher limitation 
     *  @var Integer $teacher_id
     *  @var Interger $students_number => the number of students that the teacher want to add 
     *  @return 'canAdd' => false if the limit is reached
     */
    public function isStudentsLimitReachedForTeacher(Int $teacher_id, Int $students_number): ?array
    {
        include_once(__DIR__ . "/../../../../../default-restrictions/constants.php");

        $Applications = $this->getEntityManager()->getRepository(UsersLinkApplications::class)->findBy(['user' => $teacher_id]);
        $ApplicationFromGroup = $this->getEntityManager()->getRepository(UsersLinkApplicationsFromGroups::class)->findOneBy(['user' => $teacher_id]);

        $today = new \DateTime('NOW');

        // Teacher's var
        $totalStudentsFromTeacher = 0;
        $maxStudentsPerTeachers = 0;

        // Group's var
        $maxStudentsPerGroup = 0;
        $maxStudentsPerTeachersGroup = 0;
        $totalStudentsInTheGroup = 0;

        $teacherInfo = [
            'active' => false,
            'numbersOfApps' => 0,
            'actualStudents' => 0,
            'maxStudents' => 0,
            'applications' => []
        ];


        $groupInfo = [
            'active' => false,
            'outDated' => false,
            'actualStudents' => 0,
            'maxStudents' => 0,
            'maxStudentsPerTeacher' => 0
        ];

        // Personnal apps management
        if ($Applications) {
            foreach ($Applications as $application) {
                $userApplication = ['outDated' => false, 'maxStudents' => 0];
                $teacherInfo['numbersOfApps']++;
                if ($application->getDateEnd() > $today) {
                    if ($application->getmaxStudentsPerTeachers() > $maxStudentsPerTeachers) {
                        $maxStudentsPerTeachers = $application->getmaxStudentsPerTeachers();
                        $teacherInfo['active'] = true;
                    }
                } else {
                    $userApplication['outDated'] = true;
                }
                $userApplication['maxStudents'] = $application->getmaxStudentsPerTeachers();
                $teacherInfo['applications'][] = $userApplication;
            }
        } else {
            $maxStudentsPerTeachers = userDefaultRestrictions['maxStudents'];
        }

        if (!$teacherInfo['active']) {
            $maxStudentsPerTeachers = userDefaultRestrictions['maxStudents'];
        }
        $teacherInfo['maxStudents'] = $maxStudentsPerTeachers;



        // get the actuel count of students of the teacher
        $actualTeacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)
            ->findBy(['user' => $teacher_id, 'rights' => 2]);
        foreach ($actualTeacherClassrooms as $classroom) {
            // retrieve all student for the current classroom
            $studentsInClassroomFromActualTeacher = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)
                ->findBy(['classroom' => $classroom->getClassroom()->getId(), 'rights' => 0]);

            $totalStudentsFromTeacher += count($studentsInClassroomFromActualTeacher);
        }
        $teacherInfo['actualStudents'] = $totalStudentsFromTeacher;

        // Group apps management
        if ($ApplicationFromGroup) {
            // The group app exist
            $groupInfo['active'] = true;

            // Get the link between the group and the app
            $GroupLinkApplication = $this->getEntityManager()->getRepository(GroupsLinkApplications::class)
                ->findOneBy([
                    'application' => $ApplicationFromGroup->getApplication(),
                    'group' => $ApplicationFromGroup->getGroup()
                ]);

            // Check if the app if outdated or not
            if ($GroupLinkApplication->getDateEnd() > $today) {
                if ($GroupLinkApplication->getmaxStudentsPerTeachers() > $maxStudentsPerTeachers) {
                    $maxStudentsPerTeachersGroup = $GroupLinkApplication->getmaxStudentsPerTeachers();
                    $maxStudentsPerGroup = $GroupLinkApplication->getmaxStudentsPerGroups();

                    $teachersFromGroupWithThisApp = $this->getEntityManager()->getRepository(UsersLinkApplicationsFromGroups::class)
                        ->findBy([
                            'group' => $ApplicationFromGroup->getGroup(),
                            'application' => $ApplicationFromGroup->getApplication()
                        ]);

                    // count the students in the group
                    foreach ($teachersFromGroupWithThisApp as $teacher) {
                        $teacherPersonalMax = 0;
                        $teacherPersonalApps = $this->getEntityManager()->getRepository(UsersLinkApplications::class)->findBy(['user' => $teacher->getUser()]);
                        foreach ($teacherPersonalApps as $personalApp) {
                            if ($personalApp->getDateEnd() > $today) {
                                $teacherPersonalMax = $personalApp->getmaxStudentsPerTeachers();
                            }
                        }
                        $teacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['user' => $teacher->getUser(), 'rights' => 2]);
                        foreach ($teacherClassrooms as $classroomObject) {
                            // retrieve all student for the current classroom
                            $studentsInClassroom = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomObject->getClassroom()->getId(), 'rights' => 0]);

                            if ($teacherPersonalMax < count($studentsInClassroom)) {
                                $totalStudentsInTheGroup += count($studentsInClassroom);
                            }
                        }
                    }
                    $groupInfo['actualStudents'] += $totalStudentsInTheGroup;
                }
            } else {
                $groupInfo['outDated'] = true;
            }

            // Register log
            $groupInfo['maxStudents'] = $GroupLinkApplication->getmaxStudentsPerGroups();
            $groupInfo['maxStudentsPerTeacher'] = $GroupLinkApplication->getmaxStudentsPerTeachers();
        }


        // if the teacher application limit is not reached we can add the student
        // $maxStudentsPerTeachers = 0 = unlimited
        if ($maxStudentsPerTeachers >= $totalStudentsFromTeacher + $students_number || $maxStudentsPerTeachers == 0) {
            return ['canAdd' => true];
        } else if ($maxStudentsPerTeachers < $totalStudentsFromTeacher + $students_number && !$ApplicationFromGroup) {
            return ['canAdd' => false, 'message' => 'personalLimit', 'teacherInfo' => $teacherInfo, 'groupInfo' => $groupInfo];
        } else {
            if ($groupInfo['outDated']) {
                return ['canAdd' => false, 'message' => 'personalLimitAndGroupOutDated', 'teacherInfo' => $teacherInfo, 'groupInfo' => $groupInfo];
            } else {
                // if the group's application limit is not reached with the total group's students + the actual students count from the teacher
                if ($totalStudentsInTheGroup < $maxStudentsPerGroup && $maxStudentsPerTeachersGroup >= $totalStudentsFromTeacher + $students_number) {
                    return ['canAdd' => true];
                } else {
                    // Otherwise we denied the addition
                    return ['canAdd' => false, 'message' => 'bothLimitReached', 'teacherInfo' => $teacherInfo, 'groupInfo' => $groupInfo];
                }
            }
        }
    }


    // Check if the application can be attributed to the teacher
    public function isApplicationFromGroupFull(Int $group_id, Int $app_id, int $user_id)
    {
        $Application = $this->getEntityManager()->getRepository(GroupsLinkApplications::class)->findOneBy(['group' => $group_id, 'application' => $app_id]);
        $endDate = $Application->getDateEnd();
        $today = new \DateTime('NOW');

        if (!$endDate || $endDate < $today) {
            return ['canAdd' => false, 'message' => 'outDated'];
        }

        $maxTeachersPerGroup = $Application->getmaxTeachersPerGroups();
        $maxStudentsPerGroup = $Application->getmaxStudentsPerGroups();
        $maxStudentsPerTeachers = $Application->getmaxStudentsPerTeachers();

        $totalStudentsInTheGroup = 0;
        $totalStudentsFromTeacher = 0;

        $teachersFromGroupWithThisApp = $this->getEntityManager()->getRepository(UsersLinkApplicationsFromGroups::class)->findBy(['group' => $group_id, 'application' => $app_id]);
        if (count($teachersFromGroupWithThisApp) >= $maxTeachersPerGroup) {
            return ['canAdd' => false, 'message' => 'maxTeachers'];
        }

        // count the students already in the group
        foreach ($teachersFromGroupWithThisApp as $teacher) {
            $teacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['user' => $teacher->getUser(), 'rights' => 2]);
            foreach ($teacherClassrooms as $classroomObject) {
                // retrieve all student for the current classroom
                $studentsInClassroom = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroomObject->getClassroom()->getId(), 'rights' => 0]);
                $totalStudentsInTheGroup += count($studentsInClassroom);
            }
        }

        $actualTeacherClassrooms = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['user' => $user_id, 'rights' => 2]);
        foreach ($actualTeacherClassrooms as $classroom) {
            // retrieve all student for the current classroom
            $studentsInClassroomFromActualTeacher = $this->getEntityManager()->getRepository(ClassroomLinkUser::class)->findBy(['classroom' => $classroom->getClassroom()->getId(), 'rights' => 0]);
            $totalStudentsFromTeacher += count($studentsInClassroomFromActualTeacher);
        }

        if (($totalStudentsInTheGroup + $totalStudentsFromTeacher) >= $maxStudentsPerGroup) {
            return [
                'canAdd' => false,
                'message' => 'maxStudentsInGroup',
                'actualStudents' => $totalStudentsInTheGroup,
                'studentsFromTeacher' => $totalStudentsFromTeacher,
                'maxStudents' => $maxStudentsPerGroup
            ];
        }

        if ($totalStudentsFromTeacher >= $maxStudentsPerTeachers) {
            return ['canAdd' => false, 'message' => 'maxStudentsFromTeahcer'];
        }

        return ['canAdd' => true];
    }
}
